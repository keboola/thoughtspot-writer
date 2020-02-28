<?php

declare(strict_types=1);

namespace Keboola\ThoughtSpot\Command;

class CreateDatabase extends AbstractCommand
{
    public function __construct(string $databaseName)
    {
        $this->command = $this->getTqlCommand(
            sprintf('CREATE DATABASE %s;', $this->quote($databaseName)));
    }
}
