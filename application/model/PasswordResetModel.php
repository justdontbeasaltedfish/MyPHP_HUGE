<?php

/**
 * Class PasswordResetModel
 * 类密码重置模型
 *
 * Handles all the stuff that is related to the password-reset process
 * 处理所有的东西是相关的密码重置的过程
 */
class PasswordResetModel
{
    /**
     * Perform the necessary actions to send a password reset mail
     * 执行必要的操作发送密码重置邮件
     *
     * @param $user_name_or_email string Username or user's email 用户名或用户的电子邮件
     * @param $captcha string Captcha string 验证码字符串
     *
     * @return bool success status 成功地位
     */
    public static function requestPasswordReset($user_name_or_email, $captcha)
    {
        if (!CaptchaModel::checkCaptcha($captcha)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_CAPTCHA_WRONG'));
            return false;
        }

        if (empty($user_name_or_email)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_USERNAME_EMAIL_FIELD_EMPTY'));
            return false;
        }

        // check if that username exists
        // 检查,如果用户名存在
        $result = UserModel::getUserDataByUserNameOrEmail($user_name_or_email);
        if (!$result) {
            Session::add('feedback_negative', Text::get('FEEDBACK_USER_DOES_NOT_EXIST'));
            return false;
        }

        // generate integer-timestamp (to see when exactly the user (or an attacker) requested the password reset mail)
        // 生成integer-timestamp(看到何时用户(或攻击者)请求密码重置邮件)
        // generate random hash for email password reset verification (40 char string)
        // 生成随机散列密码重置邮件验证(40字符字符串)
        $temporary_timestamp = time();
        $user_password_reset_hash = sha1(uniqid(mt_rand(), true));

        // set token (= a random hash string and a timestamp) into database ...
        // 设置令牌(=随机哈希字符串和一个时间戳)到数据库……
        $token_set = self::setPasswordResetDatabaseToken($result->user_name, $user_password_reset_hash, $temporary_timestamp);
        if (!$token_set) {
            return false;
        }

        // ... and send a mail to the user, containing a link with username and token hash string
        // …和发送邮件给用户,与用户名和令牌包含一个链接哈希字符串
        $mail_sent = self::sendPasswordResetMail($result->user_name, $user_password_reset_hash, $result->user_email);
        if ($mail_sent) {
            return true;
        }

        // default return
        return false;
    }

