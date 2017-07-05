<?php

/**
 * Class Redirect
 * 类重定向
 *
 * Simple abstraction for redirecting the user to a certain page
 * 重定向用户的简单抽象一个特定的页面
 */
class Redirect
{
    /**
     * To the last visited page before user logged in (useful when people are on a certain page inside your application
     * and then want to log in (to edit or comment something for example) and don't to be redirected to the main page).
     * 上次访问页面在用户登录(有用当人们在一个特定的页面在您的应用程序,然后想登录(例如编辑或评论),不要被重定向到主页)。
     *
     * This is just a bulletproof version of Redirect::to(), redirecting to an ABSOLUTE URL path like
     * "http://www.mydomain.com/user/profile", useful as people had problems with the RELATIVE URL path generated
     * by Redirect::to() when using HUGE inside sub-folders.
     * 这只是一个防弹版的Redirect::to(),将绝对URL路径像"http://www.mydomain.com/user/profile",
     * 有用的人的问题所产生的相对URL路径Redirect::to()当使用巨大的子文件夹内。
     *
     * @param $path string
     */
    public static function toPreviousViewedPageAfterLogin($path)
    {
        header('location: http://' . $_SERVER['HTTP_HOST'] . '/' . $path);
    }

    /**
     * To the homepage
     * 的主页
     */
    public static function home()
    {
        header("location: " . Config::get('URL'));
    }

    /**
     * To the defined page, uses a relative path (like "user/profile")
     * 定义页面,使用相对路径(如"user/profile")
     *
     * Redirects to a RELATIVE path, like "user/profile" (which works very fine unless you are using HUGE inside tricky
     * sub-folder structures)
     * 重定向到一个相对路径,就像"user/profile"(这很好,除非你使用的是巨大的内部复杂的子文件夹结构)
     *
     * @see https://github.com/panique/huge/issues/770
     * @see https://github.com/panique/huge/issues/754
     *
     * @param $path string
     */
    public static function to($path)
    {
        header("location: " . Config::get('URL') . $path);
    }
}
