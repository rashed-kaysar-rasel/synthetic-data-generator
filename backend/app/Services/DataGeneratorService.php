<?php

namespace App\Services;

use Faker\Generator as FakerGenerator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Service for generating synthetic data based on a schema and configuration.
 */
class DataGeneratorService
{
    /**
     * The Faker instance for generating random data.
     * @var FakerGenerator
     */
    protected $faker;

    /**
     * The database schema.
     * @var array
     */
    protected $schema;

    /**
     * The configuration for data generation.
     * @var array
     */
    protected $generationConfig;

    /**
     * Stores generated primary keys to maintain relational integrity.
     * @var array
     */
    protected $generatedPrimaryKeys = [];

    /**
     * Track unique constraint values per table.
     * @var array
     */
    protected $uniqueValues = [];

    /**
     * Unique constraints keyed by table.
     * @var array
     */
    protected $uniqueConstraints = [];

    /**
     * Auto-increment counters per table.
     * @var array
     */
    protected $autoIncrementCounters = [];

    /**
     * Unique counters for non-auto-increment unique columns.
     * @var array
     */
    protected $uniqueColumnCounters = [];

    /**
     * DataGeneratorService constructor.
     * @param FakerGenerator $faker
     */
    public function __construct(FakerGenerator $faker)
    {
        $this->faker = $faker;
    }

    /**
     * Generate data and return the path to the generated file.
     *
     * @param array $generationConfig
     * @param array $schema
     * @param string|null $outputFileName
     * @return string The path to the generated file.
     */
    public function generate(array $generationConfig, array $schema, ?string $outputFileName = null): string
    {
        $this->generationConfig = $generationConfig;
        $this->schema = $schema;
        $this->generatedPrimaryKeys = [];
        $this->uniqueValues = [];
        $this->autoIncrementCounters = [];
        $this->uniqueColumnCounters = [];
        $this->uniqueConstraints = $this->buildUniqueConstraints($schema);

        $format = $this->generationConfig['format'];
        
        if ($format === 'sql') {
            $fileName = $outputFileName ?: uniqid('data_') . '.sql';
            if (!str_ends_with($fileName, '.sql')) {
                $fileName .= '.sql';
            }
            Storage::disk('public')->makeDirectory('generated_data');
            $filePath = Storage::disk('public')->path('generated_data/' . $fileName);
            $file = fopen($filePath, 'w');
            $this->generateSqlFile($file);
            fclose($file);
            return $filePath;
        }
        
        if ($format === 'csv') {
            return $this->generateCsvZip($outputFileName);
        }

        return '';
    }

    /**
     * Generate data in SQL format and write to a file.
     *
     * @param resource $file
     */
    protected function generateSqlFile($file): void
    {
        foreach ($this->getOrderedTableConfigs() as $tableName => $tableConfig) {
            for ($i = 0; $i < $tableConfig['rowCount']; $i++) {
                $rowData = $this->generateRow($tableName, $tableConfig);
                $columnKeys = array_keys($rowData);
                $sanitizedColumns = array_map([$this, 'normalizeIdentifier'], $columnKeys);
                $sanitizedTableName = $this->normalizeIdentifier($tableName);
                $columns = '`' . implode('`, `', $sanitizedColumns) . '`';
                $values = implode(', ', array_map([$this, 'quoteValue'], array_values($rowData)));
                fwrite($file, "INSERT INTO `{$sanitizedTableName}` ($columns) VALUES ($values);\n");
            }
        }
    }

    /**
     * Generate data in CSV format and create a zip archive.
     *
     * @param string|null $outputFileName
     * @return string The path to the generated zip file.
     * @throws \Exception
     */
    protected function generateCsvZip(?string $outputFileName = null): string
    {
        $zip = new ZipArchive();
        $fileName = $outputFileName ?: uniqid('data_') . '.zip';
        if (!str_ends_with($fileName, '.zip')) {
            $fileName .= '.zip';
        }
        Storage::disk('public')->makeDirectory('generated_data');
        $zipFileName = Storage::disk('public')->path('generated_data/' . $fileName);

        if ($zip->open($zipFileName, ZipArchive::CREATE) !== TRUE) {
            throw new \Exception("Cannot open <$zipFileName>\n");
        }

        foreach ($this->getOrderedTableConfigs() as $tableName => $tableConfig) {
            $csvFileName = $tableName . '.csv';
            $csvFilePath = Storage::disk('local')->path($csvFileName);
            $csvFile = fopen($csvFilePath, 'w');

            $firstRow = true;
            for ($i = 0; $i < $tableConfig['rowCount']; $i++) {
                $rowData = $this->generateRow($tableName, $tableConfig);
                if ($firstRow) {
                    $columnKeys = array_keys($rowData);
                    $sanitizedColumns = array_map([$this, 'normalizeIdentifier'], $columnKeys);
                    fputcsv($csvFile, $sanitizedColumns);
                    $firstRow = false;
                }
                fputcsv($csvFile, $rowData);
            }
            fclose($csvFile);
            $zip->addFile($csvFilePath, $csvFileName);
        }

        $zip->close();

        // Clean up individual CSV files
        foreach ($this->getOrderedTableConfigs() as $tableName => $tableConfig) {
            $csvFileName = $tableName . '.csv';
            $csvFilePath = Storage::disk('local')->path($csvFileName);
            File::delete($csvFilePath);
        }

        return $zipFileName;
    }

