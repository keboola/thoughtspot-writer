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
        $sql = sprintf(
            "CREATE TABLE %s (",
            $this->getFullTableName($dbParams, $table['dbName'])
        );

        $columns = array_filter($table['items'], function ($item) {
            return (strtolower($item['type']) !== 'ignore');
        });

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

            $sql .= $this->quote($col['dbName']) . " $type";
            $sql .= ',';
        }
        $sql = substr($sql, 0, -1);
        $sql .= ");";

        $this->command = $this->getTqlCommand($sql);
    }
}