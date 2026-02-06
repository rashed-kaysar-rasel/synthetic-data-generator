<?php

namespace App\Services;

use Illuminate\Database\ConnectionInterface;

/**
 * Extracts schema metadata from a live database connection.
 */
class DatabaseSchemaService
{
    /**
     * Extract schema for the given connection.
     *
     * @param ConnectionInterface $connection
     * @param string $driver
     * @return array{tables: array, relationships: array, error: ?string}
     */
    public function extract(ConnectionInterface $connection, string $driver): array
    {
        try {
            if ($driver === 'mysql') {
                return $this->extractMySql($connection);
            }
            if ($driver === 'pgsql') {
                return $this->extractPostgres($connection);
            }

            return [
                'tables' => [],
                'relationships' => [],
                'error' => 'Unsupported database driver.',
            ];
        } catch (\Throwable $exception) {
            return [
                'tables' => [],
                'relationships' => [],
                'error' => $exception->getMessage(),
            ];
        }
    }

    private function extractMySql(ConnectionInterface $connection): array
    {
        $database = $connection->getDatabaseName();

        $tableRows = $connection->select(
            "SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_type = 'BASE TABLE'",
            [$database]
        );

        $tablesByName = [];
        foreach ($tableRows as $row) {
            $tableName = $row->table_name;
            $tablesByName[$tableName] = [
                'name' => $tableName,
                'columns' => [],
                'constraints' => [],
                'indexes' => [],
            ];
        }

        $columnRows = $connection->select(
            "SELECT table_name, column_name, column_type, is_nullable, column_default, extra
             FROM information_schema.columns
             WHERE table_schema = ?
             ORDER BY ordinal_position",
            [$database]
        );

        foreach ($columnRows as $row) {
            if (!isset($tablesByName[$row->table_name])) {
                continue;
            }
            $tablesByName[$row->table_name]['columns'][] = [
                'name' => $row->column_name,
                'dataType' => $row->column_type,
                'nullable' => strtoupper((string) $row->is_nullable) === 'YES',
                'defaultValue' => $row->column_default,
                'autoIncrement' => stripos((string) $row->extra, 'auto_increment') !== false,
                'isPrimaryKey' => false,
                'isForeignKey' => false,
                'isUnique' => false,
            ];
        }

        $constraintRows = $connection->select(
            "SELECT tc.constraint_name, tc.constraint_type, tc.table_name, kcu.column_name,
                    kcu.referenced_table_name, kcu.referenced_column_name
             FROM information_schema.table_constraints tc
             JOIN information_schema.key_column_usage kcu
               ON tc.constraint_name = kcu.constraint_name
              AND tc.table_schema = kcu.table_schema
              AND tc.table_name = kcu.table_name
             WHERE tc.table_schema = ?",
            [$database]
        );

        $constraints = $this->buildConstraintsFromRows($constraintRows);

        $tables = $this->applyConstraintsToTables($tablesByName, $constraints);
        $relationships = $this->constraintsToRelationships($constraints);

        return [
            'tables' => array_values($tables),
            'relationships' => $relationships,
            'error' => null,
        ];
    }

    private function extractPostgres(ConnectionInterface $connection): array
    {
        $schemaRow = $connection->selectOne('SELECT current_schema() AS schema_name');
        $schemaName = $schemaRow?->schema_name ?? 'public';

        $tableRows = $connection->select(
            "SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_type = 'BASE TABLE'",
            [$schemaName]
        );

        $tablesByName = [];
        foreach ($tableRows as $row) {
            $tableName = $row->table_name;
            $tablesByName[$tableName] = [
                'name' => $tableName,
                'columns' => [],
                'constraints' => [],
                'indexes' => [],
            ];
        }

        $columnRows = $connection->select(
            "SELECT table_name, column_name, data_type, is_nullable, column_default,
                    character_maximum_length, numeric_precision, numeric_scale, is_identity
             FROM information_schema.columns
             WHERE table_schema = ?
             ORDER BY ordinal_position",
            [$schemaName]
        );

        foreach ($columnRows as $row) {
            if (!isset($tablesByName[$row->table_name])) {
                continue;
            }
            $tablesByName[$row->table_name]['columns'][] = [
                'name' => $row->column_name,
                'dataType' => $this->formatPostgresDataType($row),
                'nullable' => strtoupper((string) $row->is_nullable) === 'YES',
                'defaultValue' => $row->column_default,
                'autoIncrement' => $this->isPostgresAutoIncrement($row),
                'isPrimaryKey' => false,
                'isForeignKey' => false,
                'isUnique' => false,
            ];
        }

        $constraintRows = $connection->select(
            "SELECT tc.constraint_name, tc.constraint_type, tc.table_name, kcu.column_name,
                    ccu.table_name AS referenced_table_name, ccu.column_name AS referenced_column_name
             FROM information_schema.table_constraints tc
             JOIN information_schema.key_column_usage kcu
               ON tc.constraint_name = kcu.constraint_name
              AND tc.table_schema = kcu.table_schema
             LEFT JOIN information_schema.constraint_column_usage ccu
               ON tc.constraint_name = ccu.constraint_name
              AND tc.table_schema = ccu.table_schema
             WHERE tc.table_schema = ?",
            [$schemaName]
        );

        $constraints = $this->buildConstraintsFromRows($constraintRows);
        $tables = $this->applyConstraintsToTables($tablesByName, $constraints);
        $relationships = $this->constraintsToRelationships($constraints);

        return [
            'tables' => array_values($tables),
            'relationships' => $relationships,
            'error' => null,
        ];
    }

