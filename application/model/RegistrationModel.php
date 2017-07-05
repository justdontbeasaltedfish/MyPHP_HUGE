<?php

/**
 * Class RegistrationModel
 * 类注册模型
 *
 * Everything registration-related happens here.
 * 这里发生了注册相关的一切。
 */
class RegistrationModel
{
    /**
     * Handles the entire registration process for DEFAULT users (not for people who register with
     * 3rd party services, like facebook) and creates a new user in the database if everything is fine
     * 处理整个注册过程为默认用户(不是为那些注册第三方服务,像facebook)并在数据库中创建一个新用户,如果一切都很好
     *
     * @return boolean Gives back the success status of the registration 回馈的成功状态登记
     */
    public static function registerNewUser()
    {
        // clean the input
        // 干净的输入
        $user_name = strip_tags(Request::post('user_name'));
        $user_email = strip_tags(Request::post('user_email'));
        $user_email_repeat = strip_tags(Request::post('user_email_repeat'));
        $user_password_new = Request::post('user_password_new');
        $user_password_repeat = Request::post('user_password_repeat');

        // stop registration flow if registrationInputValidation() returns false (= anything breaks the input check rules)
        // 停止注册流程如果registrationInputValidation()返回false(= anything breaks the input check rules)
        $validation_result = self::registrationInputValidation(Request::post('captcha'), $user_name, $user_password_new, $user_password_repeat, $user_email, $user_email_repeat);
        if (!$validation_result) {
            return false;
        }

        // crypt the password with the PHP 5.5's password_hash() function, results in a 60 character hash string.
        // 地下室的密码与PHP 5.5的password_hash()函数,结果在一个60字符哈希字符串。
        // @see php.net/manual/en/function.password-hash.php for more, especially for potential options
        // @see php.net/manual/en/function.password-hash.php 更多信息,特别是对潜在的选项
        $user_password_hash = password_hash($user_password_new, PASSWORD_DEFAULT);

        // make return a bool variable, so both errors can come up at once if needed
        // 返回一个布尔值变量,所以如果需要可以马上出现错误
        $return = true;

        // check if username already exists
        // 检查用户名是否已经存在
        if (UserModel::doesUsernameAlreadyExist($user_name)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_USERNAME_ALREADY_TAKEN'));
            $return = false;
        }

        // check if email already exists
        // 检查电子邮件是否已经存在
        if (UserModel::doesEmailAlreadyExist($user_email)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_USER_EMAIL_ALREADY_TAKEN'));
            $return = false;
        }

        // if Username or Email were false, return false
        // 如果用户名或电子邮件是错误的,返回false
        if (!$return) return false;

        // generate random hash for email verification (40 char string)
        // 为电子邮件验证生成随机散列(40字符字符串)
        $user_activation_hash = sha1(uniqid(mt_rand(), true));

        // write user data to database
        // 用户数据写入数据库
        if (!self::writeNewUserToDatabase($user_name, $user_password_hash, $user_email, time(), $user_activation_hash)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_ACCOUNT_CREATION_FAILED'));
            return false;
            // no reason not to return false here
            // 没有理由不返回false
        }

        // get user_id of the user that has been created, to keep things clean we DON'T use lastInsertId() here
        // user_id已经创建了用户,保持清洁我们不用lastInsertId()
        $user_id = UserModel::getUserIdByUsername($user_name);

        if (!$user_id) {
            Session::add('feedback_negative', Text::get('FEEDBACK_UNKNOWN_ERROR'));
            return false;
        }

        // send verification email
        // 发送验证邮件
        if (self::sendVerificationEmail($user_id, $user_email, $user_activation_hash)) {
            Session::add('feedback_positive', Text::get('FEEDBACK_ACCOUNT_SUCCESSFULLY_CREATED'));
            return true;
        }

        // if verification email sending failed: instantly delete the user
        // 如果验证邮件发送失败:立即删除用户
        self::rollbackRegistrationByUserId($user_id);
        Session::add('feedback_negative', Text::get('FEEDBACK_VERIFICATION_MAIL_SENDING_FAILED'));
        return false;
    }

