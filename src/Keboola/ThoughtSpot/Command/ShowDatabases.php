<?php

declare(strict_types=1);

namespace Keboola\ThoughtSpot\Command;

class ShowDatabases extends AbstractCommand
{
    public function __construct()
    {
        $this->command = $this->getTqlCommand(
            sprintf("SHOW DATABASES;"));
    }
}
