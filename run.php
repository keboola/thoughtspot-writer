<?php

use Keboola\DbWriter\Exception\ApplicationException;
use Keboola\DbWriter\Exception\UserException;
use Keboola\DbWriter\Logger;
use Keboola\Thoughtspot\Application;
use Keboola\ThoughtSpot\Configuration\ConfigDefinition;
use Keboola\ThoughtSpot\Configuration\ConfigLoader;

define('APP_NAME', 'wr-thoughtspot');
define('ROOT_PATH', __DIR__);

require_once(dirname(__FILE__) . "/vendor/keboola/db-writer-common/bootstrap.php");

$logger = new Logger(APP_NAME);

$action = 'run';

try {
    $arguments = getopt('d::', ['data::']);
    if (!isset($arguments["data"])) {
        throw new UserException('Data folder not set.');
    }
    $config = ConfigLoader::load($arguments['data'] . '/config.json');
    $action = $config['action'];

    $app = new Application($config, $logger, new ConfigDefinition());

    echo json_encode($app->run());
} catch (UserException $e) {
    $logger->log('error', $e->getMessage(), (array) $e->getData());

    if ($action !== 'run') {
        echo $e->getMessage();
    }

    exit(1);
} catch (ApplicationException $e) {
    $logger->log('error', $e->getMessage(), (array) $e->getData());
    exit($e->getCode() > 1 ? $e->getCode(): 2);
} catch (\Exception $e) {
    $logger->log('error', $e->getMessage(), [
        'errFile' => $e->getFile(),
        'errLine' => $e->getLine(),
    ]);
    exit(2);
}
exit(0);
