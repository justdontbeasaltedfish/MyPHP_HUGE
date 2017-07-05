<?php

/**
 * Session class
 * 会话类
 *
 * handles the session stuff. creates session when no one exists, sets and gets values, and closes the session
 * properly (=logout). Not to forget the check if the user is logged in or not.
 * 处理会话。创建会话当没有人存在,集和价值观,正确和关闭会话(=logout)。不要忘记检查用户是否登录。
 */
class Session
{
    /**
     * starts the session
     * 启动会议
     */
    public static function init()
    {
        // if no session exist, start the session
        // 如果不存在会话,会话开始
        if (session_id() == '') {
            session_start();
        }
    }

    /**
     * sets a specific value to a specific key of the session
     * 设置一个特定的会话值到一个特定的键
     *
     * @param mixed $key key 关键
     * @param mixed $value value 价值
     */
    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * gets/returns the value of a specific key of the session
     * 得到/返回值的一个特定的会话的关键
     *
     * @param mixed $key Usually a string, right ? $key通常一个字符串,对吧?
     * @return mixed the key's value or nothing 键的值
     */
    public static function get($key)
    {
        if (isset($_SESSION[$key])) {
            $value = $_SESSION[$key];

            // filter the value for XSS vulnerabilities
            // 过滤值XSS漏洞
            return Filter::XSSFilter($value);
        }
    }

    /**
     * adds a value as a new array element to the key.
     * useful for collecting error messages etc
     * 添加一个值作为一个新的数组元素的关键。
     * 用于收集错误信息等
     *
     * @param mixed $key
     * @param mixed $value
     */
    public static function add($key, $value)
    {
        $_SESSION[$key][] = $value;
    }

    /**
     * deletes the session (= logs the user out)
     * 删除会话(= logs the user out)
     */
    public static function destroy()
    {
        session_destroy();
    }

    /**
     * update session id in database
     * 更新数据库中的会话id
     *
     * @access public 公共
     * @static static method 静态方法
     * @param  string $userId
     * @param  string $sessionId
     */
    public static function updateSessionId($userId, $sessionId = null)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "UPDATE users SET session_id = :session_id WHERE user_id = :user_id";

        $query = $database->prepare($sql);
        $query->execute(array(':session_id' => $sessionId, ":user_id" => $userId));
    }

    /**
     * checks for session concurrency
     * 检查会话并发
     *
     * This is done as the following:
     * UserA logs in with his session id('123') and it will be stored in the database.
     * Then, UserB logs in also using the same email and password of UserA from another PC,
     * and also store the session id('456') in the database
     * 这是以下:
     * UserA登录会话id(“123”),它将被存储在数据库中。
     * 然后,UserB登录也使用相同的电子邮件和密码UserA从另一个电脑,还有存储会话id(“456”)在数据库中
     *
     * Now, Whenever UserA performs any action,
     * You then check the session_id() against the last one stored in the database('456'),
     * If they don't match then log both of them out.
     * 现在,每当UserA执行任何操作,然后检查session_id()对存储在数据库中的最后一个(“456”),如果他们不匹配日志他们两人。
     *
     * @access public 公共
     * @static static method 静态方法
     * @return bool
     * @see Session::updateSessionId()
     * @see http://stackoverflow.com/questions/6126285/php-stop-concurrent-user-logins
     */
    public static function isConcurrentSessionExists()
    {
        $session_id = session_id();
        $userId = Session::get('user_id');

        if (isset($userId) && isset($session_id)) {

            $database = DatabaseFactory::getFactory()->getConnection();
            $sql = "SELECT session_id FROM users WHERE user_id = :user_id LIMIT 1";

            $query = $database->prepare($sql);
            $query->execute(array(":user_id" => $userId));

            $result = $query->fetch();
            $userSessionId = !empty($result) ? $result->session_id : null;

            return $session_id !== $userSessionId;
        }

        return false;
    }

    /**
     * Checks if the user is logged in or not
     * 检查用户是否登录
     *
     * @return bool user's login status 用户的登录状态
     */
    public static function userIsLoggedIn()
    {
        return (self::get('user_logged_in') ? true : false);
    }
}
