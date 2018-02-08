<?php
/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 27/10/16
 * Time: 17:20
 */

namespace Keboola\DbWriter\Redshift\Tests;

use Keboola\DbWriter\Logger;
use Keboola\ThoughtSpot\Writer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class FunctionalTest extends TestCase
{
    protected $dataDir = ROOT_PATH . 'tests/data/functional';

    protected $tmpDataDir = '/tmp/wr-db-redshift/data';

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
        $this->prepareDataFiles($this->initConfig());
        $process = $this->runProcess();
        $this->assertEquals(0, $process->getExitCode(), $process->getOutput());
    }

    public function testTestConnection()
    {
        $config = $this->initConfig(function ($config) {
            $config['action'] = 'testConnection';
            return $config;
        });
        $this->prepareDataFiles($config);

        $process = $this->runProcess();
        $data = json_decode($process->getOutput(), true);

        $this->assertEquals(0, $process->getExitCode(), $process->getOutput());
        $this->assertArrayHasKey('status', $data);
        $this->assertEquals('success', $data['status']);
    }

    private function initConfig(callable $callback = null)
    {
        $srcConfigPath = $this->dataDir . '/config.json';
        $dstConfigPath = $this->tmpDataDir . '/config.json';
        $config = json_decode(file_get_contents($srcConfigPath), true);

        $config['parameters']['data_dir'] = $this->tmpDataDir;
        $config['parameters']['db']['user'] = getenv('DB_USER');
        $config['parameters']['db']['#password'] = getenv('DB_PASSWORD');
        $config['parameters']['db']['password'] = getenv('DB_PASSWORD');
        $config['parameters']['db']['host'] = getenv('DB_HOST');
        $config['parameters']['db']['port'] = getenv('DB_PORT');
        $config['parameters']['db']['database'] = getenv('DB_DATABASE');
        $config['parameters']['db']['ssh']['user'] = getenv('SSH_USER');
        $config['parameters']['db']['ssh']['password'] = getenv('SSH_PASSWORD');

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
            $writer->drop($table['dbName']);

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
