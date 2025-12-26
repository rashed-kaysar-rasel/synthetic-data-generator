<?php

namespace App\Services;

use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\exceptions\UnableToCalculatePositionException;

/**
 * Class SqlParserService
 *
 * This service is responsible for parsing SQL DDL (Data Definition Language)
 * to extract a structured representation of the database schema.
 * It uses the `PHPSQLParser` library to interpret SQL statements and
 * identify tables, columns, primary keys, and foreign key relationships.
 */
class SqlParserService
{
    /**
     * Parses a SQL DDL string and extracts schema information.
     *
     * @param string $sql The SQL DDL string to parse.
     * @return array An associative array containing 'tables' and 'relationships'.
     */
    public function parse(string $sql): array
    {
        $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
        $error = null;
        $tablesByName = [];
        $constraints = [];
        $indexes = [];

        foreach ($this->splitStatements($sql) as $statement) {
            if (!preg_match('/\bCREATE\s+TABLE\b/i', $statement)) {
                continue;
            }

            $parser = new PHPSQLParser();
            try {
                $parsed = $parser->parse($statement, true);
            } catch (UnableToCalculatePositionException $exception) {
                try {
                    $parsed = $parser->parse($statement, false);
                } catch (\Throwable $fallbackException) {
                    $error = 'Unable to parse the SQL DDL. Please verify the file syntax.';
                    continue;
                }
            } catch (\Throwable $exception) {
                $error = 'Unable to parse the SQL DDL. Please verify the file syntax.';
                continue;
            }

            if (empty($parsed['TABLE'])) {
                continue;
            }

            foreach ($this->normalizeTableEntries($parsed['TABLE']) as $tableEntry) {
                $tableName = $this->normalizeTableName($tableEntry['name'] ?? '');
                if (!$tableName) {
                    continue;
                }

                $createDef = $tableEntry['create-def']['sub_tree'] ?? [];
                $primaryKeys = $this->extractPrimaryKeys($createDef);
                $columnResult = $this->extractColumns($createDef, $primaryKeys);
                $columns = $columnResult['columns'];
                $primaryKeys = $columnResult['primaryKeys'];
                $tableConstraints = $this->extractConstraintsFromCreateDef($tableName, $createDef, $primaryKeys);
                $tableIndexes = $this->extractIndexesFromCreateDef($tableName, $createDef);

                $tablesByName[$tableName] = [
                    'name' => $tableName,
                    'columns' => $columns,
                    'constraints' => [],
                    'indexes' => [],
                ];

                $constraints = array_merge($constraints, $tableConstraints);
                $indexes = array_merge($indexes, $tableIndexes);
            }
        }

        $alterMetadata = $this->extractAlterMetadata($sql);
        $constraints = array_merge($constraints, $alterMetadata['constraints']);
        $indexes = array_merge($indexes, $alterMetadata['indexes']);

        $constraints = $this->uniqueConstraints($constraints);
        $indexes = $this->uniqueIndexes($indexes);

        $tables = $this->applyConstraintsToTables(
            $tablesByName,
            $constraints,
            $indexes,
            $alterMetadata['columnOverrides']
        );
        $relationships = $this->constraintsToRelationships($constraints);

        return [
            'tables' => $tables,
            'relationships' => $relationships,
            'error' => $error,
        ];
    }

    private function splitStatements(string $sql): array
    {
        $statements = [];
        $current = '';
        $length = strlen($sql);
        $inSingle = false;
        $inDouble = false;
        $inBacktick = false;
        $inLineComment = false;
        $inBlockComment = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            if ($inLineComment) {
                if ($char === "\n") {
                    $inLineComment = false;
                }
                continue;
            }

            if ($inBlockComment) {
                if ($char === '*' && $next === '/') {
                    $inBlockComment = false;
                    $i++;
                }
                continue;
            }

            if (!$inSingle && !$inDouble && !$inBacktick) {
                if ($char === '-' && $next === '-') {
                    $inLineComment = true;
                    $i++;
                    continue;
                }
                if ($char === '#') {
                    $inLineComment = true;
                    continue;
                }
                if ($char === '/' && $next === '*') {
                    $inBlockComment = true;
                    $i++;
                    continue;
                }
            }

            if ($char === "'" && !$inDouble && !$inBacktick) {
                $escaped = $i > 0 && $sql[$i - 1] === '\\';
                if (!$escaped) {
                    $inSingle = !$inSingle;
                }
            } elseif ($char === '"' && !$inSingle && !$inBacktick) {
                $escaped = $i > 0 && $sql[$i - 1] === '\\';
                if (!$escaped) {
                    $inDouble = !$inDouble;
                }
            } elseif ($char === '`' && !$inSingle && !$inDouble) {
                $inBacktick = !$inBacktick;
            }

            if ($char === ';' && !$inSingle && !$inDouble && !$inBacktick) {
                $trimmed = trim($current);
                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }
                $current = '';
                continue;
            }

