<?php

namespace Keboola\ThoughtSpot\Command;

use Keboola\DbWriter\Exception\UserException;

class CreateTable extends AbstractCommand
{
    public static $allowedTypes = [
        'int', 'bigint',
        'double', 'float',
        'bool',
        'varchar',
        'date', 'time', 'datetime', 'timestamp',
    ];

    public function __construct($dbParams, $table)
    {
        $columns = array_filter($table['items'], function ($item) {
            return (strtolower($item['type']) !== 'ignore');
        });

        $columnsSql = [];
        foreach ($columns as $col) {
            if (!in_array(strtolower($col['type']), static::$allowedTypes)) {
                throw new UserException(sprintf('Type %s not allowed', $col['type']));
            }
            $type = strtoupper($col['type']);
            if (!empty($col['size'])) {
                $type .= "({$col['size']})";
                if (strtoupper($col['type']) === 'ENUM') {
                    $type = $col['size'];
                }
            }

            $columnsSql[] = $this->quote($col['dbName']) . " $type";
        }

        $pkSql = '';
        if (!empty($table['primaryKey'])) {
            $pkSql = sprintf(
                ", PRIMARY KEY (%s)",
                implode(',', $table['primaryKey'])
            );
        }

        $sql = sprintf("CREATE %s TABLE %s (%s %s);",
            isset($table['type']) && strtolower($table['type']) != 'standard' ? $table['type'] : '',
            $this->getFullTableName($dbParams, $table['dbName']),
            implode(',', $columnsSql),
            $pkSql
        );

        $this->command = $this->getTqlCommand($sql);
    }
}