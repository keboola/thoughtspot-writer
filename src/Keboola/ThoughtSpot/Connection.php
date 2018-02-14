<?php

namespace Keboola\ThoughtSpot;

use Keboola\DbWriter\Exception\UserException;

class Connection
{
    private $connection;

    public function __construct(array $options)
    {
        // check params
        foreach (['host', 'database', 'user', '#password'] as $param) {
            if (!isset($options[$param])) {
                throw new UserException(sprintf("Parameter %s is missing.", $param));
            }
        }

        $database = $options['database'];
        $serverList = sprintf('%s %s', $options['host'], '12345');
        $schema = empty($options['schema']) ? 'falcon_default_schema' : $options['schema'];

        $dsn = "Driver={ThoughtSpot(x64)};Database=$database;SERVERLIST=$serverList;SCHEMA=$schema";
        $this->connection = odbc_connect($dsn, $options['user'], $options['#password']);
    }

    public function quote($value)
    {
        return sprintf('%s', $value);
    }

    public function query($sql, array $bind = [])
    {
        $stmt = odbc_prepare($this->connection, $sql);
        odbc_execute($stmt, $bind);
        odbc_free_result($stmt);
    }

    public function fetchAll($sql, $bind = [])
    {
        $stmt = odbc_prepare($this->connection, $sql);
        odbc_execute($stmt, $this->repairBinding($bind));
        $rows = [];
        while ($row = odbc_fetch_array($stmt)) {
            $rows[] = $row;
        }
        odbc_free_result($stmt);
        return $rows;
    }

    public function fetch($sql, $bind, callable $callback)
    {
        $stmt = odbc_prepare($this->connection, $sql);
        odbc_execute($stmt, $this->repairBinding($bind));
        while ($row = odbc_fetch_array($stmt)) {
            $callback($row);
        }
        odbc_free_result($stmt);
    }

    /**
     * Avoid odbc file open http://php.net/manual/en/function.odbc-execute.php
     * @param array $bind
     * @return array
     */
    private function repairBinding(array $bind)
    {
        return array_map(function ($value) {
            if (preg_match("/^'.*'$/", $value)) {
                return " {$value} ";
            } else {
                return $value;
            }
        }, $bind);
    }
}