    /**
     * Validates the registration input
     * 验证注册输入
     *
     * @param $captcha
     * @param $user_name
     * @param $user_password_new
     * @param $user_password_repeat
     * @param $user_email
     * @param $user_email_repeat
     *
     * @return bool
     */
    public static function registrationInputValidation($captcha, $user_name, $user_password_new, $user_password_repeat, $user_email, $user_email_repeat)
    {
        $return = true;

        // perform all necessary checks
        // 执行所有必要的检查
        if (!CaptchaModel::checkCaptcha($captcha)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_CAPTCHA_WRONG'));
            $return = false;
        }

        // if username, email and password are all correctly validated, but make sure they all run on first sumbit
        // 如果用户名、电子邮件和密码都正确验证,但要确保他们都第一次sumbit上运行
        if (self::validateUserName($user_name) AND self::validateUserEmail($user_email, $user_email_repeat) AND self::validateUserPassword($user_password_new, $user_password_repeat) AND $return) {
            return true;
        }

        // otherwise, return false
        // 否则,返回假
        return false;
    }

    /**
     * Validates the username
     * 验证用户名
     *
     * @param $user_name
     * @return bool
     */
    public static function validateUserName($user_name)
    {
        if (empty($user_name)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_USERNAME_FIELD_EMPTY'));
            return false;
        }

        // if username is too short (2), too long (64) or does not fit the pattern (aZ09)
        // 如果用户名太短(2),太长(64)或不符合模式(aZ09)
        if (!preg_match('/^[a-zA-Z0-9]{2,64}$/', $user_name)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_USERNAME_DOES_NOT_FIT_PATTERN'));
            return false;
        }

        return true;
    }

    /**
     * Validates the email
     * 验证电子邮件
     *
     * @param $user_email
     * @param $user_email_repeat
     * @return bool
     */
    public static function validateUserEmail($user_email, $user_email_repeat)
    {
        if (empty($user_email)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_EMAIL_FIELD_EMPTY'));
            return false;
        }

        if ($user_email !== $user_email_repeat) {
            Session::add('feedback_negative', Text::get('FEEDBACK_EMAIL_REPEAT_WRONG'));
            return false;
        }

        // validate the email with PHP's internal filter
        // side-fact: Max length seems to be 254 chars
        // 验证电子邮件使用PHP的内部过滤器
        // side-fact:最大长度似乎是254个字符
        // @see http://stackoverflow.com/questions/386294/what-is-the-maximum-length-of-a-valid-email-address
        if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_EMAIL_DOES_NOT_FIT_PATTERN'));
            return false;
        }

