<?php

namespace App\Services;

class SchemaSignatureService
{
    public function compute(array $schema): string
    {
        $tables = $schema['tables'] ?? [];
        $normalized = [];

        foreach ($tables as $table) {
            $tableName = (string) ($table['name'] ?? '');
            $columns = $table['columns'] ?? [];
            $columnParts = [];

            foreach ($columns as $column) {
                $columnName = (string) ($column['name'] ?? '');
                $dataType = strtolower((string) ($column['dataType'] ?? ''));
                $columnParts[] = $columnName . ':' . $dataType;
            }

            sort($columnParts);
            $normalized[$tableName] = $columnParts;
        }

        ksort($normalized);

        return hash('sha256', json_encode($normalized));
    }
}
