<?php

/**
 * This is under development. Expect changes!
 * 这是正在开发。希望更改!
 * Class Request
 * 类请求
 * Abstracts the access to $_GET, $_POST and $_COOKIE, preventing direct access to these super-globals.
 * 摘要访问$_GET,$_POST和$_COOKIE,防止直接访问这些超全局变量。
 * This makes PHP code quality analyzer tools very happy.
 * 这使得PHP代码质量分析器工具非常高兴。
 * @see http://php.net/manual/en/reserved.variables.request.php
 */
class Request
{
    /**
     * Gets/returns the value of a specific key of the POST super-global.
     * When using just Request::post('x') it will return the raw and untouched $_POST['x'], when using it like
     * Request::post('x', true) then it will return a trimmed and stripped $_POST['x'] !
     * 得到/返回一个特定的价值关键的超全局变量。
     * 当仅使用Request::post('x'),它将返回原始的和没有$_POST['x'],
     * 当使用它就像Request::post('x', true),那么它将返回一个修剪和剥夺了$_POST['x']!
     *
     * @param mixed $key key 关键
     * @param bool $clean marker for optional cleaning of the var 标记为可选的var的清洁
     * @return mixed the key's value or nothing 键的值
     */
    public static function post($key, $clean = false)
    {
        if (isset($_POST[$key])) {
            // we use the Ternary Operator here which saves the if/else block
            // 我们在这里使用三元运算符可以节省if/else块
            // @see http://davidwalsh.name/php-shorthand-if-else-ternary-operators
            return ($clean) ? trim(strip_tags($_POST[$key])) : $_POST[$key];
        }
    }

    /**
     * Returns the state of a checkbox.
     * 返回一个复选框的状态。
     *
     * @param mixed $key key 关键
     * @return mixed state of the checkbox 复选框的状态
     */
    public static function postCheckbox($key)
    {
        return isset($_POST[$key]) ? 1 : NULL;
    }

    /**
     * gets/returns the value of a specific key of the GET super-global
     * 获得/返回一个特定的价值GET超全局的关键
     * @param mixed $key key 关键
     * @return mixed the key's value or nothing 键的值
     */
    public static function get($key)
    {
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }
    }

    /**
     * gets/returns the value of a specific key of the COOKIE super-global
     * 得到/返回值的特定COOKIE超全局的关键
     * @param mixed $key key 关键
     * @return mixed the key's value or nothing 键的值
     */
    public static function cookie($key)
    {
        if (isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
        }
    }
}
