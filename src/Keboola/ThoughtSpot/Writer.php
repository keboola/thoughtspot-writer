<?php

namespace Keboola\ThoughtSpot;

use Keboola\Csv\CsvFile;
use Keboola\DbWriter\Exception\UserException;
use Keboola\DbWriter\Logger;
use Keboola\DbWriter\Writer as BaseWriter;
use Keboola\DbWriter\WriterInterface;
use Keboola\ThoughtSpot\Command\CommandInterface;
use Keboola\ThoughtSpot\Command\CreateDatabase;
use Keboola\ThoughtSpot\Command\CreateSchema;
use Keboola\ThoughtSpot\Command\DropTable;
use Keboola\ThoughtSpot\Command\ShowDatabases;
use Keboola\ThoughtSpot\Command\ShowSchemas;
use Keboola\ThoughtSpot\Exception\DatabaseNotExistsException;
use Keboola\ThoughtSpot\Exception\SchemaNotExistsException;
use Symfony\Component\Process\Process;

class Writer extends BaseWriter implements WriterInterface
{
    use Utils;

    /** @var Connection */
    protected $db;

    public function __construct($dbParams, Logger $logger)
    {
        if (empty($dbParams['schema'])) {
            $dbParams['schema'] = $this->getDefaultSchema();
        }

        parent::__construct($dbParams, $logger);

        try {
            $this->checkDatabaseExists($dbParams);
        } catch (DatabaseNotExistsException $exception) {
            if (!$this->createDatabase($dbParams)) {
                throw new UserException(
                    sprintf('Unable to create database "%s"', $dbParams['database']),
                    0,
                    $exception
                );
            }
        }
        try {
            $this->checkSchemaExists($dbParams);
        } catch (SchemaNotExistsException $exception) {
            if (!$this->createSchema($dbParams)) {
                throw new UserException(
                    sprintf('Unable to create schema "%s"', $dbParams['schema']),
                    0,
                    $exception
                );
            }
        }
    }

    public function checkDatabaseExists(array $dbParams): bool
    {
        $databases = $this->runSshCmd(
            new ShowDatabases()
        )->getOutput();

        $databases = preg_split("/\r\n|\n|\r/", $databases);

        if (!in_array($dbParams['database'], $databases)) {
            throw new DatabaseNotExistsException(sprintf('Database "%s" does not exists', $dbParams['database']));
        }

        return true;
    }

    public function checkSchemaExists(array $dbParams): bool
    {
        $schemas = $this->runSshCmd(
            new ShowSchemas($dbParams['database'])
        )->getOutput();

        $schemas = array_filter(
            preg_split("/\r\n|\n|\r/", $schemas),
            function ($v){
                if (empty($v)) {
                    return false;
                }
                return true;
            }
        );
        array_walk($schemas, function (&$val) {
            list($schemaName) = explode('|', $val);
            $val = trim($schemaName);
        });

        if (!in_array($dbParams['schema'], $schemas)) {
            throw new SchemaNotExistsException(sprintf('Schema "%s" does not exists', $dbParams['schema']));
        }

        return true;
    }

    public function createConnection($dbParams)
    {
        return new Connection($dbParams);
    }

    private function runSshCmd($cmd): Process
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

    private function createDatabase($dbParams): bool
    {
        $process = $this->runSshCmd(
            new CreateDatabase($dbParams['database'])
        );
        return $process->getExitCode() === 0;
    }

    private function createSchema($dbParams): bool
    {
        $process = $this->runSshCmd(
            new CreateSchema($dbParams['database'], $dbParams['schema'])
        );
        return $process->getExitCode() === 0;
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