    private function buildConstraintsFromRows(array $constraintRows): array
    {
        $constraintsByKey = [];

        foreach ($constraintRows as $row) {
            $type = strtoupper((string) $row->constraint_type);
            $table = $row->table_name ?? null;
            $column = $row->column_name ?? null;
            if (!$table || !$column) {
                continue;
            }

            $key = ($row->constraint_name ?? '') . '|' . $table . '|' . $type;
            if (!isset($constraintsByKey[$key])) {
                $constraintsByKey[$key] = [
                    'type' => $this->normalizeConstraintType($type),
                    'table' => $table,
                    'columns' => [],
                    'referenceTable' => null,
                    'referenceColumns' => [],
                ];
            }

            $constraintsByKey[$key]['columns'][] = $column;

            if ($type === 'FOREIGN KEY') {
                $refTable = $row->referenced_table_name ?? null;
                $refColumn = $row->referenced_column_name ?? null;
                if ($refTable && $refColumn) {
                    $constraintsByKey[$key]['referenceTable'] = $refTable;
                    $constraintsByKey[$key]['referenceColumns'][] = $refColumn;
                }
            }
        }

        $constraints = [];
        foreach ($constraintsByKey as $constraint) {
            $constraints[] = $constraint;
        }

        return $constraints;
    }

    private function normalizeConstraintType(string $type): string
    {
        if ($type === 'PRIMARY KEY') {
            return 'primary_key';
        }
        if ($type === 'UNIQUE') {
            return 'unique';
        }
        if ($type === 'FOREIGN KEY') {
            return 'foreign_key';
        }
        return strtolower($type);
    }

    private function applyConstraintsToTables(array $tablesByName, array $constraints): array
    {
        foreach ($constraints as $constraint) {
            $tableName = $constraint['table'] ?? null;
            if (!$tableName || !isset($tablesByName[$tableName])) {
                continue;
            }
            $tablesByName[$tableName]['constraints'][] = $constraint;
        }

        foreach ($tablesByName as &$table) {
            foreach ($table['constraints'] as $constraint) {
                $columns = $constraint['columns'] ?? [];
                foreach ($table['columns'] as &$column) {
                    if (!in_array($column['name'], $columns, true)) {
                        continue;
                    }
                    if (($constraint['type'] ?? '') === 'primary_key') {
                        $column['isPrimaryKey'] = true;
                        $column['isUnique'] = true;
                    }
                    if (($constraint['type'] ?? '') === 'unique' && count($columns) === 1) {
                        $column['isUnique'] = true;
                    }
                    if (($constraint['type'] ?? '') === 'foreign_key') {
                        $column['isForeignKey'] = true;
                    }
                }
            }
        }

        return $tablesByName;
    }

    private function constraintsToRelationships(array $constraints): array
    {
        $relationships = [];
        foreach ($constraints as $constraint) {
            if (($constraint['type'] ?? '') !== 'foreign_key') {
                continue;
            }
            $fromColumns = $constraint['columns'] ?? [];
            $toColumns = $constraint['referenceColumns'] ?? [];
            $count = min(count($fromColumns), count($toColumns));
            for ($i = 0; $i < $count; $i++) {
                $relationships[] = [
                    'from_table' => $constraint['table'],
                    'from_column' => $fromColumns[$i],
                    'to_table' => $constraint['referenceTable'],
                    'to_column' => $toColumns[$i],
                ];
            }
        }

        return $relationships;
    }

    private function isPostgresAutoIncrement(object $row): bool
    {
        if (isset($row->is_identity) && strtoupper((string) $row->is_identity) === 'YES') {
            return true;
        }

        return isset($row->column_default) && str_contains((string) $row->column_default, 'nextval(');
    }

    private function formatPostgresDataType(object $row): string
    {
        $dataType = $row->data_type ?? 'text';
        $length = $row->character_maximum_length ?? null;
        $precision = $row->numeric_precision ?? null;
        $scale = $row->numeric_scale ?? null;

        if ($length !== null) {
            return $dataType . '(' . $length . ')';
        }

        if ($precision !== null) {
            if ($scale !== null) {
                return $dataType . '(' . $precision . ',' . $scale . ')';
            }
            return $dataType . '(' . $precision . ')';
        }

        return $dataType;
    }
}
