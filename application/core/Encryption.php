<?php

/**
 * Encryption and Decryption Class
 * 加密和解密类
 *
 */
class Encryption
{

    /**
     * Cipher algorithm
     * 密码算法
     *
     * @var string
     */
    const CIPHER = 'aes-256-cbc';

    /**
     * Hash function
     * 哈希函数
     *
     * @var string
     */
    const HASH_FUNCTION = 'sha256';

    /**
     * constructor for Encryption object.
     * 加密对象的构造函数。
     *
     * @access private 私人
     */
    private function __construct()
    {
    }

    /**
     * Encrypt a string.
     * 加密字符串。
     *
     * @access public 公共
     * @static static method static method
     * @param  string $plain
     * @return string
     * @throws Exception If functions don't exists 如果函数不存在异常
     */
    public static function encrypt($plain)
    {
        if (!function_exists('openssl_cipher_iv_length') ||
            !function_exists('openssl_random_pseudo_bytes') ||
            !function_exists('openssl_encrypt')
        ) {

            throw new Exception('Encryption function doesn\'t exist');
        }

        // generate initialization vector,
        // this will make $iv different every time,
        // so, encrypted string will be also different.
        // 生成初始化向量,这将使$iv每次都不同,所以,加密字符串也将不同。
        $iv_size = openssl_cipher_iv_length(self::CIPHER);
        $iv = openssl_random_pseudo_bytes($iv_size);

        // generate key for authentication using ENCRYPTION_KEY & HMAC_SALT
        // 使用ENCRYPTION_KEY & HMAC_SALT生成密钥对身份验证
        $key = mb_substr(hash(self::HASH_FUNCTION, Config::get('ENCRYPTION_KEY') . Config::get('HMAC_SALT')), 0, 32, '8bit');

        // append initialization vector
        // 添加初始化向量
        $encrypted_string = openssl_encrypt($plain, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        $ciphertext = $iv . $encrypted_string;

        // apply the HMAC
        // 应用了HMAC
        $hmac = hash_hmac('sha256', $ciphertext, $key);

        return $hmac . $ciphertext;
    }

    /**
     * Decrypted a string.
     * 解密字符串。
     *
     * @access public 公共
     * @static static method 静态方法
     * @param  string $ciphertext
     * @return string
     * @throws Exception If $ciphertext is empty, or If functions don't exists 异常如果$ciphertext是空的,或如果函数不存在
     */
    public static function decrypt($ciphertext)
    {
        if (empty($ciphertext)) {
            throw new Exception('The String to decrypt can\'t be empty');
        }

        if (!function_exists('openssl_cipher_iv_length') ||
            !function_exists('openssl_decrypt')
        ) {

            throw new Exception('Encryption function doesn\'t exist');
        }

        // generate key used for authentication using ENCRYPTION_KEY & HMAC_SALT
        // 使用ENCRYPTION_KEY & HMAC_SALT生成密钥用于身份验证
        $key = mb_substr(hash(self::HASH_FUNCTION, Config::get('ENCRYPTION_KEY') . Config::get('HMAC_SALT')), 0, 32, '8bit');

        // split cipher into: hmac, cipher & iv
        // 将密码分为:hmac,密码和iv
        $macSize = 64;
        $hmac = mb_substr($ciphertext, 0, $macSize, '8bit');
        $iv_cipher = mb_substr($ciphertext, $macSize, null, '8bit');

        // generate original hmac & compare it with the one in $ciphertext
        // 生成原始hmac &比较它与$ciphertext
        $originalHmac = hash_hmac('sha256', $iv_cipher, $key);
        if (!self::hashEquals($hmac, $originalHmac)) {
            return false;
        }

        // split out the initialization vector and cipher
        $iv_size = openssl_cipher_iv_length(self::CIPHER);
        $iv = mb_substr($iv_cipher, 0, $iv_size, '8bit');
        $cipher = mb_substr($iv_cipher, $iv_size, null, '8bit');

        return openssl_decrypt($cipher, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * A timing attack resistant comparison.
     * 比较时间攻击的抵抗力。
     *
     * @access private 私人
     * @static static method 静态方法
     * @param string $hmac The hmac from the ciphertext being decrypted. 了hmac密文的解密。
     * @param string $compare The comparison hmac. hmac进行了比较。
     * @return bool
     * @see https://github.com/sarciszewski/php-future/blob/bd6c91fb924b2b35a3e4f4074a642868bd051baf/src/Security.php#L36
     */
    private static function hashEquals($hmac, $compare)
    {
        if (function_exists('hash_equals')) {
            return hash_equals($hmac, $compare);
        }

        // if hash_equals() is not available,
        // 如果hash_equals()不可用,
        // then use the following snippet.
        // 然后使用以下代码片段。
        // It's equivalent to hash_equals() in PHP 5.6.
        // 它等于hash_equals()在PHP 5.6。
        $hashLength = mb_strlen($hmac, '8bit');
        $compareLength = mb_strlen($compare, '8bit');

        if ($hashLength !== $compareLength) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < $hashLength; $i++) {
            $result |= (ord($hmac[$i]) ^ ord($compare[$i]));
        }

        return $result === 0;
    }
}
