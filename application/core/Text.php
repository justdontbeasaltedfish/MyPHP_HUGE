<?php

class Text
{
    private static $texts;

    public static function get($key, $data = null)
    {
        // if not $key
        // 如果不是$key
        if (!$key) {
            return null;
        }

        if ($data) {
            foreach ($data as $var => $value) {
                ${$var} = $value;
            }
        }

        // load config file (this is only done once per application lifecycle)
        // 加载配置文件(这是每个应用程序生命周期只做一次)
        if (!self::$texts) {
            self::$texts = require('../application/config/texts.php');
        }

        // check if array key exists
        // 检查如果数组键存在
        if (!array_key_exists($key, self::$texts)) {
            return null;
        }

        return self::$texts[$key];
    }
}
