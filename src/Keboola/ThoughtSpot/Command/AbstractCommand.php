<?php

namespace Keboola\ThoughtSpot\Command;

use Keboola\ThoughtSpot\Utils;

class AbstractCommand implements CommandInterface
{
    use Utils;

    protected $command;

    public function getTqlCommand($tql)
    {
        return sprintf('echo \'%s\' | tql', addslashes($tql));
    }

    public function __toString()
    {
        return $this->command;
    }
}