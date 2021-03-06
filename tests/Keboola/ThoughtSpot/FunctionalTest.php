<?php
/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 27/10/16
 * Time: 17:20
 */

namespace Keboola\ThoughtSpot;

use Keboola\Csv\CsvFile;
use Keboola\DbWriter\Logger;
use Keboola\ThoughtSpot\Command\DropTable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class FunctionalTest extends TestCase
{
    protected $dataDir = ROOT_PATH . 'tests/data/functional';

    protected $tmpDataDir = '/tmp/wr-thoughtspot/data';

    private $defaultSchema = 'falcon_default_schema';

    public function setUp()
    {
        $fs = new Filesystem();
        if (file_exists($this->tmpDataDir)) {
            $fs->remove($this->tmpDataDir);
        }
        $fs->mkdir($this->tmpDataDir . '/in/tables');
    }

    public function testRun()
    {
        $config = $this->initConfig();
        $this->prepareDataFiles($config);
        $writer = $this->getWriter($config['parameters']['db']);

        $process = $this->runProcess();

        static::assertEquals(0, $process->getExitCode(), $process->getOutput());

        /** @var Connection $conn */
        $conn = $writer->getConnection();
        $res = $conn->fetchAll("SELECT id, name, glasses FROM simple");

        $resFilename = tempnam($this->tmpDataDir, 'simple-');
        $resCsv = new CsvFile($resFilename);
        $resCsv->writeRow(['id', 'name', 'glasses']);
        foreach ($res as $row) {
            $resCsv->writeRow($row);
        }

        static::assertFileEquals($this->dataDir . '/simple_expected.csv', $resFilename);
    }

    public function testTestConnection()
    {
        $config = $this->initConfig(function ($config) {
            $config['action'] = 'testConnection';
            unset($config['storage']);
            return $config;
        });
        $this->prepareDataFiles($config);

        $process = $this->runProcess();
        $data = json_decode($process->getOutput(), true);

        static::assertEquals(0, $process->getExitCode(), $process->getOutput());
        static::assertArrayHasKey('status', $data);
        static::assertEquals('success', $data['status']);
    }

    private function initConfig(callable $callback = null)
    {
        $srcConfigPath = $this->dataDir . '/config.json';
        $dstConfigPath = $this->tmpDataDir . '/config.json';
        $config = json_decode(file_get_contents($srcConfigPath), true);
        $schema = getenv('DB_SCHEMA');
        if (empty($schema)) {
            $schema = $this->defaultSchema;
        }
        $config['parameters']['data_dir'] = $this->tmpDataDir;
        $config['parameters']['db']['user'] = getenv('DB_USER');
        $config['parameters']['db']['#password'] = getenv('DB_PASSWORD');
        $config['parameters']['db']['host'] = getenv('DB_HOST');
        $config['parameters']['db']['port'] = getenv('DB_PORT');
        $config['parameters']['db']['database'] = getenv('DB_DATABASE');
        $config['parameters']['db']['schema'] = $schema;
        $config['parameters']['db']['sshUser'] = getenv('SSH_USER');
        $config['parameters']['db']['#sshPassword'] = getenv('SSH_PASSWORD');

        if ($callback !== null) {
            $config = $callback($config);
        }

        @unlink($dstConfigPath);
        file_put_contents($dstConfigPath, json_encode($config));

        return $config;
    }

    private function prepareDataFiles($config)
    {
        $writer = $this->getWriter($config['parameters']['db']);

        $fs = new Filesystem();
        foreach ($config['parameters']['tables'] as $table) {
            // clean destination DB
            $writer->execute([
                new DropTable($config['parameters']['db'], $table['dbName'])
            ]);

            $srcPath = $this->dataDir . '/in/tables/' . $table['tableId'] . '.csv';
            $dstPath = $this->tmpDataDir . '/in/tables/' . $table['tableId'] . '.csv';
            $fs->copy($srcPath, $dstPath);
        }
    }

    protected function getWriter($dbParams)
    {
        return new Writer($dbParams, new Logger(APP_NAME));
    }

    protected function runProcess()
    {
        $process = new Process('php ' . ROOT_PATH . 'run.php --data=' . $this->tmpDataDir . ' 2>&1');
        $process->setTimeout(300);
        $process->run();

        return $process;
    }
}
