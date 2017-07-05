<?php

/**
 * RegisterController
 * Register new user
 * 注册控制器
 * 注册新用户
 */
class RegisterController extends Controller
{
    /**
     * Construct this object by extending the basic Controller class. The parent::__construct thing is necessary to
     * put checkAuthentication in here to make an entire controller only usable for logged-in users (for sure not
     * needed in the RegisterController).
     * 构建这个对象通过扩展基本控制器类。
     * parent::__construct东西有必要把checkAuthentication在这里只让整个控制器用于登录用户(RegisterController确定不需要的)。
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Register page
     * Show the register form, but redirect to main-page if user is already logged-in
     * 注册页面
     * 显示注册表单,但重定向到主页,如果用户已经登录
     */
    public function index()
    {
        if (LoginModel::isUserLoggedIn()) {
            Redirect::home();
        } else {
            $this->View->render('register/index');
        }
    }

    /**
     * Register page action
     * POST-request after form submit
     * 注册页面动作
     * post请求表单提交后
     */
    public function register_action()
    {
        $registration_successful = RegistrationModel::registerNewUser();

        if ($registration_successful) {
            Redirect::to('login/index');
        } else {
            Redirect::to('register/index');
        }
    }

    /**
     * Verify user after activation mail link opened
     * 验证用户激活后邮件链接打开
     * @param int $user_id user's id 用户的id
     * @param string $user_activation_verification_code user's verification token 用户验证令牌
     */
    public function verify($user_id, $user_activation_verification_code)
    {
        if (isset($user_id) && isset($user_activation_verification_code)) {
            RegistrationModel::verifyNewUser($user_id, $user_activation_verification_code);
            $this->View->render('register/verify');
        } else {
            Redirect::to('login/index');
        }
    }

    /**
     * Generate a captcha, write the characters into $_SESSION['captcha'] and returns a real image which will be used
     * like this: <img src="......./login/showCaptcha" />
     * IMPORTANT: As this action is called via <img ...> AFTER the real application has finished executing (!), the
     * SESSION["captcha"] has no content when the application is loaded. The SESSION["captcha"] gets filled at the
     * moment the end-user requests the <img .. >
     * Maybe refactor this sometime.
     * 生成一个验证码,把人物写进$_SESSION['captcha']并返回一个实像,将使用这样的:<img src="......./login/showCaptcha" />
     * 重要:这一行为称为通过<img ...>在真实的应用程序中执行完成(!),SESSION["captcha"]加载应用程序时没有内容。
     * SESSION["captcha"]得到了目前终端用户请求<img .. >
     * 也许重构。
     */
    public function showCaptcha()
    {
        CaptchaModel::generateAndShowCaptcha();
    }
}
