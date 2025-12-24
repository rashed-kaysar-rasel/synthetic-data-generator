<?php

namespace App\Services;

use PHPSQLParser\PHPSQLParser;

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
        $parser = new PHPSQLParser();
        $parsed = $parser->parse($sql, true);

        $tables = [];
        $relationships = [];

        if (empty($parsed['CREATE TABLE'])) {
            return ['tables' => [], 'relationships' => []];
        }

        foreach ($parsed['CREATE TABLE'] as $create) {
            $tableName = $create['name'];
            $columns = [];
            
            $primaryKeys = [];
            // Extract primary keys from table constraints
            if (isset($create['constraints'])) {
                foreach($create['constraints'] as $constraint) {
                    if ($constraint['type'] === 'PRIMARY KEY') {
                        foreach($constraint['columns'] as $col) {
                            $primaryKeys[] = $col['name'];
                        }
                    }
                }
            }

            // Extract column definitions
            foreach ($create['columns'] as $def) {
                if ($def['type'] === 'column') {
                    $colName = $def['name'];
                    $colType = $def['dataType']['name'];
                    if(isset($def['dataType']['length'])){
                        $colType .= '('.$def['dataType']['length'].')';
                    }

                    // Check if column is a primary key (inline or table constraint)
                    $isPk = in_array($colName, $primaryKeys);
                    if (!$isPk && isset($def['primary'])) {
                        $isPk = true;
                        if (!in_array($colName, $primaryKeys)) {
                            $primaryKeys[] = $colName;
                        }
                    }

                    $columns[] = [
                        'name' => $colName,
                        'dataType' => $colType,
                        'isPrimaryKey' => $isPk,
                        'isForeignKey' => false, // Will be updated when parsing foreign keys
                    ];
                }
            }
            
            // Extract foreign key relationships from table constraints
            if (isset($create['constraints'])) {
                foreach($create['constraints'] as $constraint) {
                    if ($constraint['type'] === 'FOREIGN KEY') {
                        $fromColumns = array_map(fn($c) => $c['name'], $constraint['columns']);
                        $toTable = $constraint['references']['table'];
                        $toColumns = array_map(fn($c) => $c['name'], $constraint['references']['columns']);

                        for($i = 0; $i < count($fromColumns); $i++) {
                             $relationships[] = [
                                'from_table' => $tableName,
                                'from_column' => $fromColumns[$i],
                                'to_table' => $toTable,
                                'to_column' => $toColumns[$i],
                            ];
                            // Mark the column as a foreign key
                            foreach($columns as &$c) {
                                if ($c['name'] === $fromColumns[$i]) {
                                    $c['isForeignKey'] = true;
                                }
                            }
                        }
                    }
                }
            }

            $tables[] = [
                'name' => $tableName,
                'columns' => $columns,
            ];
        }

        return [
            'tables' => $tables,
            'relationships' => $relationships,
        ];
    }
}
