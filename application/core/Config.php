<?php

class Config
{
    // this is public to allow better Unit Testing
    // 这是公开允许更好的单元测试
    public static $config;

    public static function get($key)
    {
        if (!self::$config) {

            $config_file = '../application/config/config.' . Environment::get() . '.php';

            if (!file_exists($config_file)) {
                return false;
            }

            self::$config = require $config_file;
        }

        return self::$config[$key];
    }
}
