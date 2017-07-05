<?php

class AdminController extends Controller
{
    /**
     * Construct this object by extending the basic Controller class
     * 构建这个对象通过扩展基本控制器类
     */
    public function __construct()
    {
        parent::__construct();

        // special authentication check for the entire controller: Note the check-ADMIN-authentication!
        // 特殊身份验证检查整个控制器:注意check-ADMIN-authentication !
        // All methods inside this controller are only accessible for admins (= users that have role type 7)
        // 所有方法在这个控制器只访问管理员(=用户,角色类型7)
        Auth::checkAdminAuthentication();
    }

    /**
     * This method controls what happens when you move to /admin or /admin/index in your app.
     * // 该方法控制当你移动/admin /admin/index在您的应用程序。
     */
    public function index()
    {
        $this->View->render('admin/index', array(
                'users' => UserModel::getPublicProfilesOfAllUsers())
        );
    }

    public function actionAccountSettings()
    {
        AdminModel::setAccountSuspensionAndDeletionStatus(
            Request::post('suspension'), Request::post('softDelete'), Request::post('user_id')
        );

        Redirect::to("admin");
    }
}