    /**
     * Generate a single row of data for a table.
     *
     * @param string $tableName
     * @param array $tableConfig
     * @return array
     */
    protected function generateRow(string $tableName, array $tableConfig): array
    {
        $rowData = [];
        $tableSchema = collect($this->schema['tables'])->firstWhere('name', $tableName);

        foreach ($tableSchema['columns'] as $columnSchema) {
            $columnName = $columnSchema['name'];
            $rowData[$columnName] = $this->generateColumnValue(
                $tableName,
                $tableConfig,
                $columnSchema
            );
        }

        $this->ensureUniqueConstraints($tableName, $tableConfig, $tableSchema, $rowData);

        foreach ($tableSchema['columns'] as $columnSchema) {
            if ($columnSchema['isPrimaryKey']) {
                $this->generatedPrimaryKeys[$tableName][] = $rowData[$columnSchema['name']];
            }
        }

        return $rowData;
    }

    /**
     * Order generation configs using the schema table order.
     *
     * @return array<string, array>
     */
    protected function getOrderedTableConfigs(): array
    {
        $ordered = [];
        $configTables = $this->generationConfig['tables'] ?? [];

        foreach ($this->schema['tables'] as $table) {
            $tableName = $table['name'];
            if (isset($configTables[$tableName])) {
                $ordered[$tableName] = $configTables[$tableName];
            }
        }

        return $ordered;
    }

    /**
     * Generate a value from a Faker provider.
     *
     * @param string $providerKey
     * @return mixed
     */
    protected function generateValueFromProvider(string $providerKey)
    {
        [$group, $provider] = explode('.', $providerKey);
        return $this->faker->{$provider};
    }

    /**
     * Get a default value for a given data type.
     *
     * @param string $dataType
     * @return mixed
     */
    protected function getDefaultValueForType(string $dataType)
    {
        $dataType = strtolower($dataType);
        if (str_contains($dataType, 'int')) {
            return $this->faker->randomNumber();
        }
        if (str_contains($dataType, 'string') || str_contains($dataType, 'char') || str_contains($dataType, 'text')) {
            return $this->faker->word;
        }
        if (str_contains($dataType, 'date')) {
            return $this->faker->date();
        }
        if (str_contains($dataType, 'time')) {
            return $this->faker->time();
        }
        if (str_contains($dataType, 'bool')) {
            return $this->faker->boolean;
        }
        return null;
    }

