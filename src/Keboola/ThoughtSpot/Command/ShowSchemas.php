<?php

declare(strict_types=1);

namespace Keboola\ThoughtSpot\Command;

class ShowSchemas extends AbstractCommand
{
    public function __construct($databaseName)
    {
        $this->command = sprintf('echo \'SHOW SCHEMAS %s;\' | tql', $this->quote($databaseName));
    }
}
