<?php

declare(strict_types=1);

namespace Keboola\ThoughtSpot\Command;

class CreateSchema extends AbstractCommand
{
    public function __construct(string $databaseName, string $schemaName)
    {
        $this->command = $this->getTqlCommand(
            sprintf('CREATE SCHEMA "%s"."%s";', $databaseName, $schemaName));
    }
}