            $current .= $char;
        }

        $trimmed = trim($current);
        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }

        return $statements;
    }

    private function normalizeTableEntries(array $tableEntry): array
    {
        if (isset($tableEntry['name'])) {
            return [$tableEntry];
        }

        return array_values($tableEntry);
    }

    private function extractPrimaryKeys(array $createDef): array
    {
        $primaryKeys = [];

        foreach ($createDef as $def) {
            if (($def['expr_type'] ?? '') !== 'primary-key') {
                continue;
            }
            foreach ($def['sub_tree'] ?? [] as $node) {
                if (($node['expr_type'] ?? '') !== 'column-list') {
                    continue;
                }
                foreach ($node['sub_tree'] ?? [] as $column) {
                    $name = $this->normalizeIdentifier($column['name'] ?? $column['base_expr'] ?? '');
                    if ($name) {
                        $primaryKeys[] = $name;
                    }
                }
            }
        }

        return array_values(array_unique($primaryKeys));
    }

    private function extractColumns(array $createDef, array $primaryKeys): array
    {
        $columns = [];
        $primaryKeyList = $primaryKeys;

        foreach ($createDef as $def) {
            if (($def['expr_type'] ?? '') !== 'column-def') {
                continue;
            }

            $columnName = $this->getColumnName($def);
            $columnType = $this->getColumnType($def);
            if (!$columnName || !$columnType) {
                continue;
            }

            $columnTypeNode = $this->getColumnTypeNode($def);
            $columnAttributes = $this->getColumnAttributes($columnTypeNode);
            $isPrimaryKey = in_array($columnName, $primaryKeyList, true);
            if (!$isPrimaryKey && $columnTypeNode && !empty($columnTypeNode['primary'])) {
                $isPrimaryKey = true;
                $primaryKeyList[] = $columnName;
            }

            $columns[] = [
                'name' => $columnName,
                'dataType' => $columnType,
                'nullable' => $columnAttributes['nullable'],
                'defaultValue' => $columnAttributes['defaultValue'],
                'autoIncrement' => $columnAttributes['autoIncrement'],
                'isPrimaryKey' => $isPrimaryKey,
                'isForeignKey' => false,
                'isUnique' => $columnAttributes['isUnique'],
            ];
        }

        return [
            'columns' => $columns,
            'primaryKeys' => array_values(array_unique($primaryKeyList)),
        ];
    }

    private function extractConstraintsFromCreateDef(string $tableName, array $createDef, array $primaryKeys): array
    {
        $constraints = [];

        foreach ($createDef as $def) {
            $exprType = $def['expr_type'] ?? '';
            if ($exprType === 'primary-key') {
                $columns = $this->extractColumnListFromNode($def);
                if ($columns) {
                    $constraints[] = [
                        'type' => 'primary_key',
                        'table' => $tableName,
                        'columns' => $columns,
                    ];
                }
            }

            if ($exprType === 'unique-index') {
                $columns = $this->extractColumnListFromNode($def);
                if ($columns) {
                    $constraints[] = [
                        'type' => 'unique',
                        'table' => $tableName,
                        'columns' => $columns,
                    ];
                }
            }

            if ($exprType === 'foreign-key') {
                $constraint = $this->extractForeignKeyConstraint($tableName, $def);
                if ($constraint) {
                    $constraints[] = $constraint;
                }
            }
        }

        if (!empty($primaryKeys)) {
            $constraints[] = [
                'type' => 'primary_key',
                'table' => $tableName,
                'columns' => $primaryKeys,
            ];
        }

        return $constraints;
    }

    private function getColumnName(array $columnDef): ?string
    {
        foreach ($columnDef['sub_tree'] ?? [] as $node) {
            if (($node['expr_type'] ?? '') === 'colref') {
                return $this->normalizeIdentifier($node['base_expr'] ?? $node['name'] ?? '');
            }
        }

        return null;
    }

    private function getColumnType(array $columnDef): ?string
    {
        $columnTypeNode = $this->getColumnTypeNode($columnDef);
        if (!$columnTypeNode) {
            return null;
        }

        $dataType = null;
        $length = null;

        foreach ($columnTypeNode['sub_tree'] ?? [] as $node) {
            if (($node['expr_type'] ?? '') === 'data-type') {
                $dataType = $node['base_expr'] ?? null;
                if (isset($node['length'])) {
                    $length = (string) $node['length'];
                }
            } elseif (($node['expr_type'] ?? '') === 'bracket_expression' && empty($length)) {
                $length = trim($node['base_expr'] ?? '', '()');
            }
        }

        if (!$dataType) {
            return null;
        }

        if ($length !== null && $length !== '') {
            return $dataType . '(' . $length . ')';
        }

        return $dataType;
    }

    private function getColumnTypeNode(array $columnDef): ?array
    {
        foreach ($columnDef['sub_tree'] ?? [] as $node) {
            if (($node['expr_type'] ?? '') === 'column-type') {
                return $node;
            }
        }

        return null;
    }

    private function extractAlterMetadata(string $sql): array
    {
        $constraints = [];
        $indexes = [];
        $columnOverrides = [];

        foreach ($this->splitStatements($sql) as $statement) {
            if (!preg_match('/^\s*ALTER\s+TABLE\s+/i', $statement)) {
                continue;
            }
            $table = $this->extractAlterTableName($statement);
            if (!$table) {
                continue;
            }
            foreach ($this->splitAlterClauses($statement) as $clause) {
                if (preg_match('/^ADD\s+(?:CONSTRAINT\s+`?[^`\\s]+`?\s+)?FOREIGN\s+KEY\s*\\(([^)]+)\\)\s+REFERENCES\s+(.+?)\s*\\(([^)]+)\\)/i', $clause, $match)) {
                    $fromColumns = $this->splitIdentifierList($match[1] ?? '');
                    $toTable = $this->normalizeTableName($match[2] ?? '');
                    $toColumns = $this->splitIdentifierList($match[3] ?? '');
                    if ($fromColumns && $toTable && $toColumns) {
                        $constraints[] = [
                            'type' => 'foreign_key',
                            'table' => $table,
                            'columns' => $fromColumns,
                            'referenceTable' => $toTable,
                            'referenceColumns' => $toColumns,
                        ];
                    }
                    continue;
                }

                if (preg_match('/^ADD\s+PRIMARY\s+KEY\s*\\(([^)]+)\\)/i', $clause, $match)) {
                    $columns = $this->splitIdentifierList($match[1] ?? '');
                    if ($columns) {
                        $constraints[] = [
                            'type' => 'primary_key',
                            'table' => $table,
                            'columns' => $columns,
                        ];
                    }
                    continue;
                }

                if (preg_match('/^ADD\s+UNIQUE\s+KEY\s+`?([^`\\s]+)?`?\s*\\(([^)]+)\\)/i', $clause, $match)) {
                    $name = $this->normalizeIdentifier($match[1] ?? '');
                    $columns = $this->splitIdentifierList($match[2] ?? '');
                    if ($columns) {
                        $constraints[] = [
                            'type' => 'unique',
                            'table' => $table,
                            'columns' => $columns,
                        ];
                        $indexes[] = [
                            'table' => $table,
                            'name' => $name ?: null,
                            'columns' => $columns,
                            'unique' => true,
                        ];
                    }
                    continue;
                }

                if (preg_match('/^ADD\s+KEY\s+`?([^`\\s]+)?`?\s*\\(([^)]+)\\)/i', $clause, $match)) {
                    $name = $this->normalizeIdentifier($match[1] ?? '');
                    $columns = $this->splitIdentifierList($match[2] ?? '');
                    if ($columns) {
                        $indexes[] = [
                            'table' => $table,
                            'name' => $name ?: null,
                            'columns' => $columns,
                            'unique' => false,
                        ];
                    }
                    continue;
                }

                if (preg_match('/^MODIFY\s+`?([^`\\s]+)`?.*AUTO_INCREMENT/i', $clause, $match)) {
                    $column = $this->normalizeIdentifier($match[1] ?? '');
                    if ($column) {
                        $columnOverrides[$table][$column]['autoIncrement'] = true;
                    }
                    continue;
                }
            }
        }

        return [
            'constraints' => $constraints,
            'indexes' => $indexes,
            'columnOverrides' => $columnOverrides,
        ];
    }

    private function extractAlterTableName(string $statement): string
    {
        if (!preg_match('/^\s*ALTER\s+TABLE\s+([`"\']?[^\\s`"\']+[`"\']?(?:\\.[`"\']?[^\\s`"\']+[`"\']?)?)/i', $statement, $match)) {
            return '';
        }

        return $this->normalizeTableName($match[1] ?? '');
    }

    private function splitAlterClauses(string $statement): array
    {
        $clauses = [];
        $statement = preg_replace('/^\s*ALTER\s+TABLE\s+[`"\']?[^\\s`"\']+[`"\']?(?:\\.[`"\']?[^\\s`"\']+[`"\']?)?\s*/i', '', $statement);
        if ($statement === null) {
            return $clauses;
        }

        $current = '';
        $depth = 0;
        $inSingle = false;
        $inDouble = false;
        $inBacktick = false;
        $length = strlen($statement);

        for ($i = 0; $i < $length; $i++) {
            $char = $statement[$i];
            $next = $i + 1 < $length ? $statement[$i + 1] : '';

            if ($char === "'" && !$inDouble && !$inBacktick) {
                $escaped = $i > 0 && $statement[$i - 1] === '\\';
                if (!$escaped) {
                    $inSingle = !$inSingle;
                }
            } elseif ($char === '"' && !$inSingle && !$inBacktick) {
                $escaped = $i > 0 && $statement[$i - 1] === '\\';
                if (!$escaped) {
                    $inDouble = !$inDouble;
                }
            } elseif ($char === '`' && !$inSingle && !$inDouble) {
                $inBacktick = !$inBacktick;
            }

            if (!$inSingle && !$inDouble && !$inBacktick) {
                if ($char === '(') {
                    $depth++;
                } elseif ($char === ')') {
                    $depth = max(0, $depth - 1);
                }
            }

            if ($char === ',' && $depth === 0 && !$inSingle && !$inDouble && !$inBacktick) {
                $trimmed = trim($current);
                if ($trimmed !== '') {
                    $clauses[] = $trimmed;
                }
                $current = '';
                continue;
            }

            $current .= $char;
        }

        $trimmed = trim($current);
        if ($trimmed !== '') {
            $clauses[] = $trimmed;
        }

        return $clauses;
    }

    private function splitIdentifierList(string $list): array
    {
        $parts = array_map('trim', explode(',', $list));
        $identifiers = [];
        foreach ($parts as $part) {
            $name = $this->normalizeIdentifier($part);
            if ($name !== '') {
                $identifiers[] = $name;
            }
        }
        return $identifiers;
    }

    private function normalizeTableName(string $tableName): string
    {
        $clean = $this->normalizeIdentifier($tableName);
        if ($clean === '') {
            return '';
        }
        $clean = str_replace('`', '', $clean);
        $clean = str_replace(['"', "'"], '', $clean);
        $parts = array_map('trim', explode('.', $clean));
        return $parts[count($parts) - 1] ?? $clean;
    }

    private function normalizeIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return '';
        }

        $first = $identifier[0];
        $last = $identifier[strlen($identifier) - 1];
        if (($first === '`' || $first === '"' || $first === "'") && $last === $first) {
            $identifier = substr($identifier, 1, -1);
        }

        return trim($identifier);
    }

    private function uniqueConstraints(array $constraints): array
    {
        $seen = [];
        $unique = [];
        foreach ($constraints as $constraint) {
            $key = implode('|', [
                $constraint['type'] ?? '',
                $constraint['table'] ?? '',
                implode(',', $constraint['columns'] ?? []),
                $constraint['referenceTable'] ?? '',
                implode(',', $constraint['referenceColumns'] ?? []),
            ]);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $constraint;
        }

        return $unique;
    }

    private function uniqueIndexes(array $indexes): array
    {
        $seen = [];
        $unique = [];
        foreach ($indexes as $index) {
            $key = implode('|', [
                $index['table'] ?? '',
                $index['name'] ?? '',
                implode(',', $index['columns'] ?? []),
                ($index['unique'] ?? false) ? '1' : '0',
            ]);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $index;
        }

        return $unique;
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

    private function applyConstraintsToTables(
        array $tablesByName,
        array $constraints,
        array $indexes,
        array $columnOverrides
    ): array {
        foreach ($tablesByName as $tableName => &$table) {
            foreach ($constraints as $constraint) {
                if (($constraint['table'] ?? '') !== $tableName) {
                    continue;
                }
                $table['constraints'][] = $constraint;
            }

            foreach ($indexes as $index) {
                if (($index['table'] ?? '') !== $tableName) {
                    continue;
                }
                $table['indexes'][] = $index;
            }

            foreach ($table['columns'] as &$column) {
                if (isset($columnOverrides[$tableName][$column['name']]['autoIncrement'])) {
                    $column['autoIncrement'] = true;
                }
            }
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

        return array_values($tablesByName);
    }

    private function extractIndexesFromCreateDef(string $tableName, array $createDef): array
    {
        $indexes = [];

        foreach ($createDef as $def) {
            $exprType = $def['expr_type'] ?? '';
            if ($exprType !== 'index' && $exprType !== 'unique-index') {
                continue;
            }
            $columns = $this->extractColumnListFromNode($def);
            if (!$columns) {
                continue;
            }
            $name = $this->extractIndexName($def);
            $indexes[] = [
                'table' => $tableName,
                'name' => $name,
                'columns' => $columns,
                'unique' => $exprType === 'unique-index',
            ];
        }

        return $indexes;
    }

    private function extractColumnListFromNode(array $node): array
    {
        foreach ($node['sub_tree'] ?? [] as $child) {
            if (($child['expr_type'] ?? '') !== 'column-list') {
                continue;
            }
            $columns = [];
            foreach ($child['sub_tree'] ?? [] as $column) {
                $name = $this->normalizeIdentifier($column['name'] ?? $column['base_expr'] ?? '');
                if ($name) {
                    $columns[] = $name;
                }
            }
            return $columns;
        }

        return [];
    }

    private function extractIndexName(array $node): ?string
    {
        foreach ($node['sub_tree'] ?? [] as $child) {
            if (($child['expr_type'] ?? '') === 'const') {
                $name = $this->normalizeIdentifier($child['base_expr'] ?? '');
                return $name ?: null;
            }
        }

        return null;
    }

    private function extractForeignKeyConstraint(string $tableName, array $def): ?array
    {
        $fromColumns = [];
        $toColumns = [];
        $toTable = null;

        foreach ($def['sub_tree'] ?? [] as $node) {
            if (($node['expr_type'] ?? '') === 'column-list') {
                foreach ($node['sub_tree'] ?? [] as $column) {
                    $name = $this->normalizeIdentifier($column['name'] ?? $column['base_expr'] ?? '');
                    if ($name) {
                        $fromColumns[] = $name;
                    }
                }
            }

            if (($node['expr_type'] ?? '') === 'foreign-ref') {
                foreach ($node['sub_tree'] ?? [] as $refNode) {
                    if (($refNode['expr_type'] ?? '') === 'table') {
                        $toTable = $this->normalizeTableName($refNode['table'] ?? $refNode['base_expr'] ?? '');
                    }
                    if (($refNode['expr_type'] ?? '') === 'column-list') {
                        foreach ($refNode['sub_tree'] ?? [] as $column) {
                            $name = $this->normalizeIdentifier($column['name'] ?? $column['base_expr'] ?? '');
                            if ($name) {
                                $toColumns[] = $name;
                            }
                        }
                    }
                }
            }
        }

        if (!$fromColumns || !$toColumns || !$toTable) {
            return null;
        }

        return [
            'type' => 'foreign_key',
            'table' => $tableName,
            'columns' => $fromColumns,
            'referenceTable' => $toTable,
            'referenceColumns' => $toColumns,
        ];
    }

    private function getColumnAttributes(?array $columnTypeNode): array
    {
        if (!$columnTypeNode) {
            return [
                'nullable' => true,
                'defaultValue' => null,
                'autoIncrement' => false,
                'isUnique' => false,
            ];
        }

        $nullable = isset($columnTypeNode['nullable'])
            ? (bool) $columnTypeNode['nullable']
            : true;
        $autoIncrement = !empty($columnTypeNode['auto_inc']);
        $isUnique = !empty($columnTypeNode['unique']);
        $defaultValue = $columnTypeNode['default'] ?? null;

        if ($defaultValue === null) {
            foreach ($columnTypeNode['sub_tree'] ?? [] as $node) {
                if (($node['expr_type'] ?? '') === 'default-value') {
                    $defaultValue = $node['base_expr'] ?? null;
                    break;
                }
            }
        }

        return [
            'nullable' => $nullable,
            'defaultValue' => $defaultValue,
            'autoIncrement' => $autoIncrement,
            'isUnique' => $isUnique,
        ];
    }
}
