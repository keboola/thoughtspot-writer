<?php

namespace Keboola\ThoughtSpot;

use Keboola\Csv\CsvFile;
use Keboola\DbWriter\Exception\UserException;
use Keboola\DbWriter\Logger;
use Keboola\ThoughtSpot\Command\CreateDatabase;
use Keboola\ThoughtSpot\Command\CreateSchema;
use Keboola\ThoughtSpot\Command\CreateTable;
use Keboola\ThoughtSpot\Command\DropTable;
use Keboola\ThoughtSpot\Command\ShowSchemas;
use Keboola\ThoughtSpot\Command\WriteData;
use PHPUnit\Framework\TestCase;

class WriterTest extends TestCase
{
    private $config;

    private $defaultSchema = 'falcon_default_schema';

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

    /**
     * @dataProvider getCommands
     */
    public function testTqlCommand($command, $expectCommand): void
    {
        $this->assertEquals($expectCommand, (string) $command);
    }

    public function testCreateDatabaseAndSchema(): void
    {
        $dbConfig = $this->config['parameters']['db'];
        $dbConfig['database'] = 'nonexist-DatabaseName';
        $dbConfig['schema'] = 'nonexist-SchemaName';

        $writer = new Writer($dbConfig, new Logger('test'));

        $this->assertTrue($writer->checkDatabaseExists($dbConfig));
        $this->assertTrue($writer->checkSchemaExists($dbConfig));
    }

    public function testCreate()
    {
        $dbParams = $this->config['parameters']['db'];
        $writer = $this->getWriter($dbParams);

        $batch = [
            new DropTable($dbParams, 'country'),
            new CreateTable($dbParams, [
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
                        'name' => 'value',
                        'dbName' => 'value',
                        'type' => 'varchar',
                        'size' => 255,
                        'nullable' => null,
                        'default' => null
                    ]
                ]
            ])
        ];
        $writer->execute($batch);

        $exists = $writer->tableExists('country');

        static::assertTrue($exists);
    }

    public function testWrite()
    {
        $dbParams = $this->config['parameters']['db'];
        $writer = $this->getWriter($dbParams);
        /** @var Connection $conn */
        $conn = $writer->getConnection();

        $srcFilename = ROOT_PATH . '/tests/data/countries.csv';
        $csvFile = new CsvFile($srcFilename);
        $table = [
            'tableId' => 'country',
            'dbName' => 'country',
            'export' => true,
            'incremental' => false,
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
                    'name' => 'value',
                    'dbName' => 'value',
                    'type' => 'varchar',
                    'size' => 255,
                    'nullable' => null,
                    'default' => null
                ]
            ]
        ];

        $dstFile = $writer->uploadFile($csvFile);
        $writer->execute([
            new DropTable($dbParams, $table['dbName']),
            new CreateTable($dbParams, $table),
            new WriteData($dbParams, $dstFile, $table)
        ]);

        $res = $conn->fetchAll('SELECT "id", "value" FROM country');

        static::assertEquals('slovakia', $res[198]['value']);
    }

    public function testWriteNullValues()
    {
        $dbParams = $this->config['parameters']['db'];
        $writer = $this->getWriter($this->config['parameters']['db']);
        /** @var Connection $conn */
        $conn = $writer->getConnection();

        $srcFilename = ROOT_PATH . '/tests/data/countries_null.csv';
        $csvFile = new CsvFile($srcFilename);

        $table = [
            'tableId' => 'country',
            'dbName' => 'country',
            'export' => true,
            'incremental' => false,
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
                    'name' => 'value',
                    'dbName' => 'value',
                    'type' => 'varchar',
                    'size' => 255,
                    'nullable' => null,
                    'default' => null
                ]
            ]
        ];

        $dstFile = $writer->uploadFile($csvFile);
        $writer->execute([
            new DropTable($dbParams, $table['dbName']),
            new CreateTable($dbParams, $table),
            new WriteData($dbParams, $dstFile, $table)
        ]);

        $res = $conn->fetchAll('SELECT "id", "value" FROM country');

        static::assertEmpty($res[201]['value']);
    }

    private function initConfig()
    {
        $schema = getenv('DB_SCHEMA');
        if (empty($schema)) {
            $schema = $this->defaultSchema;
        }
        $config = [];
        $config['parameters']['db']['user'] = getenv('DB_USER');
        $config['parameters']['db']['#password'] = getenv('DB_PASSWORD');
        $config['parameters']['db']['host'] = getenv('DB_HOST');
        $config['parameters']['db']['port'] = getenv('DB_PORT');
        $config['parameters']['db']['database'] = getenv('DB_DATABASE');
        $config['parameters']['db']['schema'] = $schema;
        $config['parameters']['db']['sshUser'] = getenv('SSH_USER');
        $config['parameters']['db']['#sshPassword'] = getenv('SSH_PASSWORD');

        return $config;
    }

    protected function getWriter($dbParams)
    {
        return new Writer($dbParams, new Logger(APP_NAME));
    }

    public function getCommands()
    {
        return [
            [
                new CreateDatabase('TEST'),
                'echo \'CREATE DATABASE \"TEST\";\' | tql'
            ],
            [
                new CreateSchema('TEST', 'TEST'),
                'echo \'CREATE SCHEMA \"TEST\".\"TEST\";\' | tql'
            ],
            [
                new ShowSchemas('TEST-TEST'),
                'echo \'SHOW SCHEMAS \"TEST-TEST\";\' | tql'
            ],
            [
                new DropTable(['database' => 'TEST', 'schema' => 'TEST-TEST'], 'table'),
                'echo \'DROP TABLE \"TEST\".\"TEST-TEST\".\"table\";\' | tql'
            ],
            [
                new WriteData(
                    ['database' => 'TEST-DTB', 'schema' => 'TEST-SCHEMA'],
                    'testFile',
                    ['dbName' => 'TEST-TABLE', 'incremental' => true]
                ),
                'tsload --target_database "TEST-DTB" --target_schema "TEST-SCHEMA" --target_table "TEST-TABLE"'.
                ' --source_file /tmp/testFile --v 1 --field_separator "," --has_header_row'
            ]
        ];
    }
}