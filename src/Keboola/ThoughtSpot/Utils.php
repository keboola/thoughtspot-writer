<?php

namespace Keboola\ThoughtSpot;

trait Utils
{
    public function getDefaultSchema()
    {
        return 'falcon_default_schema';
    }

    public function quote($value)
    {
        return sprintf('"%s"', $value);
    }

    public function getTableNameWithSchema($dbParams, $tableName) {
        $schema = empty($dbParams['schema']) ? $this->getDefaultSchema() : $dbParams['schema'];
        return $schema . '.' . $tableName;
    }

    public function getFullTableName($dbParams, $tableName) {
        return $dbParams['database'] . '.' . $this->getTableNameWithSchema($dbParams, $tableName);
    }
}