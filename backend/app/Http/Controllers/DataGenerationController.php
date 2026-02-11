<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateDataJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Throwable;

class DataGenerationController extends Controller
{
    /**
     * Store a newly created resource in storage and dispatch a job.
     */
    public function store(Request $request)
    {
        $schema = Session::get('schema');

        if (!$schema) {
            $error = ['message' => 'No schema found in session. Please upload a SQL DDL file first.'];
            if ($request->expectsJson()) {
                return response()->json(['errors' => $error], 422);
            }
            return Redirect::back()->withErrors($error);
        }

        $validated = $request->validate([
            'format' => 'required|in:sql,csv',
            'seed' => 'nullable|integer',
            'tables' => 'required|array',
            'tables.*.rowCount' => 'required|integer|min:0',
            'tables.*.columns' => 'required|array',
            'tables.*.columns.*.provider' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if (!$value) return;
                    [$group, $provider] = explode('.', $value);
                    $providers = Config::get('data_providers.providers');
                    if (!isset($providers[$group]) || !in_array($provider, $providers[$group])) {
                        $fail("The provider '$value' is invalid.");
                    }
                },
            ],
            'tables.*.columns.*.enumValues' => ['nullable', 'array'],
            'tables.*.columns.*.enumValues.*' => ['nullable', 'string'],
            'tables.*.columns.*.slugSourceColumn' => [
                'nullable',
                'string',
            ],
            'insert' => 'sometimes|boolean',
            'connection' => 'nullable|array',
            'connection.driver' => 'required_if:insert,true|in:mysql,pgsql',
            'connection.host' => 'required_if:insert,true|string',
            'connection.port' => 'nullable|integer',
            'connection.database' => 'required_if:insert,true|string',
            'connection.username' => 'required_if:insert,true|string',
            'connection.password' => 'nullable|string',
        ]);

        $userId = auth()->id() ?? 1; // Fallback for now

        $slugErrors = $this->validateSlugSourceColumns($schema, $validated);
        if (!empty($slugErrors)) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $slugErrors], 422);
            }
            return Redirect::back()->withErrors($slugErrors);
        }

        $constraintErrors = $this->validateGenerationConstraints($schema, $validated);
        if (!empty($constraintErrors)) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $constraintErrors], 422);
            }
            return Redirect::back()->withErrors($constraintErrors);
        }

        $enumErrors = $this->validateEnumValues($validated);
        if (!empty($enumErrors)) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $enumErrors], 422);
            }
            return Redirect::back()->withErrors($enumErrors);
        }

        $insertEnabled = !empty($validated['insert']);
        if ($insertEnabled && $validated['format'] !== 'sql') {
            $error = ['format' => ['Direct insert requires SQL format.']];
            if ($request->expectsJson()) {
                return response()->json(['errors' => $error], 422);
            }
            return Redirect::back()->withErrors($error);
        }

        $fileToken = (string) Str::uuid();
        $fileName = $fileToken . ($validated['format'] === 'csv' ? '.zip' : '.sql');

        if ($insertEnabled) {
            try {
                $connection = $this->buildExternalConnection($validated['connection']);
                $connection->getPdo();

                $autoIncrementSeeds = $this->buildAutoIncrementSeeds($schema, $connection);
                $batchSize = 500;
                $buffers = [];
                $insertedCounts = [];

                $connection->beginTransaction();
                $generator = app(\App\Services\DataGeneratorService::class);
                $generator->generateWithCallback(
                    $validated,
                    $schema,
                    $fileName,
                    $autoIncrementSeeds,
                    function ($tableName, $rowData) use (&$buffers, $batchSize, $connection, &$insertedCounts) {
                        $buffers[$tableName][] = $rowData;
                        if (count($buffers[$tableName]) >= $batchSize) {
                            $connection->table($tableName)->insert($buffers[$tableName]);
                            $insertedCounts[$tableName] = ($insertedCounts[$tableName] ?? 0) + count($buffers[$tableName]);
                            $buffers[$tableName] = [];
                        }
                    }
                );

                foreach ($buffers as $tableName => $rows) {
                    if (!$rows) {
                        continue;
                    }
                    $connection->table($tableName)->insert($rows);
                    $insertedCounts[$tableName] = ($insertedCounts[$tableName] ?? 0) + count($rows);
                }

                $connection->commit();

                $payload = [
                    'job_id' => null,
                    'status' => 'completed',
                    'download_url' => route('generate.download', ['file_name' => $fileName]),
                    'inserted' => $insertedCounts,
                ];

                if ($request->expectsJson()) {
                    return response()->json($payload);
                }

                return Redirect::back()->with('job', $payload);
            } catch (Throwable $exception) {
                if (isset($connection)) {
                    try {
                        $connection->rollBack();
                    } catch (Throwable $rollbackException) {
                    }
                }
                $error = ['insert' => ['Data insertion failed: ' . $exception->getMessage()]];
                if ($request->expectsJson()) {
                    return response()->json(['errors' => $error], 422);
                }
                return Redirect::back()->withErrors($error);
            }
        }

        $job = new GenerateDataJob($validated, $userId, $fileName);
        $jobId = Queue::connection()->push($job);

        $payload = [
            'job_id' => $jobId,
            'status' => $jobId ? 'pending' : 'completed',
            'download_url' => $jobId ? null : route('generate.download', ['file_name' => $fileName]),
        ];

        if ($jobId) {
            Cache::put("generate:job:{$jobId}", [
                'file_name' => $fileName,
            ], now()->addDay());
        }

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return Redirect::back()->with('job', $payload);
    }

    /**
     * Display the status of a data generation job.
     */
    public function show(string $job_id)
    {
        $job = DB::table('jobs')->find($job_id);
        $cached = Cache::get("generate:job:{$job_id}");
        $fileName = $cached['file_name'] ?? null;

        if (!$job) {
            if ($fileName) {
                $filePath = storage_path('app/public/generated_data/' . $fileName);
                if (File::exists($filePath)) {
                    return response()->json([
                        'status' => 'completed',
                        'download_url' => route('generate.download', ['file_name' => $fileName]),
                        'error' => null,
                    ]);
                }
            }

            return response()->json(['status' => 'not_found'], 404);
        }

        $status = 'pending';
        $downloadUrl = null;
        $error = null;

        if (isset($job->failed_at)) {
            $status = 'failed';
            // A more robust implementation would store the actual error message
            $error = 'Job processing failed.';
        } elseif (!$job->payload) { // Assuming job is deleted on success
            // For now, check for the output file's existence.
            if ($fileName) {
                $filePath = storage_path('app/public/generated_data/' . $fileName);
                if (File::exists($filePath)) {
                    $status = 'completed';
                    $downloadUrl = route('generate.download', ['file_name' => $fileName]);
                }
            }
        }

        // If the job is still in the `jobs` table and not failed, it's pending.
        return response()->json([
            'status' => $status,
            'download_url' => $downloadUrl,
            'error' => $error,
        ]);
    }

    /**
     * Download the generated data file.
     */
    public function download(string $file_name)
    {
        $filePath = storage_path('app/public/generated_data/' . $file_name);

        if (!File::exists($filePath)) {
            abort(404, 'File not found.');
        }

        return response()->download($filePath);
    }

    private function buildExternalConnection(array $connection): \Illuminate\Database\Connection
    {
        $driver = $connection['driver'];
        $port = $connection['port'] ?? ($driver === 'pgsql' ? 5432 : 3306);

        Config::set('database.connections.external', [
            'driver' => $driver,
            'host' => $connection['host'],
            'port' => $port,
            'database' => $connection['database'],
            'username' => $connection['username'],
            'password' => $connection['password'] ?? '',
            'charset' => $driver === 'pgsql' ? 'utf8' : 'utf8mb4',
            'collation' => $driver === 'pgsql' ? null : 'utf8mb4_unicode_ci',
            'prefix' => '',
            'schema' => $driver === 'pgsql' ? 'public' : null,
        ]);

        DB::purge('external');
        return DB::connection('external');
    }

    private function buildAutoIncrementSeeds(array $schema, \Illuminate\Database\Connection $connection): array
    {
        $seeds = [];
        foreach ($schema['tables'] ?? [] as $table) {
            $tableName = $table['name'] ?? null;
            if (!$tableName) {
                continue;
            }
            $autoIncrementColumn = null;
            foreach ($table['columns'] ?? [] as $column) {
                if (!empty($column['autoIncrement'])) {
                    $autoIncrementColumn = $column['name'];
                    break;
                }
            }
            if (!$autoIncrementColumn) {
                continue;
            }
            try {
                $max = $connection->table($tableName)->max($autoIncrementColumn);
                $seeds[$tableName] = is_numeric($max) ? (int) $max : 0;
            } catch (Throwable $exception) {
                $seeds[$tableName] = 0;
            }
        }

        return $seeds;
    }

    private function validateGenerationConstraints(array $schema, array $validated): array
    {
        $errors = [];
        $tableConfigs = $validated['tables'] ?? [];

        $tablesByName = collect($schema['tables'] ?? [])->keyBy('name');

        foreach ($schema['relationships'] ?? [] as $relationship) {
            $childTable = $relationship['from_table'];
            $childColumn = $relationship['from_column'];
            $parentTable = $relationship['to_table'];

            $childConfig = $tableConfigs[$childTable] ?? null;
            $parentConfig = $tableConfigs[$parentTable] ?? null;
            if (!$childConfig || !$parentConfig) {
                continue;
            }

            $childSchema = $tablesByName->get($childTable);
            if (!$childSchema) {
                continue;
            }

            $childColumnSchema = collect($childSchema['columns'] ?? [])->firstWhere('name', $childColumn);
            $childNullable = $childColumnSchema['nullable'] ?? true;

            $parentRowCount = (int) ($parentConfig['rowCount'] ?? 0);
            if (!$childNullable && $parentRowCount === 0) {
                $errors["tables.{$childTable}.rowCount"] = "Table {$childTable} requires parent rows in {$parentTable} for {$childColumn}.";
            }
        }

        foreach ($schema['tables'] ?? [] as $table) {
            $tableName = $table['name'];
            $tableConfig = $tableConfigs[$tableName] ?? null;
            if (!$tableConfig) {
                continue;
            }

            $rowCount = (int) ($tableConfig['rowCount'] ?? 0);
            foreach ($table['constraints'] ?? [] as $constraint) {
                if (!in_array($constraint['type'] ?? '', ['unique', 'primary_key'], true)) {
                    continue;
                }
                $columns = $constraint['columns'] ?? [];
                if (count($columns) !== 1) {
                    continue;
                }
                $columnName = $columns[0];
                $columnSchema = collect($table['columns'] ?? [])->firstWhere('name', $columnName);
                if (!$columnSchema || empty($columnSchema['isForeignKey'])) {
                    continue;
                }
                $relationship = collect($schema['relationships'] ?? [])->first(function ($rel) use ($tableName, $columnName) {
                    return $rel['from_table'] === $tableName && $rel['from_column'] === $columnName;
                });
                if (!$relationship) {
                    continue;
                }
                $parentTable = $relationship['to_table'];
                $parentConfig = $tableConfigs[$parentTable] ?? null;
                if (!$parentConfig) {
                    continue;
                }
                $parentRowCount = (int) ($parentConfig['rowCount'] ?? 0);
                if ($parentRowCount > 0 && $rowCount > $parentRowCount) {
                    $errors["tables.{$tableName}.rowCount"] = "Table {$tableName} exceeds unique FK capacity for {$columnName} (parent {$parentTable} has {$parentRowCount} rows).";
                }
            }
        }

        return $errors;
    }

    private function validateEnumValues(array $validated): array
    {
        $errors = [];
        $tables = $validated['tables'] ?? [];

        foreach ($tables as $tableName => $tableConfig) {
            $columns = $tableConfig['columns'] ?? [];
            foreach ($columns as $columnName => $columnConfig) {
                $provider = $columnConfig['provider'] ?? null;
                if ($provider !== 'text.enum') {
                    continue;
                }
                $rawValues = $columnConfig['enumValues'] ?? [];
                if (!is_array($rawValues)) {
                    $rawValues = [];
                }
                $values = array_filter(array_map(function ($value) {
                    return trim((string) $value);
                }, $rawValues), function ($value) {
                    return $value !== '';
                });
                if (count($values) === 0) {
                    $errors["tables.{$tableName}.columns.{$columnName}.enumValues"] =
                        "Enum values are required for {$tableName}.{$columnName}.";
                }
            }
        }

        return $errors;
    }

    private function validateSlugSourceColumns(array $schema, array $validated): array
    {
        $errors = [];
        $tableConfigs = $validated['tables'] ?? [];
        $schemaTables = collect($schema['tables'] ?? [])->keyBy('name');

        foreach ($tableConfigs as $tableName => $tableConfig) {
            $tableSchema = $schemaTables->get($tableName);
            if (!$tableSchema) {
                continue;
            }
            $schemaColumns = collect($tableSchema['columns'] ?? [])->keyBy('name');
            $columnConfigs = $tableConfig['columns'] ?? [];

            foreach ($columnConfigs as $columnName => $columnConfig) {
                $provider = $columnConfig['provider'] ?? null;
                if ($provider !== 'text.slug') {
                    continue;
                }

                $sourceColumn = $columnConfig['slugSourceColumn'] ?? null;
                if (!$sourceColumn) {
                    $errors["tables.{$tableName}.columns.{$columnName}.slugSourceColumn"] =
                        "Slug source column is required for {$tableName}.{$columnName}.";
                    continue;
                }

                if ($sourceColumn === $columnName) {
                    $errors["tables.{$tableName}.columns.{$columnName}.slugSourceColumn"] =
                        "Slug source column must be different from {$tableName}.{$columnName}.";
                    continue;
                }

                $sourceSchema = $schemaColumns->get($sourceColumn);
                if (!$sourceSchema) {
                    $errors["tables.{$tableName}.columns.{$columnName}.slugSourceColumn"] =
                        "Slug source column {$sourceColumn} does not exist for {$tableName}.";
                    continue;
                }

                if (!$this->isTextLikeDataType($sourceSchema['dataType'] ?? '')) {
                    $errors["tables.{$tableName}.columns.{$columnName}.slugSourceColumn"] =
                        "Slug source column {$sourceColumn} must be a text column for {$tableName}.";
                }
            }
        }

        return $errors;
    }

    private function isTextLikeDataType(string $dataType): bool
    {
        $dataType = strtolower($dataType);
        return str_contains($dataType, 'char')
            || str_contains($dataType, 'text')
            || str_contains($dataType, 'uuid')
            || str_contains($dataType, 'citext');
    }
}
