<?php

declare(strict_types=1);

namespace Keboola\ThoughtSpot\Command;

class ShowSchemas extends AbstractCommand
{
    public function __construct($databaseName)
    {
        $this->command = $this->getTqlCommand(
            sprintf('SHOW SCHEMAS %s;', $this->quote($databaseName))
        );
    }
}
