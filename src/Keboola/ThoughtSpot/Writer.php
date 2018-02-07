<?php

namespace Keboola\ThoughtSpot;

use Keboola\Csv\CsvFile;
use Keboola\DbWriter\Writer as BaseWriter;
use Keboola\DbWriter\WriterInterface;

class Writer extends BaseWriter implements WriterInterface
{
    /** @var Connection */
    protected $db;

    public function createConnection($dbParams)
    {
        return new Connection($dbParams);
    }

    public function write(CsvFile $csv, array $table)
    {
        // skip header
        $csv->next();

        while ($row = $csv->current()) {
            $sql = sprintf(
                'INSERT INTO %s VALUES (%s)',
                $table['dbName'],
                implode(',', array_fill(0, count($row), '?'))
            );

            $this->db->query($sql, $row);

            $csv->next();
        }
    }

    public function drop($tableName)
    {
        // TODO: Implement drop() method.
    }

    public function create(array $table)
    {
        $sql = sprintf(
            "CREATE TABLE %s (",
            $this->db->quote($table['dbName'])
        );

        $columns = array_filter($table['items'], function ($item) {
            return (strtolower($item['type']) !== 'ignore');
        });
        foreach ($columns as $col) {
            $type = strtoupper($col['type']);
            if (!empty($col['size'])) {
                $type .= "({$col['size']})";
                if (strtoupper($col['type']) === 'ENUM') {
                    $type = $col['size'];
                }
            }

            $sql .= "{$this->db->quote($col['dbName'])} $type";
            $sql .= ',';
        }
        $sql = substr($sql, 0, -1);
        $sql .= ")";

        $this->db->query($sql);
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