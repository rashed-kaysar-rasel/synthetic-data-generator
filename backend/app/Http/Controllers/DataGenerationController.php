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
        ]);

        $userId = auth()->id() ?? 1; // Fallback for now

        $constraintErrors = $this->validateGenerationConstraints($schema, $validated);
        if (!empty($constraintErrors)) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $constraintErrors], 422);
            }
            return Redirect::back()->withErrors($constraintErrors);
        }

        $fileToken = (string) Str::uuid();
        $fileName = $fileToken . ($validated['format'] === 'csv' ? '.zip' : '.sql');

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
}
