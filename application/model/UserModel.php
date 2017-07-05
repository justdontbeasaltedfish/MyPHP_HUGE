<?php

/**
 * UserModel
 * Handles all the PUBLIC profile stuff. This is not for getting data of the logged in user, it's more for handling
 * data of all the other users. Useful for display profile information, creating user lists etc.
 * 用户模型
 * 处理所有的公众形象的东西。这不是获取登录用户的数据,更多的是用于处理数据的所有其他用户。用于显示配置信息,创建用户列表等。
 */
class UserModel
{
    /**
     * Gets an array that contains all the users in the database. The array's keys are the user ids.
     * Each array element is an object, containing a specific user's data.
     * The avatar line is built using Ternary Operators, have a look here for more:
     * 得到一个数组,其中包含数据库中的所有用户。数组的键是用户id。
     * 每个数组元素是一个对象,包含一个特定用户的数据。
     * 《阿凡达》的线是使用三元运算符,看看这里更多:
     * @see http://davidwalsh.name/php-shorthand-if-else-ternary-operators
     *
     * @return array The profiles of all users 所有用户的配置文件
     */
    public static function getPublicProfilesOfAllUsers()
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT user_id, user_name, user_email, user_active, user_has_avatar, user_deleted FROM users";
        $query = $database->prepare($sql);
        $query->execute();

        $all_users_profiles = array();

        foreach ($query->fetchAll() as $user) {

            // all elements of array passed to Filter::XSSFilter for XSS sanitation, have a look into
            // application/core/Filter.php for more info on how to use. Removes (possibly bad) JavaScript etc from
            // the user's values
            // 所有元素的数组传递给Filter::XSSFilter XSS卫生,看看application/core/Filter.php
            // 如何使用的更多信息。删除(可能是坏的)JavaScript等从用户的价值
            array_walk_recursive($user, 'Filter::XSSFilter');

            $all_users_profiles[$user->user_id] = new stdClass();
            $all_users_profiles[$user->user_id]->user_id = $user->user_id;
            $all_users_profiles[$user->user_id]->user_name = $user->user_name;
            $all_users_profiles[$user->user_id]->user_email = $user->user_email;
            $all_users_profiles[$user->user_id]->user_active = $user->user_active;
            $all_users_profiles[$user->user_id]->user_deleted = $user->user_deleted;
            $all_users_profiles[$user->user_id]->user_avatar_link = (Config::get('USE_GRAVATAR') ? AvatarModel::getGravatarLinkByEmail($user->user_email) : AvatarModel::getPublicAvatarFilePathOfUser($user->user_has_avatar, $user->user_id));
        }

