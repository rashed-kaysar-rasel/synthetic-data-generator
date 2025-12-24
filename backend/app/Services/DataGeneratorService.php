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
     * @return string The path to the generated file.
     */
    public function generate(array $generationConfig, array $schema): string
    {
        $this->generationConfig = $generationConfig;
        $this->schema = $schema;

        $format = $this->generationConfig['format'];
        
        if ($format === 'sql') {
            $fileName = uniqid('data_') . '.sql';
            $filePath = Storage::disk('local')->path($fileName);
            $file = fopen($filePath, 'w');
            $this->generateSqlFile($file);
            fclose($file);
            return $filePath;
        }
        
        if ($format === 'csv') {
            return $this->generateCsvZip();
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
        foreach ($this->generationConfig['tables'] as $tableName => $tableConfig) {
            for ($i = 0; $i < $tableConfig['rowCount']; $i++) {
                $rowData = $this->generateRow($tableName, $tableConfig);
                $columns = '`' . implode('`, `', array_keys($rowData)) . '`';
                $values = implode(', ', array_map([$this, 'quoteValue'], array_values($rowData)));
                fwrite($file, "INSERT INTO `$tableName` ($columns) VALUES ($values);\n");
            }
        }
    }

    /**
     * Generate data in CSV format and create a zip archive.
     *
     * @return string The path to the generated zip file.
     * @throws \Exception
     */
    protected function generateCsvZip(): string
    {
        $zip = new ZipArchive();
        $zipFileName = Storage::disk('local')->path(uniqid('data_') . '.zip');

        if ($zip->open($zipFileName, ZipArchive::CREATE) !== TRUE) {
            throw new \Exception("Cannot open <$zipFileName>\n");
        }

        foreach ($this->generationConfig['tables'] as $tableName => $tableConfig) {
            $csvFileName = $tableName . '.csv';
            $csvFilePath = Storage::disk('local')->path($csvFileName);
            $csvFile = fopen($csvFilePath, 'w');

            $firstRow = true;
            for ($i = 0; $i < $tableConfig['rowCount']; $i++) {
                $rowData = $this->generateRow($tableName, $tableConfig);
                if ($firstRow) {
                    fputcsv($csvFile, array_keys($rowData));
                    $firstRow = false;
                }
                fputcsv($csvFile, $rowData);
            }
            fclose($csvFile);
            $zip->addFile($csvFilePath, $csvFileName);
        }

        $zip->close();

        // Clean up individual CSV files
        foreach ($this->generationConfig['tables'] as $tableName => $tableConfig) {
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
            $providerKey = $tableConfig['columns'][$columnName]['provider'] ?? null;

            if ($columnSchema['isForeignKey']) {
                $relationship = collect($this->schema['relationships'])->first(function ($rel) use ($tableName, $columnName) {
                    return $rel['from_table'] === $tableName && $rel['from_column'] === $columnName;
                });

                if ($relationship && isset($this->generatedPrimaryKeys[$relationship['to_table']])) {
                    $rowData[$columnName] = $this->faker->randomElement($this->generatedPrimaryKeys[$relationship['to_table']]);
                    continue;
                }
            }
            
            if ($providerKey) {
                $value = $this->generateValueFromProvider($providerKey);
                $rowData[$columnName] = $value;

                if ($columnSchema['isPrimaryKey']) {
                    $this->generatedPrimaryKeys[$tableName][] = $value;
                }
            } else {
                $rowData[$columnName] = $this->getDefaultValueForType($columnSchema['dataType']);
            }
        }
        return $rowData;
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
}
