<?php

namespace Keboola\ThoughtSpot;

use Keboola\Csv\CsvFile;
use Keboola\DbWriter\Exception\UserException;
use Keboola\DbWriter\Writer as BaseWriter;
use Keboola\DbWriter\WriterInterface;
use Keboola\ThoughtSpot\Command\CommandInterface;
use Keboola\ThoughtSpot\Command\DropTable;
use Symfony\Component\Process\Process;

class Writer extends BaseWriter implements WriterInterface
{
    use Utils;

    /** @var Connection */
    protected $db;

    public function createConnection($dbParams)
    {
        return new Connection($dbParams);
    }

    private function runSshCmd($cmd)
    {
        $this->logger->info(sprintf('Executing command "%s"', $cmd));

        $process = new Process(sprintf(
            'sshpass -p%s ssh -o LogLevel=error -o StrictHostKeyChecking=no %s@%s "%s"',
            $this->dbParams['#sshPassword'],
            $this->dbParams['sshUser'],
            $this->dbParams['host'],
            $cmd
        ));

        try {
            $process->mustRun();
        } catch (\Exception $e) {
            throw new UserException(
                'output: ' . $process->getOutput() . PHP_EOL . 'error output: ' . $process->getErrorOutput()
            );
        }

        $errorOutput = $process->getErrorOutput();
        if (!empty($errorOutput)
            && false === strstr($errorOutput, 'Statement executed successfully')
            && !($cmd instanceof DropTable)
        ) {
            throw new UserException($process->getErrorOutput());
        }

        return $process;
    }

    public function uploadFile(CsvFile $csv)
    {
        $dstFile = str_replace('.csv', '', $csv->getFileInfo()->getFilename()) . microtime(true) . '.csv';

        // copy file to server using scp
        $process = new Process(sprintf(
            'sshpass -p%s scp -o LogLevel=error -o StrictHostKeyChecking=no %s %s@%s:/tmp/%s',
            $this->dbParams['#sshPassword'],
            $csv->getFileInfo()->getPathname(),
            $this->dbParams['sshUser'],
            $this->dbParams['host'],
            $dstFile
        ));
        $process->setTimeout(3600);
        $process->mustRun();

        return $dstFile;
    }

    public function execute($batch)
    {
        foreach ($batch as $command) {
            /** @var CommandInterface $command */
            $this->runSshCmd($command);
        }
    }

    public function write(CsvFile $csv, array $table)
    {

    }

    public function upsert(array $table, $targetTable)
    {
        // TODO: Implement upsert() method.
    }

    public function tableExists($tableName)
    {
        try {
            $this->db->fetchAll(sprintf(
                "SELECT 1 FROM %s",
                $this->getTableNameWithSchema($this->dbParams, $tableName)
            ));
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
        return $this->db->fetchAll("SHOW TABLES;");
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
        if ($this->getConnection() === null) {
            throw new UserException("DB connection unsuccessful");
        }
        $this->runSshCmd('whoami');
    }

    public function drop($tableName)
    {
        // TODO: Implement drop() method.
    }

    public function create(array $table)
    {
        // TODO: Implement create() method.
    }
}
