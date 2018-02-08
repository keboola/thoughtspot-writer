<?php

namespace Keboola\ThoughtSpot;

use Keboola\Csv\CsvFile;
use Keboola\DbWriter\Exception\UserException;
use Keboola\DbWriter\Writer as BaseWriter;
use Keboola\DbWriter\WriterInterface;
use Symfony\Component\Process\Process;

class Writer extends BaseWriter implements WriterInterface
{
    private static $allowedTypes = [
        'int', 'bigint',
        'double', 'float',
        'bool',
        'varchar',
        'date', 'time', 'datetime', 'timestamp',
    ];

    private $defaultSchema = 'falcon_default_schema';

    /** @var Connection */
    protected $db;

    public function createConnection($dbParams)
    {
        return new Connection($dbParams);
    }

    private function runSshCmd($cmd)
    {
        $process = new Process(sprintf(
            "sshpass -p%s ssh -oStrictHostKeyChecking=no %s@%s '%s'",
            $this->dbParams['ssh']['password'],
            $this->dbParams['ssh']['user'],
            $this->dbParams['ssh']['remoteHost'],
            $cmd
        ));

        $process->mustRun();
    }

    private function getTableNameWithSchema($tableName) {
        $schema = empty($this->dbParams['schema']) ? $this->defaultSchema : $this->dbParams['schema'];
        return $schema . '.' . $tableName;
    }

    private function getFullTableName($tableName) {
        return $this->dbParams['database'] . '.' . $this->getTableNameWithSchema($tableName);
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
            $this->dbParams['ssh']['remoteHost'],
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

        $this->runSshCmd($tsloadCmd);
    }

    public function drop($tableName)
    {
        $this->runSshCmd(sprintf('echo "DROP TABLE %s;" | tql', $this->getFullTableName($tableName)));
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
            if (!in_array(strtolower($col['type']), self::$allowedTypes)) {
                throw new UserException(sprintf('Type %s not allowed', $col['type']));
            }
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
        try {
            $this->db->fetchAll(sprintf("SELECT 1 FROM %s", $this->getTableNameWithSchema($tableName)));
            return true;
        } catch (\Exception $e) {
            return false;
        }
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

    public function testConnection()
    {
        return $this->getConnection() !== null;
    }
}