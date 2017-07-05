<?php

/**
 * Class Auth
 * Checks if user is logged in, if not then sends the user to "yourdomain.com/login".
 * Auth::checkAuthentication() can be used in the constructor of a controller (to make the
 * entire controller only visible for logged-in users) or inside a controller-method to make only this part of the
 * application available for logged-in users.
 * 类身份验证
 * 检查用户是否登录,如果没有发送用户"yourdomain.com/login"。
 * Auth::checkAuthentication()的构造函数中可以使用一个控制器(让整个控制器仅对登录用户可见)
 * 或在一个控制器方法只有这对登录的用户可用应用程序的一部分。
 */
class Auth
{
    /**
     * The normal authentication flow, just check if the user is logged in (by looking into the session).
     * If user is not, then he will be redirected to login page and the application is hard-stopped via exit().
     * 正常的认证流程,只是检查用户是否登录(通过调查会话)。
     * 如果用户没有,那么他将被重定向到登录页面和应用程序通过exit()hard-stopped。
     */
    public static function checkAuthentication()
    {
        // initialize the session (if not initialized yet)
        // 初始化会话(如果没有初始化)
        Session::init();

        // self::checkSessionConcurrency();

        // if user is NOT logged in...
        // 如果用户没有登录…
        // (if user IS logged in the application will not run the code below and therefore just go on)
        // (如果用户登录应用程序不会运行下面的代码,因此只是继续)
        if (!Session::userIsLoggedIn()) {

            // ... then treat user as "not logged in", destroy session, redirect to login page
            // …然后把用户当作“没有登录”,破坏会话,重定向到登录页面
            Session::destroy();

            // send the user to the login form page, but also add the current page's URI (the part after the base URL)
            // as a parameter argument, making it possible to send the user back to where he/she came from after a
            // successful login
            // 发送用户登录表单页面,但也添加当前页面的URI(后一部分基础URL)作为一个参数的参数,使其能够发送用户回到他/她来自成功后登录
            header('location: ' . Config::get('URL') . 'login?redirect=' . urlencode($_SERVER['REQUEST_URI']));

            // to prevent fetching views via cURL (which "ignores" the header-redirect above) we leave the application
            // 防止获取视图通过旋度(“忽略”header-redirect上图)我们离开应用程序
            // the hard way, via exit(). 困难的方式,通过exit()。 @see https://github.com/panique/php-login/issues/453
            // this is not optimal and will be fixed in future releases
            // 这不是最优的,并将固定在将来的版本中
            exit();
        }
    }

    /**
     * The admin authentication flow, just check if the user is logged in (by looking into the session) AND has
     * user role type 7 (currently there's only type 1 (normal user), type 2 (premium user) and 7 (admin)).
     * If user is not, then he will be redirected to login page and the application is hard-stopped via exit().
     * Using this method makes only sense in controllers that should only be used by admins.
     * 管理身份验证流,只是检查用户是否登录(通过调查会话)和用户角色类型7(目前只有1型(普通用户)、2型(高级用户)和7(管理))。
     * 如果用户没有,那么他将被重定向到登录页面和应用程序通过hard-stoppedexit()。
     * 使用这种方法只有意义的控制器只能由管理员使用。
     */
    public static function checkAdminAuthentication()
    {
        // initialize the session (if not initialized yet)
        // 初始化会话(如果没有初始化)
        Session::init();

        // self::checkSessionConcurrency();

        // if user is not logged in or is not an admin (= not role type 7)
        // 如果用户没有登录或者不是一个管理员(=不是角色类型7)
        if (!Session::userIsLoggedIn() || Session::get("user_account_type") != 7) {

            // ... then treat user as "not logged in", destroy session, redirect to login page
            // …然后把用户当作“没有登录”,破坏会话,重定向到登录页面
            Session::destroy();
            header('location: ' . Config::get('URL') . 'login');

            // to prevent fetching views via cURL (which "ignores" the header-redirect above) we leave the application
            // the hard way, via exit().
            // 防止获取视图通过旋度(“忽略”header-redirect上图)我们离开应用程序困难的方式,通过exit()。
            // @see https://github.com/panique/php-login/issues/453
            // this is not optimal and will be fixed in future releases
            // 这不是最优的,并将固定在将来的版本中
            exit();
        }
    }

    /**
     * Detects if there is concurrent session (i.e. another user logged in with the same current user credentials),
     * If so, then logout.
     * 检测是否存在并发会话(即相同的另一个用户登录当前用户凭证),如果是这样,然后注销。
     */
    public static function checkSessionConcurrency()
    {
        if (Session::userIsLoggedIn()) {
            if (Session::isConcurrentSessionExists()) {
                LoginModel::logout();
                Redirect::home();
                exit();
            }
        }
    }
}
