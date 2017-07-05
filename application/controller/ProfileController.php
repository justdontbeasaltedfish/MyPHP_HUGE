<?php

class ProfileController extends Controller
{
    /**
     * Construct this object by extending the basic Controller class
     * 构建这个对象通过扩展基本控制器类
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * This method controls what happens when you move to /overview/index in your app.
     * Shows a list of all users.
     * 该方法控制当你移动在app /overview/index。显示所有用户的列表。
     */
    public function index()
    {
        $this->View->render('profile/index', array(
                'users' => UserModel::getPublicProfilesOfAllUsers())
        );
    }

    /**
     * This method controls what happens when you move to /overview/showProfile in your app.
     * Shows the (public) details of the selected user.
     * 该方法控制当你移动到/overview/showProfile你的应用。
     * 显示了(公共)所选用户的细节。
     * @param $user_id int id the the user id的用户
     */
    public function showProfile($user_id)
    {
        if (isset($user_id)) {
            $this->View->render('profile/showProfile', array(
                    'user' => UserModel::getPublicProfileOfUser($user_id))
            );
        } else {
            Redirect::home();
        }
    }
}