    /**
     * Set password reset token in database (for DEFAULT user accounts)
     * 在数据库设置密码重置令牌(默认用户帐户)
     *
     * @param string $user_name username
     * @param string $user_password_reset_hash password reset hash 密码重置散列
     * @param int $temporary_timestamp timestamp 时间戳
     *
     * @return bool success status 成功地位
     */
    public static function setPasswordResetDatabaseToken($user_name, $user_password_reset_hash, $temporary_timestamp)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "UPDATE users
                SET user_password_reset_hash = :user_password_reset_hash, user_password_reset_timestamp = :user_password_reset_timestamp
                WHERE user_name = :user_name AND user_provider_type = :provider_type LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(
            ':user_password_reset_hash' => $user_password_reset_hash, ':user_name' => $user_name,
            ':user_password_reset_timestamp' => $temporary_timestamp, ':provider_type' => 'DEFAULT'
        ));

        // check if exactly one row was successfully changed
        // 检查是否完全成功改变一行
        if ($query->rowCount() == 1) {
            return true;
        }

        // fallback
        // 回退
        Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_RESET_TOKEN_FAIL'));
        return false;
    }

    /**
     * Send the password reset mail
     * 发送密码重置邮件
     *
     * @param string $user_name username 用户名
     * @param string $user_password_reset_hash password reset hash 密码重置散列
     * @param string $user_email user email 用户的电子邮件
     *
     * @return bool success status 成功地位
     */
    public static function sendPasswordResetMail($user_name, $user_password_reset_hash, $user_email)
    {
        // create email body
        // 创建邮件正文
        $body = Config::get('EMAIL_PASSWORD_RESET_CONTENT') . ' ' . Config::get('URL') .
                Config::get('EMAIL_PASSWORD_RESET_URL') . '/' . urlencode($user_name) . '/' . urlencode($user_password_reset_hash);

        // create instance of Mail class, try sending and check
        // 创建邮件类的实例,试着发送和检查
        $mail = new Mail;
        $mail_sent = $mail->sendMail($user_email, Config::get('EMAIL_PASSWORD_RESET_FROM_EMAIL'),
            Config::get('EMAIL_PASSWORD_RESET_FROM_NAME'), Config::get('EMAIL_PASSWORD_RESET_SUBJECT'), $body
        );

        if ($mail_sent) {
            Session::add('feedback_positive', Text::get('FEEDBACK_PASSWORD_RESET_MAIL_SENDING_SUCCESSFUL'));
            return true;
        }

        Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_RESET_MAIL_SENDING_ERROR') . $mail->getError() );
        return false;
    }

    /**
     * Verifies the password reset request via the verification hash token (that's only valid for one hour)
     * 验证通过验证哈希密码重置请求令牌(只适用于一个小时)
     * @param string $user_name Username 用户名
     * @param string $verification_code Hash token 哈希令牌
     * @return bool Success status 成功地位
     */
    public static function verifyPasswordReset($user_name, $verification_code)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        // check if user-provided username + verification code combination exists
        // 检查是否存在用户提供的用户名+验证码组合
        $sql = "SELECT user_id, user_password_reset_timestamp
                  FROM users
                 WHERE user_name = :user_name
                       AND user_password_reset_hash = :user_password_reset_hash
                       AND user_provider_type = :user_provider_type
                 LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(
            ':user_password_reset_hash' => $verification_code, ':user_name' => $user_name,
            ':user_provider_type' => 'DEFAULT'
        ));

        // if this user with exactly this verification hash code does NOT exist
        // 如果这个用户这个验证散列码不存在
        if ($query->rowCount() != 1) {
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_RESET_COMBINATION_DOES_NOT_EXIST'));
            return false;
        }

        // get result row (as an object)
        // 得到结果行(对象)
        $result_user_row = $query->fetch();

        // 3600 seconds are 1 hour
        // 3600秒1小时
        $timestamp_one_hour_ago = time() - 3600;

        // if password reset request was sent within the last hour (this timeout is for security reasons)
        // 如果密码重置请求发送最后一个小时内(这个超时是出于安全原因)
        if ($result_user_row->user_password_reset_timestamp > $timestamp_one_hour_ago) {

            // verification was successful
            // 验证是成功的
            Session::add('feedback_positive', Text::get('FEEDBACK_PASSWORD_RESET_LINK_VALID'));
            return true;
        } else {
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_RESET_LINK_EXPIRED'));
            return false;
        }
    }

    /**
     * Writes the new password to the database
     * 将新密码写入数据库
     *
     * @param string $user_name username 用户名
     * @param string $user_password_hash
     * @param string $user_password_reset_hash
     *
     * @return bool
     */
    public static function saveNewUserPassword($user_name, $user_password_hash, $user_password_reset_hash)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "UPDATE users SET user_password_hash = :user_password_hash, user_password_reset_hash = NULL,
                       user_password_reset_timestamp = NULL
                 WHERE user_name = :user_name AND user_password_reset_hash = :user_password_reset_hash
                       AND user_provider_type = :user_provider_type LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(
            ':user_password_hash' => $user_password_hash, ':user_name' => $user_name,
            ':user_password_reset_hash' => $user_password_reset_hash, ':user_provider_type' => 'DEFAULT'
        ));

        // if one result exists, return true, else false. Could be written even shorter btw.
        // 如果存在一个结果,返回true,否则为假。可以写的更短顺便说一句。
        return ($query->rowCount() == 1 ? true : false);
    }

    /**
     * Set the new password (for DEFAULT user, FACEBOOK-users don't have a password)
     * Please note: At this point the user has already pre-verified via verifyPasswordReset() (within one hour),
     * so we don't need to check again for the 60min-limit here. In this method we authenticate
     * via username & password-reset-hash from (hidden) form fields.
     * 设置新密码(默认用户,facebook用户没有密码)
     * 请注意:此时用户已经添加通过verifyPasswordReset()(一小时内),所以我们不需要再次检查的60分钟以内。
     * 在这个方法中,我们通过用户名验证& password-reset-hash从表单字段(隐藏)。
     *
     * @param string $user_name
     * @param string $user_password_reset_hash
     * @param string $user_password_new
     * @param string $user_password_repeat
     *
     * @return bool success state of the password reset 成功的密码重置
     */
    public static function setNewPassword($user_name, $user_password_reset_hash, $user_password_new, $user_password_repeat)
    {
        // validate the password
        // 验证密码
        if (!self::validateResetPassword($user_name, $user_password_reset_hash, $user_password_new, $user_password_repeat)) {
            return false;
        }

        // crypt the password (with the PHP 5.5+'s password_hash() function, result is a 60 character hash string)
        // 地下室的密码(PHP 5.5+'s password_hash()函数,结果是一个60字符哈希字符串)
        $user_password_hash = password_hash($user_password_new, PASSWORD_DEFAULT);

        // write the password to database (as hashed and salted string), reset user_password_reset_hash
        // 写密码数据库(如散列和咸字符串),重置user_password_reset_hash
        if (self::saveNewUserPassword($user_name, $user_password_hash, $user_password_reset_hash)) {
            Session::add('feedback_positive', Text::get('FEEDBACK_PASSWORD_CHANGE_SUCCESSFUL'));
            return true;
        } else {
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_CHANGE_FAILED'));
            return false;
        }
    }

    /**
     * Validate the password submission
     * 验证密码提交
     *
     * @param $user_name
     * @param $user_password_reset_hash
     * @param $user_password_new
     * @param $user_password_repeat
     *
     * @return bool
     */
    public static function validateResetPassword($user_name, $user_password_reset_hash, $user_password_new, $user_password_repeat)
    {
        if (empty($user_name)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_USERNAME_FIELD_EMPTY'));
            return false;
        } else if (empty($user_password_reset_hash)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_RESET_TOKEN_MISSING'));
            return false;
        } else if (empty($user_password_new) || empty($user_password_repeat)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_FIELD_EMPTY'));
            return false;
        } else if ($user_password_new !== $user_password_repeat) {
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_REPEAT_WRONG'));
            return false;
        } else if (strlen($user_password_new) < 6) {
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_TOO_SHORT'));
            return false;
        }

        return true;
    }


    /**
     * Writes the new password to the database
     *
     * @param string $user_name
     * @param string $user_password_hash
     *
     * @return bool
     */
    public static function saveChangedPassword($user_name, $user_password_hash)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "UPDATE users SET user_password_hash = :user_password_hash
                 WHERE user_name = :user_name
                 AND user_provider_type = :user_provider_type LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(
            ':user_password_hash' => $user_password_hash, ':user_name' => $user_name,
            ':user_provider_type' => 'DEFAULT'
        ));

        // if one result exists, return true, else false. Could be written even shorter btw.
        return ($query->rowCount() == 1 ? true : false);
    }


    /**
     * Validates fields, hashes new password, saves new password
     *
     * @param string $user_name
     * @param string $user_password_current
     * @param string $user_password_new
     * @param string $user_password_repeat
     *
     * @return bool
     */
    public static function changePassword($user_name, $user_password_current, $user_password_new, $user_password_repeat)
    {
        // validate the passwords
        if (!self::validatePasswordChange($user_name, $user_password_current, $user_password_new, $user_password_repeat)) {
            return false;
        }

        // crypt the password (with the PHP 5.5+'s password_hash() function, result is a 60 character hash string)
        $user_password_hash = password_hash($user_password_new, PASSWORD_DEFAULT);

        // write the password to database (as hashed and salted string)
        if (self::saveChangedPassword($user_name, $user_password_hash)) {
            Session::add('feedback_positive', Text::get('FEEDBACK_PASSWORD_CHANGE_SUCCESSFUL'));
            return true;
        } else {
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_CHANGE_FAILED'));
            return false;
        }
    }


    /**
     * Validates current and new passwords
     *
     * @param string $user_name
     * @param string $user_password_current
     * @param string $user_password_new
     * @param string $user_password_repeat
     *
     * @return bool
     */
    public static function validatePasswordChange($user_name, $user_password_current, $user_password_new, $user_password_repeat)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT user_password_hash, user_failed_logins FROM users WHERE user_name = :user_name LIMIT 1;";
        $query = $database->prepare($sql);
        $query->execute(array(
            ':user_name' => $user_name
        ));

        $user = $query->fetch();

        if ($query->rowCount() == 1) {
            $user_password_hash = $user->user_password_hash;
        } else {
            Session::add('feedback_negative', Text::get('FEEDBACK_USER_DOES_NOT_EXIST'));
            return false;
        }

        if (!password_verify($user_password_current, $user_password_hash)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_CURRENT_INCORRECT'));
            return false;
        } else if (empty($user_password_new) || empty($user_password_repeat)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_FIELD_EMPTY'));
            return false;
        } else if ($user_password_new !== $user_password_repeat) {
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_REPEAT_WRONG'));
            return false;
        } else if (strlen($user_password_new) < 6) {
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_TOO_SHORT'));
            return false;
        } else if ($user_password_current == $user_password_new){
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_NEW_SAME_AS_CURRENT'));
            return false;
        }

        return true;
    }
}
