<?php

/**
 * UserController
 * Controls everything that is user-related
 * 用户控件
 * 控制所有与用户相关的
 */
class UserController extends Controller
{
    /**
     * Construct this object by extending the basic Controller class.
     * 构建这个对象通过扩展基本控制器类。
     */
    public function __construct()
    {
        parent::__construct();

        // VERY IMPORTANT: All controllers/areas that should only be usable by logged-in users
        // need this line! Otherwise not-logged in users could do actions.
        // 非常重要:所有控制器/区域只能可用的登录用户需要这条线!否则没有登录的用户可以做动作。
        Auth::checkAuthentication();
    }

    /**
     * Show user's PRIVATE profile
     * 显示用户的私人资料
     */
    public function index()
    {
        $this->View->render('user/index', array(
            'user_name' => Session::get('user_name'),
            'user_email' => Session::get('user_email'),
            'user_gravatar_image_url' => Session::get('user_gravatar_image_url'),
            'user_avatar_file' => Session::get('user_avatar_file'),
            'user_account_type' => Session::get('user_account_type')
        ));
    }

    /**
     * Show edit-my-username page
     * 显示edit-my-username页
     */
    public function editUsername()
    {
        $this->View->render('user/editUsername');
    }

    /**
     * Edit user name (perform the real action after form has been submitted)
     * 编辑用户名(执行表单被提交后的实际行动)
     */
    public function editUsername_action()
    {
        // check if csrf token is valid
        // 检查是否csrf令牌是有效的
        if (!Csrf::isTokenValid()) {
            LoginModel::logout();
            Redirect::home();
            exit();
        }

        UserModel::editUserName(Request::post('user_name'));
        Redirect::to('user/editUsername');
    }

    /**
     * Show edit-my-user-email page
     * 显示edit-my-user-email页
     */
    public function editUserEmail()
    {
        $this->View->render('user/editUserEmail');
    }

    /**
     * Edit user email (perform the real action after form has been submitted)
     * 编辑用户电子邮件(执行表单被提交后的实际行动)
     */
    // make this POST
    // 使这篇文章
    public function editUserEmail_action()
    {
        UserModel::editUserEmail(Request::post('user_email'));
        Redirect::to('user/editUserEmail');
    }

    /**
     * Edit avatar
     * 编辑《阿凡达》
     */
    public function editAvatar()
    {
        $this->View->render('user/editAvatar', array(
            'avatar_file_path' => AvatarModel::getPublicUserAvatarFilePathByUserId(Session::get('user_id'))
        ));
    }

    /**
     * Perform the upload of the avatar
     * POST-request
     * 《阿凡达》的执行上传
     * post请求
     */
    public function uploadAvatar_action()
    {
        AvatarModel::createAvatar();
        Redirect::to('user/editAvatar');
    }

    /**
     * Delete the current user's avatar
     * 删除当前用户的《阿凡达》
     */
    public function deleteAvatar_action()
    {
        AvatarModel::deleteAvatar(Session::get("user_id"));
        Redirect::to('user/editAvatar');
    }

    /**
     * Show the change-account-type page
     * 显示change-account-type页面
     */
    public function changeUserRole()
    {
        $this->View->render('user/changeUserRole');
    }

    /**
     * Perform the account-type changing
     * POST-request
     * 执行帐户类型改变
     * post请求
     */
    public function changeUserRole_action()
    {
        if (Request::post('user_account_upgrade')) {
            // "2" is quick & dirty account type 2, something like "premium user" maybe. you got the idea :)
            // “2”是快速和肮脏的帐户类型2,类似“高级用户”也许吧。你有这个想法:)
            UserRoleModel::changeUserRole(2);
        }

        if (Request::post('user_account_downgrade')) {
            // "1" is quick & dirty account type 1, something like "basic user" maybe.
            // “1”是快速和肮脏的帐户类型1,类似“基本用户”也许吧。
            UserRoleModel::changeUserRole(1);
        }

        Redirect::to('user/changeUserRole');
    }

    /**
     * Password Change Page
     * 密码更改页面
     */
    public function changePassword()
    {
        $this->View->render('user/changePassword');
    }

    /**
     * Password Change Action
     * Submit form, if retured positive redirect to index, otherwise show the changePassword page again
     * 密码更改操作
     * 提交表单,如果收益正重定向到索引,否则显示changePassword页面了
     */
    public function changePassword_action()
    {
        $result = PasswordResetModel::changePassword(
            Session::get('user_name'), Request::post('user_password_current'),
            Request::post('user_password_new'), Request::post('user_password_repeat')
        );

        if ($result)
            Redirect::to('user/index');
        else
            Redirect::to('user/changePassword');
    }
}
