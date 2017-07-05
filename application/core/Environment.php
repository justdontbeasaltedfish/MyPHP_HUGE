<?php

/**
 * Class Environment
 * 类环境
 *
 * Extremely simple way to get the environment, everywhere inside your application.
 * Extend this the way you want.
 * 极其简单的方法环境,到处都在您的应用程序。
 * 扩展这个你所希望的方式。
 */
class Environment
{
    public static function get()
    {
        // if APPLICATION_ENV constant exists (set in Apache configs)
        // 如果APPLICATION_ENV常数存在(在Apache配置设置)
        // then return content of APPLICATION_ENV
        // 然后返回APPLICATION_ENV内容
        // else return "development"
        // 否则返回“development”
        return (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : "development");
    }
}
