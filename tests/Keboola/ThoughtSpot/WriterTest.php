<?php

namespace Keboola\ThoughtSpot;

use Keboola\Csv\CsvFile;
use Keboola\DbWriter\Logger;
use Keboola\DbWriter\Test\BaseTest;

class WriterTest extends BaseTest
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

    // @todo Can't test CREATE TABLE, because we cant DROP the table :/
//    public function testCreate()
//    {
//        $writer = $this->getWriter($this->config['parameters']['db']);
//        /** @var Connection $conn */
//        $conn = $writer->getConnection();
//
//        $writer->create([
//            'tableId' => 'country',
//            'dbName' => 'country',
//            'export' => true,
//            'incremental' => true,
//            'primaryKey' => ['id'],
//            'items' => [
//                [
//                    'name' => 'id',
//                    'dbName' => 'id',
//                    'type' => 'int',
//                    'size' => null,
//                    'nullable' => null,
//                    'default' => null
//                ],
//                [
//                    'name' => 'name',
//                    'dbName' => 'name',
//                    'type' => 'varchar',
//                    'size' => 255,
//                    'nullable' => null,
//                    'default' => null
//                ]
//            ]
//        ]);
//
//        $res = $conn->fetchAll("SELECT * FROM country");
//    }

    public function testWrite()
    {
        $writer = $this->getWriter($this->config['parameters']['db']);
        /** @var Connection $conn */
        $conn = $writer->getConnection();

        $csvFile = new CsvFile(ROOT_PATH . '/tests/data/countries.csv');

        $writer->write($csvFile, ['dbName' => 'country']);

        $res = $conn->fetchAll("SELECT id, name FROM country");

        var_dump($res);
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

    protected function getWriter($dbParams)
    {
        return new Writer($dbParams, new Logger(APP_NAME));
    }
}