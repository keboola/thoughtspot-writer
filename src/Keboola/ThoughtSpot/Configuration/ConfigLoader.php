<?php

namespace Keboola\ThoughtSpot\Configuration;

class ConfigLoader
{
    public static function load($path)
    {
        $config = json_decode(file_get_contents($path), true);
        $config['parameters']['data_dir'] = dirname($path);

        if (!isset($config['action'])) {
            $config['action'] = 'run';
        };

        return $config;
    }
}