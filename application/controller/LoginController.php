<?php

/**
 * LoginController
 * Controls everything that is authentication-related
 * 登录控制器
 * 控制所有与身份
 */
class LoginController extends Controller
{
    /**
     * Construct this object by extending the basic Controller class. The parent::__construct thing is necessary to
     * put checkAuthentication in here to make an entire controller only usable for logged-in users (for sure not
     * needed in the LoginController).
     * 构建这个对象通过扩展基本控制器类。
     * parent::__construct东西有必要把checkAuthentication在这里只让整个控制器用于登录用户(LoginController确定不需要的)。
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Index, default action (shows the login form), when you do login/index
     * 指数,违约行为(显示登录表单),当你login/index
     */
    public function index()
    {
        // if user is logged in redirect to main-page, if not show the view
        // 如果用户已登录重定向到主页,如果没有显示视图
        if (LoginModel::isUserLoggedIn()) {
            Redirect::home();
        } else {
            $data = array('redirect' => Request::get('redirect') ? Request::get('redirect') : NULL);
            $this->View->render('login/index', $data);
        }
    }

    /**
     * The login action, when you do login/login
     * 登录操作,当你login/login
     */
    public function login()
    {
        // check if csrf token is valid
        // 检查是否csrf令牌是有效的
        if (!Csrf::isTokenValid()) {
            LoginModel::logout();
            Redirect::home();
            exit();
        }

        // perform the login method, put result (true or false) into $login_successful
        // 执行登录方法,结果(真或假)放入$login_successful
        $login_successful = LoginModel::login(
            Request::post('user_name'), Request::post('user_password'), Request::post('set_remember_me_cookie')
        );

        // check login status: if true, then redirect user to user/index, if false, then to login form again
        // 检查登录状态:如果这是真的,然后将用户重定向到user/index,如果错误,那么再次登录表单
        if ($login_successful) {
            if (Request::post('redirect')) {
                Redirect::toPreviousViewedPageAfterLogin(ltrim(urldecode(Request::post('redirect')), '/'));
            } else {
                Redirect::to('user/index');
            }
        } else {
            if (Request::post('redirect')) {
                Redirect::to('login?redirect=' . ltrim(urlencode(Request::post('redirect')), '/'));
            } else {
                Redirect::to('login/index');
            }
        }
    }

    /**
     * The logout action
     * Perform logout, redirect user to main-page
     * 注销操作
     * 执行注销,将用户重定向到主页
     */
    public function logout()
    {
        LoginModel::logout();
        Redirect::home();
        exit();
    }

    /**
     * Login with cookie
     * 登录和饼干
     */
    public function loginWithCookie()
    {
        // run the loginWithCookie() method in the login-model, put the result in $login_successful (true or false)
        // 运行loginWithCookie() login-model()方法,将导致$login_successful(真或假)
        $login_successful = LoginModel::loginWithCookie(Request::cookie('remember_me'));

        // if login successful, redirect to dashboard/index ...
        // 如果登录成功,重定向到dashboard/index……
        if ($login_successful) {
            Redirect::to('dashboard/index');
        } else {
            // if not, delete cookie (outdated? attack?) and route user to login form to prevent infinite login loops
            // 如果没有,删除cookie(过时?攻击?)和路由用户登录登录表单,以防止无限循环
            LoginModel::deleteCookie();
            Redirect::to('login/index');
        }
    }

    /**
     * Show the request-password-reset page
     * 显示request-password-reset页面
     */
    public function requestPasswordReset()
    {
        $this->View->render('login/requestPasswordReset');
    }

    /**
     * The request-password-reset action
     * POST-request after form submit
     * request-password-reset行动
     * post请求表单提交后
     */
    public function requestPasswordReset_action()
    {
        PasswordResetModel::requestPasswordReset(Request::post('user_name_or_email'), Request::post('captcha'));
        Redirect::to('login/index');
    }

    /**
     * Verify the verification token of that user (to show the user the password editing view or not)
     * 验证该用户的验证令牌(显示用户密码编辑视图或不是)
     * @param string $user_name username 用户名
     * @param string $verification_code password reset verification token 密码重置验证令牌
     */
    public function verifyPasswordReset($user_name, $verification_code)
    {
        // check if this the provided verification code fits the user's verification code
        // 检查如果这所提供的验证码适合用户的验证码
        if (PasswordResetModel::verifyPasswordReset($user_name, $verification_code)) {
            // pass URL-provided variable to view to display them
            // URL-provided变量传递给视图来显示它们
            $this->View->render('login/resetPassword', array(
                'user_name' => $user_name,
                'user_password_reset_hash' => $verification_code
            ));
        } else {
            Redirect::to('login/index');
        }
    }

    /**
     * Set the new password
     * Please note that this happens while the user is not logged in. The user identifies via the data provided by the
     * password reset link from the email, automatically filled into the <form> fields. See verifyPasswordReset()
     * for more. Then (regardless of result) route user to index page (user will get success/error via feedback message)
     * POST request !
     * 设置新密码
     * 请注意,这个用户没有登录时发生。
     * 用户通过提供的数据确定密码重置链接的电子邮件,自动填充到<form>字段。
     * 看到verifyPasswordReset()。然后(不管结果)用户路由到索引页(用户将通过反馈得到成功/错误消息)POST请求!
     * TODO this is an _action 这是一个_action
     */
    public function setNewPassword()
    {
        PasswordResetModel::setNewPassword(
            Request::post('user_name'), Request::post('user_password_reset_hash'),
            Request::post('user_password_new'), Request::post('user_password_repeat')
        );
        Redirect::to('login/index');
    }
}
