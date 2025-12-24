<?php

namespace App\Services;

/**
 * Class TopologicalSortService
 *
 * This service is responsible for performing a topological sort on a set of
 * database tables based on their foreign key relationships. This ensures that
 * tables with dependencies are processed in the correct order.
 */
class TopologicalSortService
{
    /**
     * Sorts an array of tables topologically based on their foreign key relationships.
     *
     * @param array $tables An array of table definitions.
     * @param array $relationships An array of relationship definitions.
     * @return array A new array of table definitions, sorted topologically.
     */
    public function sort(array $tables, array $relationships): array
    {
        $graph = [];
        $inDegree = [];
        $tableNames = array_map(fn($t) => $t['name'], $tables);

        // Initialize graph and in-degree for each table
        foreach ($tableNames as $tableName) {
            $graph[$tableName] = [];
            $inDegree[$tableName] = 0;
        }

        // Build the graph and in-degree based on relationships
        foreach ($relationships as $rel) {
            $from = $rel['from_table'];
            $to = $rel['to_table'];
            if ($from !== $to && in_array($from, $tableNames) && in_array($to, $tableNames)) {
                $graph[$to][] = $from;
                $inDegree[$from]++;
            }
        }

        // Initialize the queue with nodes having an in-degree of 0
        $queue = new \SplQueue();
        foreach ($inDegree as $tableName => $degree) {
            if ($degree === 0) {
                $queue->enqueue($tableName);
            }
        }

        $sorted = [];
        // Process the queue
        while (!$queue->isEmpty()) {
            $tableName = $queue->dequeue();
            $sorted[] = $tableName;

            if (isset($graph[$tableName])) {
                foreach ($graph[$tableName] as $dependentTable) {
                    $inDegree[$dependentTable]--;
                    if ($inDegree[$dependentTable] === 0) {
                        $queue->enqueue($dependentTable);
                    }
                }
            }
        }
        
        // Handle circular dependencies
        if (count($sorted) !== count($tableNames)) {
            $remaining = array_diff($tableNames, $sorted);
            $sorted = array_merge($sorted, $remaining);
        }

        // Create the sorted list of tables
        $sortedTables = [];
        foreach($sorted as $tableName) {
            foreach($tables as $table) {
                if ($table['name'] === $tableName) {
                    $sortedTables[] = $table;
                    break;
                }
            }
        }

        return $sortedTables;
    }
}
