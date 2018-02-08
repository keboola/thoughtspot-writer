<?php

namespace Keboola\ThoughtSpot;

use Keboola\Csv\CsvFile;
use Keboola\DbWriter\Writer as BaseWriter;
use Keboola\DbWriter\WriterInterface;
use Symfony\Component\Process\Process;

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
        $dstFile = str_replace('.csv', '', $csv->getFileInfo()->getFilename()) . microtime(true) . '.csv';

        // copy file to server using scp
        $process = new Process(sprintf(
            'sshpass -p%s scp -oStrictHostKeyChecking=no %s %s@%s:/tmp/%s',
            $this->dbParams['ssh']['password'],
            $csv->getFileInfo()->getPathname(),
            $this->dbParams['ssh']['user'],
            $this->dbParams['ssh']['host'],
            $dstFile
        ));
        $process->setTimeout(3600);
        $process->mustRun();

        // load file to TS using tsload
        $tsloadCmd = 'tsload'
            . sprintf(' --target_database %s', $this->dbParams['database'])
            . sprintf(' --target_table %s', $table['dbName'])
            . sprintf(' --source_file /tmp/%s', $dstFile)
            . ' --v 1 --field_separator "," --has_header_row';

        if ($table['incremental'] == false) {
            $tsloadCmd .= ' --empty_target';
        }

        $process = new Process(sprintf(
            "sshpass -p%s ssh -oStrictHostKeyChecking=no %s@%s '%s'",
            $this->dbParams['ssh']['password'],
            $this->dbParams['ssh']['user'],
            $this->dbParams['ssh']['host'],
            $tsloadCmd
        ));

        $process->mustRun();
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