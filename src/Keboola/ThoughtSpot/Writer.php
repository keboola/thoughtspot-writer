<?php

namespace Keboola\ThoughtSpot;

use Keboola\Csv\CsvFile;
use Keboola\DbWriter\Exception\UserException;
use Keboola\DbWriter\Writer as BaseWriter;
use Keboola\DbWriter\WriterInterface;

class Writer extends BaseWriter implements WriterInterface
{
    public function createConnection($dbParams)
    {
        var_dump($dbParams);

        // check params
        foreach (['host', 'database', 'user', 'password'] as $param) {
            if (!isset($dbParams[$param])) {
                throw new UserException(sprintf("Parameter %s is missing.", $param));
            }
        }

        $database = $dbParams['database'];
        $serverList = sprintf('%s %s', $dbParams['host'], '12345');

        $dsn = "Driver={ThoughtSpot(x64)};Database=$database;SERVERLIST=$serverList";

        return odbc_connect($dsn, $dbParams['user'], $dbParams['password']);
    }

    public function write(CsvFile $csv, array $table)
    {
        // TODO: Implement write() method.
    }

    public function drop($tableName)
    {
        // TODO: Implement drop() method.
    }

    public function create(array $table)
    {
        // TODO: Implement create() method.
    }

    public function upsert(array $table, $targetTable)
    {
        // TODO: Implement upsert() method.
    }

    public function tableExists($tableName)
    {
        // TODO: Implement tableExists() method.
    }

    public function generateTmpName($tableName)
    {
        // TODO: Implement generateTmpName() method.
    }

    public function showTables($dbName)
    {
        // TODO: Implement showTables() method.
    }

    public function getTableInfo($tableName)
    {
        // TODO: Implement getTableInfo() method.
    }

    public static function getAllowedTypes()
    {
        // TODO: Implement getAllowedTypes() method.
    }

    public function validateTable($tableConfig)
    {
        // TODO: Implement validateTable() method.
    }
}