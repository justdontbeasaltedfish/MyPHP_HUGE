<?php

/**
 * This is the "base controller class". All other "real" controllers extend this class.
 * Whenever a controller is created, we also
 * 1. initialize a session
 * 2. check if the user is not logged in anymore (session timeout) but has a cookie
 * 这是“基本控制器类”。其他所有“真实”控制器扩展这个类。
 * 每当创建一个控制器,我们也
 * 1.初始化一个会话
 * 2.检查用户是否不再登录(会话超时),但有一个cookie
 */
class Controller
{
    /** @var View View The view object 查看视图对象 */
    public $View;

    /**
     * Construct the (base) controller. This happens when a real controller is constructed, like in
     * the constructor of IndexController when it says: parent::__construct();
     * 构造(基地)控制器。当一个真正的构造控制器,像IndexController当它的构造函数中说:parent::__construct();
     */
    public function __construct()
    {
        // always initialize a session
        // 总是初始化一个会话
        Session::init();

        // check session concurrency
        // 检查会话并发
        Auth::checkSessionConcurrency();

        // user is not logged in but has remember-me-cookie ? then try to login with cookie ("remember me" feature)
        // 用户没有登录,但remember-me-cookie吗?然后尝试登录与饼干(“记住我”功能)
        if (!Session::userIsLoggedIn() AND Request::cookie('remember_me')) {
            header('location: ' . Config::get('URL') . 'login/loginWithCookie');
        }

        // create a view object to be able to use it inside a controller, like $this->View->render();
        // 创建一个视图对象能够使用它在一个控制器,如$this->View->render();
        $this->View = new View();
    }
}