        return $all_users_profiles;
    }

    /**
     * Gets a user's profile data, according to the given $user_id
     * 得到用户的配置文件数据,根据给定的$user_id
     * @param int $user_id The user's id 用户的id
     * @return mixed The selected user's profile 所选用户的概要文件
     */
    public static function getPublicProfileOfUser($user_id)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT user_id, user_name, user_email, user_active, user_has_avatar, user_deleted
                FROM users WHERE user_id = :user_id LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(':user_id' => $user_id));

        $user = $query->fetch();

        if ($query->rowCount() == 1) {
            if (Config::get('USE_GRAVATAR')) {
                $user->user_avatar_link = AvatarModel::getGravatarLinkByEmail($user->user_email);
            } else {
                $user->user_avatar_link = AvatarModel::getPublicAvatarFilePathOfUser($user->user_has_avatar, $user->user_id);
            }
        } else {
            Session::add('feedback_negative', Text::get('FEEDBACK_USER_DOES_NOT_EXIST'));
        }

        // all elements of array passed to Filter::XSSFilter for XSS sanitation, have a look into
        // application/core/Filter.php for more info on how to use. Removes (possibly bad) JavaScript etc from
        // the user's values
        // 所有元素的数组传递给Filter::XSSFilter XSS卫生,看看application/core/Filter.php如何使用的更多信息。
        // 删除(可能是坏的)JavaScript等从用户的价值
        array_walk_recursive($user, 'Filter::XSSFilter');

        return $user;
    }

    /**
     * @param $user_name_or_email
     *
     * @return mixed
     */
    public static function getUserDataByUserNameOrEmail($user_name_or_email)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $query = $database->prepare("SELECT user_id, user_name, user_email FROM users
                                     WHERE (user_name = :user_name_or_email OR user_email = :user_name_or_email)
                                           AND user_provider_type = :provider_type LIMIT 1");
        $query->execute(array(':user_name_or_email' => $user_name_or_email, ':provider_type' => 'DEFAULT'));

        return $query->fetch();
    }

    /**
     * Checks if a username is already taken
     * 检查用户名是否已被使用
     *
     * @param $user_name string username 用户名
     *
     * @return bool
     */
    public static function doesUsernameAlreadyExist($user_name)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $query = $database->prepare("SELECT user_id FROM users WHERE user_name = :user_name LIMIT 1");
        $query->execute(array(':user_name' => $user_name));
        if ($query->rowCount() == 0) {
            return false;
        }
        return true;
    }

    /**
     * Checks if a email is already used
     * 检查是否已经使用电子邮件
     *
     * @param $user_email string email 电子邮件
     *
     * @return bool
     */
    public static function doesEmailAlreadyExist($user_email)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $query = $database->prepare("SELECT user_id FROM users WHERE user_email = :user_email LIMIT 1");
        $query->execute(array(':user_email' => $user_email));
        if ($query->rowCount() == 0) {
            return false;
        }
        return true;
    }

    /**
     * Writes new username to database
     * 写数据库的新用户名
     *
     * @param $user_id int user id 用户id
     * @param $new_user_name string new username 新用户名
     *
     * @return bool
     */
    public static function saveNewUserName($user_id, $new_user_name)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $query = $database->prepare("UPDATE users SET user_name = :user_name WHERE user_id = :user_id LIMIT 1");
        $query->execute(array(':user_name' => $new_user_name, ':user_id' => $user_id));
        if ($query->rowCount() == 1) {
            return true;
        }
        return false;
    }

    /**
     * Writes new email address to database
     * 写新邮件地址数据库
     *
     * @param $user_id int user id 用户id
     * @param $new_user_email string new email address 新电子邮件地址
     *
     * @return bool
     */
    public static function saveNewEmailAddress($user_id, $new_user_email)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $query = $database->prepare("UPDATE users SET user_email = :user_email WHERE user_id = :user_id LIMIT 1");
        $query->execute(array(':user_email' => $new_user_email, ':user_id' => $user_id));
        $count = $query->rowCount();
        if ($count == 1) {
            return true;
        }
        return false;
    }

    /**
     * Edit the user's name, provided in the editing form
     * 编辑用户的名称,编辑提供的形式
     *
     * @param $new_user_name string The new username 新用户名
     *
     * @return bool success status 成功地位
     */
    public static function editUserName($new_user_name)
    {
        // new username same as old one ?
        // 新用户名和旧的一样吗?
        if ($new_user_name == Session::get('user_name')) {
            Session::add('feedback_negative', Text::get('FEEDBACK_USERNAME_SAME_AS_OLD_ONE'));
            return false;
        }

        // username cannot be empty and must be azAZ09 and 2-64 characters
        // 用户名不能是空的,必须azAZ09和2 - 64个字符
        if (!preg_match("/^[a-zA-Z0-9]{2,64}$/", $new_user_name)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_USERNAME_DOES_NOT_FIT_PATTERN'));
            return false;
        }

        // clean the input, strip usernames longer than 64 chars (maybe fix this ?)
        // 干净的输入,带用户名超过64字符(也许解决这个问题?)
        $new_user_name = substr(strip_tags($new_user_name), 0, 64);

        // check if new username already exists
        // 检查新用户名是否已经存在
        if (self::doesUsernameAlreadyExist($new_user_name)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_USERNAME_ALREADY_TAKEN'));
            return false;
        }

        $status_of_action = self::saveNewUserName(Session::get('user_id'), $new_user_name);
        if ($status_of_action) {
            Session::set('user_name', $new_user_name);
            Session::add('feedback_positive', Text::get('FEEDBACK_USERNAME_CHANGE_SUCCESSFUL'));
            return true;
        } else {
            Session::add('feedback_negative', Text::get('FEEDBACK_UNKNOWN_ERROR'));
            return false;
        }
    }

    /**
     * Edit the user's email
     * 编辑用户的电子邮件
     *
     * @param $new_user_email
     *
     * @return bool success status 成功地位
     */
    public static function editUserEmail($new_user_email)
    {
        // email provided ?
        // 提供的电子邮件吗?
        if (empty($new_user_email)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_EMAIL_FIELD_EMPTY'));
            return false;
        }

        // check if new email is same like the old one
        // 检查新邮件是否像旧的一样
        if ($new_user_email == Session::get('user_email')) {
            Session::add('feedback_negative', Text::get('FEEDBACK_EMAIL_SAME_AS_OLD_ONE'));
            return false;
        }

        // user's email must be in valid email format, also checks the length
        // 用户的电子邮件必须是有效的电子邮件格式,长度也检查
        // @see http://stackoverflow.com/questions/21631366/php-filter-validate-email-max-length
        // @see http://stackoverflow.com/questions/386294/what-is-the-maximum-length-of-a-valid-email-address
        if (!filter_var($new_user_email, FILTER_VALIDATE_EMAIL)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_EMAIL_DOES_NOT_FIT_PATTERN'));
            return false;
        }

        // strip tags, just to be sure
        // 带标签,就可以肯定的
        $new_user_email = substr(strip_tags($new_user_email), 0, 254);

        // check if user's email already exists
        // 检查用户的电子邮件已经存在
        if (self::doesEmailAlreadyExist($new_user_email)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_USER_EMAIL_ALREADY_TAKEN'));
            return false;
        }

        // write to database, if successful ...
        // 写入数据库,如果成功……
        // ... then write new email to session, Gravatar too (as this relies to the user's email address)
        // …然后写新邮件会话,功能(这是用户的电子邮件地址)
        if (self::saveNewEmailAddress(Session::get('user_id'), $new_user_email)) {
            Session::set('user_email', $new_user_email);
            Session::set('user_gravatar_image_url', AvatarModel::getGravatarLinkByEmail($new_user_email));
            Session::add('feedback_positive', Text::get('FEEDBACK_EMAIL_CHANGE_SUCCESSFUL'));
            return true;
        }

        Session::add('feedback_negative', Text::get('FEEDBACK_UNKNOWN_ERROR'));
        return false;
    }

    /**
     * Gets the user's id
     * 得到用户的id
     *
     * @param $user_name
     *
     * @return mixed
     */
    public static function getUserIdByUsername($user_name)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT user_id FROM users WHERE user_name = :user_name AND user_provider_type = :provider_type LIMIT 1";
        $query = $database->prepare($sql);

        // DEFAULT is the marker for "normal" accounts (that have a password etc.)
        // 默认是“正常”的标记账户(有密码等等)。
        // There are other types of accounts that don't have passwords etc. (FACEBOOK)
        // 还有其他类型的账户,没有密码等等(FACEBOOK)。
        $query->execute(array(':user_name' => $user_name, ':provider_type' => 'DEFAULT'));

        // return one row (we only have one result or nothing)
        // 返回一行(我们只有一个结果或无)
        return $query->fetch()->user_id;
    }

    /**
     * Gets the user's data
     * 得到用户的数据
     *
     * @param $user_name string User's name 用户名
     *
     * @return mixed Returns false if user does not exist, returns object with user's data when user exists
     * 返回false,如果用户不存在,返回与用户的数据,当用户对象存在
     */
    public static function getUserDataByUsername($user_name)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT user_id, user_name, user_email, user_password_hash, user_active,user_deleted, user_suspension_timestamp, user_account_type,
                       user_failed_logins, user_last_failed_login
                  FROM users
                 WHERE (user_name = :user_name OR user_email = :user_name)
                       AND user_provider_type = :provider_type
                 LIMIT 1";
        $query = $database->prepare($sql);

        // DEFAULT is the marker for "normal" accounts (that have a password etc.)
        // 默认是“正常”的标记账户(有密码等等)。
        // There are other types of accounts that don't have passwords etc. (FACEBOOK)
        // 还有其他类型的账户,没有密码等等(FACEBOOK)。
        $query->execute(array(':user_name' => $user_name, ':provider_type' => 'DEFAULT'));

        // return one row (we only have one result or nothing)
        // 返回一行(我们只有一个结果或无)
        return $query->fetch();
    }

    /**
     * Gets the user's data by user's id and a token (used by login-via-cookie process)
     * 被用户id和用户的数据令牌(login-via-cookie使用过程)
     *
     * @param $user_id
     * @param $token
     *
     * @return mixed Returns false if user does not exist, returns object with user's data when user exists
     * 返回false,如果用户不存在,返回与用户的数据,当用户对象存在
     */
    public static function getUserDataByUserIdAndToken($user_id, $token)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        // get real token from database (and all other data)
        // 从数据库得到真正的令牌(和所有其他数据)
        $query = $database->prepare("SELECT user_id, user_name, user_email, user_password_hash, user_active,
                                          user_account_type,  user_has_avatar, user_failed_logins, user_last_failed_login
                                     FROM users
                                     WHERE user_id = :user_id
                                       AND user_remember_me_token = :user_remember_me_token
                                       AND user_remember_me_token IS NOT NULL
                                       AND user_provider_type = :provider_type LIMIT 1");
        $query->execute(array(':user_id' => $user_id, ':user_remember_me_token' => $token, ':provider_type' => 'DEFAULT'));

        // return one row (we only have one result or nothing)
        // 返回一行(我们只有一个结果或无)
        return $query->fetch();
    }
}
