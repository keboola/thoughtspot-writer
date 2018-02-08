<?php

namespace Keboola\ThoughtSpot;

use Keboola\Csv\CsvFile;
use Keboola\DbWriter\Configuration\ConfigDefinition;
use Keboola\DbWriter\Configuration\Validator;
use Keboola\DbWriter\Exception\ApplicationException;
use Keboola\DbWriter\Exception\UserException;
use Keboola\DbWriter\Logger;
use Monolog\Handler\NullHandler;
use Pimple\Container;

class Application
{
    protected $container;

    public function __construct($config, Logger $logger, $configDefinition = null)
    {
        if ($configDefinition == null) {
            $configDefinition = new ConfigDefinition();
        }

        $validate = Validator::getValidator($configDefinition);
        $parameters = $validate($config['parameters']);

        $this->container = new Container();
        $this->container['action'] = isset($config['action']) ? $config['action'] : 'run';
        $this->container['parameters'] = $parameters;
        $this->container['inputMapping'] = $config['storage']['input']['tables'];
        $this->container['logger'] = function ($container) use ($logger) {
            if ($container['action'] !== 'run') {
                $logger->setHandlers(array(new NullHandler(Logger::INFO)));
            }
            return $logger;
        };
        $this->container['writer'] = function ($container) {
            return new Writer($container['parameters']['db'], $container['logger']);
        };
    }

    public function run()
    {
        $actionMethod = $this->container['action'] . 'Action';
        if (!method_exists($this, $actionMethod)) {
            throw new UserException(sprintf("Action '%s' does not exist.", $this->container['action']));
        }

        return $this->$actionMethod();
    }

    public function runAction()
    {
        $uploaded = [];
        $tables = array_filter($this->container['parameters']['tables'], function ($table) {
            return ($table['export']);
        });

        foreach ($tables as $tableConfig) {
            if (empty($tableConfig['items'])) {
                continue;
            }

            $csv = $this->getInputCsv($tableConfig['tableId']);

            try {
                if ($tableConfig['incremental']) {
                    $this->loadIncremental($csv, $tableConfig);
                    $uploaded[] = $tableConfig['tableId'];
                    continue;
                }

                $this->loadFull($csv, $tableConfig);
                $uploaded[] = $tableConfig['tableId'];
            } catch (\PDOException $e) {
                throw new UserException($e->getMessage(), 0, $e, ["trace" => $e->getTraceAsString()]);
            } catch (UserException $e) {
                throw $e;
            } catch (\Exception $e) {
                throw new ApplicationException($e->getMessage(), 2, $e, ["trace" => $e->getTraceAsString()]);
            }
        }

        return [
            'status' => 'success',
            'uploaded' => $uploaded
        ];
    }

    public function loadIncremental(CsvFile $csv, $tableConfig)
    {
        /** @var Writer $writer */
        $writer = $this->container['writer'];

        // create destination table if not exists
        if (!$writer->tableExists($tableConfig['dbName'])) {
            $writer->create($tableConfig);
        }

        // upsert from staging to destination table
        $writer->write($csv, $tableConfig);
    }

    public function loadFull(CsvFile $csv, $tableConfig)
    {
        /** @var Writer $writer */
        $writer = $this->container['writer'];

        $writer->drop($tableConfig['dbName']);
        $writer->create($tableConfig);
        $writer->write($csv, $tableConfig);
    }

    protected function getInputCsv($tableId)
    {
        return new CsvFile($this->container['parameters']['data_dir'] . "/in/tables/" . $tableId . ".csv");
    }

    public function testConnectionAction()
    {
        try {
            /** @var Writer $writer */
            $writer = $this->container['writer'];
            $writer->testConnection();
        } catch (\Exception $e) {
            throw new UserException(sprintf("Connection failed: '%s'", $e->getMessage()), 0, $e);
        }

        return [
            'status' => 'success',
        ];
    }

    public function getTablesInfoAction()
    {
        $tables = $this->container['writer']->showTables($this['parameters']['db']['database']);

        $tablesInfo = [];
        foreach ($tables as $tableName) {
            $tablesInfo[$tableName] = $this['writer']->getTableInfo($tableName);
        }

        return [
            'status' => 'success',
            'tables' => $tablesInfo
        ];
    }
}