    /**
     * Quote a value for SQL insertion.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function quoteValue($value)
    {
        if (is_null($value)) {
            return 'NULL';
        }
        if (is_string($value)) {
            return "'" . addslashes($value) . "'";
        }
        return $value;
    }

    /**
     * Normalize identifiers that may already be quoted.
     *
     * @param string $identifier
     * @return string
     */
    protected function normalizeIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return $identifier;
        }

        $first = $identifier[0];
        $last = $identifier[strlen($identifier) - 1];
        if (($first === '`' || $first === '"' || $first === "'") && $last === $first) {
            return substr($identifier, 1, -1);
        }

        return $identifier;
    }

    protected function buildUniqueConstraints(array $schema): array
    {
        $constraintsByTable = [];
        foreach ($schema['tables'] ?? [] as $table) {
            $tableName = $table['name'];
            foreach ($table['constraints'] ?? [] as $constraint) {
                $type = $constraint['type'] ?? '';
                if (!in_array($type, ['primary_key', 'unique'], true)) {
                    continue;
                }
                $columns = $constraint['columns'] ?? [];
                if (!$columns) {
                    continue;
                }
                $constraintsByTable[$tableName][] = [
                    'type' => $type,
                    'columns' => $columns,
                    'key' => implode('|', $columns),
                ];
            }
        }

        return $constraintsByTable;
    }

    protected function generateColumnValue(string $tableName, array $tableConfig, array $columnSchema)
    {
        $columnName = $columnSchema['name'];
        $providerKey = $tableConfig['columns'][$columnName]['provider'] ?? null;

        if (!empty($columnSchema['autoIncrement'])) {
            $this->autoIncrementCounters[$tableName] = ($this->autoIncrementCounters[$tableName] ?? 0) + 1;
            return $this->autoIncrementCounters[$tableName];
        }

        if ($columnSchema['isForeignKey']) {
            $relationship = collect($this->schema['relationships'])->first(function ($rel) use ($tableName, $columnName) {
                return $rel['from_table'] === $tableName && $rel['from_column'] === $columnName;
            });

            if ($relationship && isset($this->generatedPrimaryKeys[$relationship['to_table']])) {
                return $this->faker->randomElement($this->generatedPrimaryKeys[$relationship['to_table']]);
            }
            if ($relationship && !empty($columnSchema['nullable'])) {
                return null;
            }
            if ($relationship) {
                throw new \RuntimeException("No parent rows available for foreign key {$tableName}.{$columnName}.");
            }
        }

        if (!empty($columnSchema['isUnique']) || !empty($columnSchema['isPrimaryKey'])) {
            return $this->generateUniqueColumnValue($tableName, $columnSchema, $providerKey);
        }

        if ($providerKey) {
            return $this->generateValueFromProvider($providerKey);
        }

        if (array_key_exists('defaultValue', $columnSchema) && $columnSchema['defaultValue'] !== null) {
            return $columnSchema['defaultValue'];
        }

        return $this->getDefaultValueForType($columnSchema['dataType']);
    }

    protected function generateUniqueColumnValue(string $tableName, array $columnSchema, ?string $providerKey)
    {
        if ($providerKey) {
            return $this->generateUniqueProviderValue($providerKey);
        }

        $columnName = $columnSchema['name'];
        $dataType = strtolower($columnSchema['dataType'] ?? '');
        $counterKey = $tableName . '.' . $columnName;
        $this->uniqueColumnCounters[$counterKey] = ($this->uniqueColumnCounters[$counterKey] ?? 0) + 1;
        $counter = $this->uniqueColumnCounters[$counterKey];

        if (str_contains($dataType, 'int')) {
            return $counter;
        }

        if (str_contains($dataType, 'date')) {
            return date('Y-m-d', strtotime("+{$counter} days"));
        }

        if (str_contains($dataType, 'time')) {
            return date('H:i:s', strtotime("+{$counter} seconds"));
        }

        return $columnName . '_' . uniqid((string) $counter, true);
    }

    protected function generateUniqueProviderValue(string $providerKey)
    {
        [$group, $provider] = explode('.', $providerKey);
        $uniqueFaker = $this->faker->unique();
        return $uniqueFaker->{$provider};
    }

    protected function ensureUniqueConstraints(string $tableName, array $tableConfig, $tableSchema, array &$rowData): void
    {
        $constraints = $this->uniqueConstraints[$tableName] ?? [];
        foreach ($constraints as $constraint) {
            $columns = $constraint['columns'];
            $constraintKey = $constraint['key'];
            if (!isset($this->uniqueValues[$tableName][$constraintKey])) {
                $this->uniqueValues[$tableName][$constraintKey] = [];
            }
            $attempts = 0;
            $valueKey = $this->buildConstraintValueKey($columns, $rowData);

            while (isset($this->uniqueValues[$tableName][$constraintKey][$valueKey])) {
                if ($attempts++ > 25) {
                    throw new \RuntimeException("Unable to satisfy unique constraint on {$tableName} (" . implode(', ', $columns) . ").");
                }
                foreach ($columns as $columnName) {
                    $columnSchema = collect($tableSchema['columns'])->firstWhere('name', $columnName);
                    if (!$columnSchema) {
                        continue;
                    }
                    $rowData[$columnName] = $this->generateColumnValue($tableName, $tableConfig, $columnSchema);
                }
                $valueKey = $this->buildConstraintValueKey($columns, $rowData);
            }

            $this->uniqueValues[$tableName][$constraintKey][$valueKey] = true;
        }
    }

    protected function buildConstraintValueKey(array $columns, array $rowData): string
    {
        $parts = [];
        foreach ($columns as $column) {
            $parts[] = (string) ($rowData[$column] ?? '');
        }
        return implode('|', $parts);
    }
}
