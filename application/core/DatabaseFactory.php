<?php

/**
 * Class DatabaseFactory
 * 类数据库工厂
 *
 * Use it like this:
 * $database = DatabaseFactory::getFactory()->getConnection();
 * 使用它是这样的:
 * $database = DatabaseFactory::getFactory()->getConnection();
 *
 * That's my personal favourite when creating a database connection.
 * It's a slightly modified version of Jon Raphaelson's excellent answer on StackOverflow:
 * http://stackoverflow.com/questions/130878/global-or-singleton-for-database-connection
 * 这是我个人最喜欢的在创建一个数据库连接。
 * 稍微修改版本的Jon Raphaelson StackOverflow优秀的回答:
 * http://stackoverflow.com/questions/130878/global-or-singleton-for-database-connection
 *
 * Full quote from the answer:
 * 完整的引用答案:
 *
 * "Then, in 6 months when your app is super famous and getting dugg and slashdotted and you decide you need more than
 * a single connection, all you have to do is implement some pooling in the getConnection() method. Or if you decide
 * that you want a wrapper that implements SQL logging, you can pass a PDO subclass. Or if you decide you want a new
 * connection on every invocation, you can do do that. It's flexible, instead of rigid."
 * “6个月之后,在当你的应用程序非常著名和名字,另外,你决定你需要的不仅仅是一个单一的连接,所有你需要做的就是实现一些池getConnection()方法。
 * 或者如果你决定,你想要一个包装器实现SQL登录,你可以通过一个PDO子类。或者如果你决定你想要每调用一个新的连接,你可以这样做。
 * 它是灵活的,而不是死板的。”
 *
 * Thanks! Big up, mate!
 * 谢谢!大了,伙伴!
 */
class DatabaseFactory
{
    private static $factory;
    private $database;

    public static function getFactory()
    {
        if (!self::$factory) {
            self::$factory = new DatabaseFactory();
        }
        return self::$factory;
    }

    public function getConnection()
    {
        if (!$this->database) {

            /**
             * Check DB connection in try/catch block. Also when PDO is not constructed properly,
             * prevent to exposing database host, username and password in plain text as:
             * PDO->__construct('mysql:host=127....', 'root', '12345678', Array)
             * by throwing custom error message
             * 检查数据库连接在try/catch块。也当PDO构造不当,防止暴露数据库主机、用户名和密码以纯文本:
             * PDO->__construct('mysql:host=127....', 'root', '12345678', Array)
             * 把定制的错误消息
             */
            try {
                $options = array(PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING);
                $this->database = new PDO(
                    Config::get('DB_TYPE') . ':host=' . Config::get('DB_HOST') . ';dbname=' .
                    Config::get('DB_NAME') . ';port=' . Config::get('DB_PORT') . ';charset=' . Config::get('DB_CHARSET'),
                    Config::get('DB_USER'), Config::get('DB_PASS'), $options
                );
            } catch (PDOException $e) {

                // Echo custom message. Echo error code gives you some info.
                // 自定义消息。回声错误代码给你一些信息。
                echo 'Database connection can not be estabilished. Please try again later.' . '<br>';
                echo 'Error code: ' . $e->getCode();

                // Stop application :(
                // 停止应用程序:(
                // No connection, reached limit connections etc. so no point to keep it running
                // 没有关系,达到限制连接等所以没有意义继续运行
                exit;
            }
        }
        return $this->database;
    }
}
