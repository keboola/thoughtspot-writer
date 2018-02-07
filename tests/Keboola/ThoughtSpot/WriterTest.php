<?php

namespace Keboola\ThoughtSpot;

use Keboola\DbWriter\Logger;
use Keboola\DbWriter\Test\BaseTest;

class WriterTest extends BaseTest
{
    /** @var Writer */
    private $writer;

    private $config;

    public function setUp()
    {
        $this->config = $this->initConfig();
    }

    public function testCreateConnection()
    {
        $writer = new Writer($this->config['parameters']['db'], new Logger('test'));
        $conn = $writer->getConnection();

        $this->assertNotNull($conn);
    }

    private function initConfig()
    {
        $config = [];
        $config['parameters']['db']['user'] = getenv('DB_USER');
        $config['parameters']['db']['#password'] = getenv('DB_PASSWORD');
        $config['parameters']['db']['password'] = getenv('DB_PASSWORD');
        $config['parameters']['db']['host'] = getenv('DB_HOST');
        $config['parameters']['db']['port'] = getenv('DB_PORT');
        $config['parameters']['db']['database'] = getenv('DB_DATABASE');

        return $config;
    }

    private function dropAllTables($config)
    {
        $tables = $config['parameters']['tables'];
        foreach ($tables as $table) {
            $this->writer->drop($table['dbName']);
        }
    }
}