        return true;
    }

    /**
     * Validates the password
     * 验证密码
     *
     * @param $user_password_new
     * @param $user_password_repeat
     * @return bool
     */
    public static function validateUserPassword($user_password_new, $user_password_repeat)
    {
        if (empty($user_password_new) OR empty($user_password_repeat)) {
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_FIELD_EMPTY'));
            return false;
        }

        if ($user_password_new !== $user_password_repeat) {
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_REPEAT_WRONG'));
            return false;
        }

        if (strlen($user_password_new) < 6) {
            Session::add('feedback_negative', Text::get('FEEDBACK_PASSWORD_TOO_SHORT'));
            return false;
        }

        return true;
    }

    /**
     * Writes the new user's data to the database
     * 将新用户的数据写入数据库
     *
     * @param $user_name
     * @param $user_password_hash
     * @param $user_email
     * @param $user_creation_timestamp
     * @param $user_activation_hash
     *
     * @return bool
     */
    public static function writeNewUserToDatabase($user_name, $user_password_hash, $user_email, $user_creation_timestamp, $user_activation_hash)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        // write new users data into database
        // 新用户的数据写入数据库
        $sql = "INSERT INTO users (user_name, user_password_hash, user_email, user_creation_timestamp, user_activation_hash, user_provider_type)
                    VALUES (:user_name, :user_password_hash, :user_email, :user_creation_timestamp, :user_activation_hash, :user_provider_type)";
        $query = $database->prepare($sql);
        $query->execute(array(':user_name' => $user_name,
            ':user_password_hash' => $user_password_hash,
            ':user_email' => $user_email,
            ':user_creation_timestamp' => $user_creation_timestamp,
            ':user_activation_hash' => $user_activation_hash,
            ':user_provider_type' => 'DEFAULT'));
        $count = $query->rowCount();
        if ($count == 1) {
            return true;
        }

        return false;
    }

    /**
     * Deletes the user from users table. Currently used to rollback a registration when verification mail sending
     * was not successful.
     * 删除用户从用户表。目前用于回滚时注册验证邮件发送不成功。
     *
     * @param $user_id
     */
    public static function rollbackRegistrationByUserId($user_id)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $query = $database->prepare("DELETE FROM users WHERE user_id = :user_id");
        $query->execute(array(':user_id' => $user_id));
    }

    /**
     * Sends the verification email (to confirm the account).
     * The construction of the mail $body looks weird at first, but it's really just a simple string.
     * 发送验证邮件(确认账户)。
     * 建设的邮件$body看上去很奇怪,但这只是一个简单的字符串。
     *
     * @param int $user_id user's id 用户的id
     * @param string $user_email user's email 用户的电子邮件
     * @param string $user_activation_hash user's mail verification hash string 用户的邮件验证哈希字符串
     *
     * @return boolean gives back true if mail has been sent, gives back false if no mail could been sent
     * 回馈真的如果邮件已经发送,回馈假如果没有邮件可以被发送
     */
    public static function sendVerificationEmail($user_id, $user_email, $user_activation_hash)
    {
        $body = Config::get('EMAIL_VERIFICATION_CONTENT') . Config::get('URL') . Config::get('EMAIL_VERIFICATION_URL')
            . '/' . urlencode($user_id) . '/' . urlencode($user_activation_hash);

        $mail = new Mail;
        $mail_sent = $mail->sendMail($user_email, Config::get('EMAIL_VERIFICATION_FROM_EMAIL'),
            Config::get('EMAIL_VERIFICATION_FROM_NAME'), Config::get('EMAIL_VERIFICATION_SUBJECT'), $body
        );

        if ($mail_sent) {
            Session::add('feedback_positive', Text::get('FEEDBACK_VERIFICATION_MAIL_SENDING_SUCCESSFUL'));
            return true;
        } else {
            Session::add('feedback_negative', Text::get('FEEDBACK_VERIFICATION_MAIL_SENDING_ERROR') . $mail->getError());
            return false;
        }
    }

    /**
     * checks the email/verification code combination and set the user's activation status to true in the database
     * 检查email/verification组合和设置数据库中用户的激活状态为真
     *
     * @param int $user_id user id 用户id
     * @param string $user_activation_verification_code verification token 验证令牌
     *
     * @return bool success status 成功地位
     */
    public static function verifyNewUser($user_id, $user_activation_verification_code)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "UPDATE users SET user_active = 1, user_activation_hash = NULL
                WHERE user_id = :user_id AND user_activation_hash = :user_activation_hash LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(':user_id' => $user_id, ':user_activation_hash' => $user_activation_verification_code));

        if ($query->rowCount() == 1) {
            Session::add('feedback_positive', Text::get('FEEDBACK_ACCOUNT_ACTIVATION_SUCCESSFUL'));
            return true;
        }

        Session::add('feedback_negative', Text::get('FEEDBACK_ACCOUNT_ACTIVATION_FAILED'));
        return false;
    }
}
