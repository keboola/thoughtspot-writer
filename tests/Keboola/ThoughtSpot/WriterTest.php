<?php

namespace Keboola\ThoughtSpot;

use Keboola\Csv\CsvFile;
use Keboola\DbWriter\Logger;
use PHPUnit\Framework\TestCase;

class WriterTest extends TestCase
{
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

    public function testCreate()
    {
        $writer = $this->getWriter($this->config['parameters']['db']);
        $writer->drop('country');

        /** @var Connection $conn */
        $conn = $writer->getConnection();

        $writer->create([
            'tableId' => 'country',
            'dbName' => 'country',
            'export' => true,
            'incremental' => true,
            'primaryKey' => ['id'],
            'items' => [
                [
                    'name' => 'id',
                    'dbName' => 'id',
                    'type' => 'int',
                    'size' => null,
                    'nullable' => null,
                    'default' => null
                ],
                [
                    'name' => 'name',
                    'dbName' => 'name',
                    'type' => 'varchar',
                    'size' => 255,
                    'nullable' => null,
                    'default' => null
                ]
            ]
        ]);

        $exists = $writer->tableExists('country');

        $this->assertTrue($exists);
    }

    public function testWrite()
    {
        $writer = $this->getWriter($this->config['parameters']['db']);
        /** @var Connection $conn */
        $conn = $writer->getConnection();

        $srcFilename = ROOT_PATH . '/tests/data/countries.csv';
        $csvFile = new CsvFile($srcFilename);

        $writer->write($csvFile, [
            'tableId' => 'country',
            'dbName' => 'country',
            'export' => true,
            'incremental' => false,
            'primaryKey' => ['id'],
        ]);

        $res = $conn->fetchAll("SELECT id, name FROM country");

        $this->assertEquals('slovakia', $res[198]['name']);
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
        $config['parameters']['db']['ssh']['remoteHost'] = getenv('SSH_HOST');
        $config['parameters']['db']['ssh']['user'] = getenv('SSH_USER');
        $config['parameters']['db']['ssh']['password'] = getenv('SSH_PASSWORD');

        return $config;
    }

    protected function getWriter($dbParams)
    {
        return new Writer($dbParams, new Logger(APP_NAME));
    }
}