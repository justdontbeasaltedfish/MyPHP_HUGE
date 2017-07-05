<?php

/**
 * Class CaptchaModel
 * 类验证码模型
 *
 * This model class handles all the captcha stuff.
 * Currently this uses the excellent Captcha generator lib from https://github.com/Gregwar/Captcha
 * Have a look there for more options etc.
 * 这个模型类处理所有的验证码。
 * 目前它使用的验证码发生器自由 https://github.com/Gregwar/Captcha
 * 看看更多的选择等。
 */
class CaptchaModel
{
    /**
     * Generates the captcha, "returns" a real image, this is why there is header('Content-type: image/jpeg')
     * Note: This is a very special method, as this is echoes out binary data.
     * 生成验证码,“回报”一个真正的形象,这就是为什么有header('Content-type: image/jpeg')
     * 注意:这是一个非常特殊的方法,因为这是与二进制数据。
     */
    public static function generateAndShowCaptcha()
    {
        // create a captcha with the CaptchaBuilder lib (loaded via Composer)
        // 创建一个验证码CaptchaBuilder自由(加载通过作曲家)
        $captcha = new Gregwar\Captcha\CaptchaBuilder;
        $captcha->build(
            Config::get('CAPTCHA_WIDTH'),
            Config::get('CAPTCHA_HEIGHT')
        );

        // write the captcha character into session
        // 验证码字符写入会话
        Session::set('captcha', $captcha->getPhrase());

        // render an image showing the characters (=the captcha)
        // 呈现一个图像显示字符(=the captcha)
        header('Content-type: image/jpeg');
        $captcha->output();
    }

    /**
     * Checks if the entered captcha is the same like the one from the rendered image which has been saved in session
     * 检查输入的验证码是否相同的像渲染后的图像保存在会话
     * @param $captcha string The captcha characters 验证码字符
     * @return bool success of captcha check 成功的验证码检查
     */
    public static function checkCaptcha($captcha)
    {
        if (Session::get('captcha') && ($captcha == Session::get('captcha'))) {
            return true;
        }

        return false;
    }
}
