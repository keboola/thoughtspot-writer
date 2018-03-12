<?php

namespace Keboola\ThoughtSpot\Command;

class DropTable extends AbstractCommand
{
    public function __construct($dbParams, $tableName)
    {
        $this->command = $this->getTqlCommand(
            sprintf("DROP TABLE %s;", $this->getFullTableName($dbParams, $tableName)
        ));
    }
}