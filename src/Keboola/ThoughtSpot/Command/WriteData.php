<?php

namespace Keboola\ThoughtSpot\Command;

class WriteData extends AbstractCommand
{
    public function __construct($dbParams, $dstFile, $table)
    {
        // load file to TS using tsload
        $tsloadCmd = 'tsload'
            . sprintf(' --target_database "%s"', $dbParams['database'])
            . sprintf(' --target_schema "%s"', $dbParams['schema'])
            . sprintf(' --target_table "%s"', $table['dbName'])
            . sprintf(' --source_file /tmp/%s', $dstFile)
            . ' --v 1 --field_separator "," --has_header_row';

        if ($table['incremental'] === false) {
            $tsloadCmd .= ' --empty_target';
        }

        $this->command = $tsloadCmd;
    }
}