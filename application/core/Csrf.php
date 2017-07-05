<?php

/**
 * Cross Site Request Forgery Class
 * 跨站请求伪造类
 */

/**
 * Instructions:
 * 产品说明:
 *
 * At your form, before the submit button put:
 * <input type="hidden" name="csrf_token" value="<?= Csrf::makeToken(); ?>" />
 * 在表单,提交按钮前说:
 * <input type="hidden" name="csrf_token" value="<?= Csrf::makeToken(); ?>" />
 *
 * This validation needed in the controller action method to validate CSRF token submitted with the form:
 * 这个验证所需的控制器操作方法来验证CSRF牌提交表单:
 *
 * if (!Csrf::isTokenValid()) {
 *     LoginModel::logout();
 *     Redirect::home();
 *     exit();
 * }
 *
 * To get simpler code it might be better to put the logout, redirect, exit into an own (static) method.
 * 得到更简单的代码可能是更好的把注销,重定向,出口成自己的(静态)方法。
 */
class Csrf
{
    /**
     * get CSRF token and generate a new one if expired
     * 得到CSRF令牌并生成一个新的如果过期了
     *
     * @access public 公共
     * @static static method 静态方法
     * @return string
     */
    public static function makeToken()
    {
        // token is valid for 1 day
        // 令牌有效期为1天
        $max_time = 60 * 60 * 24;
        $stored_time = Session::get('csrf_token_time');
        $csrf_token = Session::get('csrf_token');

        if ($max_time + $stored_time <= time() || empty($csrf_token)) {
            Session::set('csrf_token', md5(uniqid(rand(), true)));
            Session::set('csrf_token_time', time());
        }

        return Session::get('csrf_token');
    }

    /**
     * checks if CSRF token in session is same as in the form submitted
     * 检查是否CSRF会话令牌是一样的形式提交
     *
     * @access public 公共
     * @static static method 静态方法
     * @return bool
     */
    public static function isTokenValid()
    {
        $token = Request::post('csrf_token');
        return $token === Session::get('csrf_token') && !empty($token);
    }
}
