<?php

/**
 * Class Mail
 * 类邮件
 *
 * Handles everything regarding mail-sending.
 * 关于mail-sending处理一切。
 */
class Mail
{
    /** @var mixed variable to collect errors 变量收集错误 */
    private $error;

    /**
     * Try to send a mail by using PHP's native mail() function.
     * Please note that not PHP itself will send a mail, it's just a wrapper for Linux's sendmail or other mail tools
     * 尝试发送邮件使用PHP的本地mail()函数。
     * 请注意,PHP本身不会发送邮件,它只是一个包装器为Linux的sendmail或其他邮件的工具
     *
     * Good guideline on how to send mails natively with mail():
     * 良好的指导如何使用邮件发送邮件mail():
     * @see http://stackoverflow.com/a/24644450/1114320
     * @see http://www.php.net/manual/en/function.mail.php
     */
    public function sendMailWithNativeMailFunction()
    {
        // no code yet, so we just return something to make IDEs and code analyzer tools happy
        // 没有代码,所以我们只返回使ide和代码分析器工具快乐
        return false;
    }

    /**
     * Try to send a mail by using SwiftMailer.
     * Make sure you have loaded SwiftMailer via Composer.
     * 通过使用SwiftMailer试图发送一个邮件。
     * 确保你有加载SwiftMailer通过作曲家。
     *
     * @return bool
     */
    public function sendMailWithSwiftMailer()
    {
        // no code yet, so we just return something to make IDEs and code analyzer tools happy
        // 没有代码,所以我们只返回使ide和代码分析器工具快乐
        return false;
    }

    /**
     * Try to send a mail by using PHPMailer.
     * Make sure you have loaded PHPMailer via Composer.
     * Depending on your EMAIL_USE_SMTP setting this will work via SMTP credentials or via native mail()
     * 通过使用PHPMailer试图发送一个邮件。
     * 确保你有加载PHPMailer通过作曲家。
     * 这将取决于你EMAIL_USE_SMTP设置工作通过SMTP认证或通过本地mail()
     *
     * @param $user_email
     * @param $from_email
     * @param $from_name
     * @param $subject
     * @param $body
     *
     * @return bool
     * @throws Exception
     * @throws phpmailerException
     */
    public function sendMailWithPHPMailer($user_email, $from_email, $from_name, $subject, $body)
    {
        $mail = new PHPMailer;

        // you should use UTF-8 to avoid encoding issues
        // 您应该使用UTF-8,避免编码问题
        $mail->CharSet = 'UTF-8';

        // if you want to send mail via PHPMailer using SMTP credentials
        // 如果你想使用SMTP发送邮件通过PHPMailer凭证
        if (Config::get('EMAIL_USE_SMTP')) {

            // set PHPMailer to use SMTP
            // 设置PHPMailer使用SMTP
            $mail->IsSMTP();

            // 0 = off, 1 = commands, 2 = commands and data, perfect to see SMTP errors
            // 0 =,1 =命令,2 =命令和数据,完美的SMTP错误
            $mail->SMTPDebug = 0;

            // enable SMTP authentication
            // 使SMTP认证
            $mail->SMTPAuth = Config::get('EMAIL_SMTP_AUTH');

            // encryption
            // 加密
            if (Config::get('EMAIL_SMTP_ENCRYPTION')) {
                $mail->SMTPSecure = Config::get('EMAIL_SMTP_ENCRYPTION');
            }

            // set SMTP provider's credentials
            // SMTP设定提供者的凭证
            $mail->Host = Config::get('EMAIL_SMTP_HOST');
            $mail->Username = Config::get('EMAIL_SMTP_USERNAME');
            $mail->Password = Config::get('EMAIL_SMTP_PASSWORD');
            $mail->Port = Config::get('EMAIL_SMTP_PORT');

        } else {

            $mail->IsMail();
        }

        // fill mail with data
        // 邮件填充数据
        $mail->From = $from_email;
        $mail->FromName = $from_name;
        $mail->AddAddress($user_email);
        $mail->Subject = $subject;
        $mail->Body = $body;

        // try to send mail, put result status (true/false into $wasSendingSuccessful)
        // 尝试发送邮件,把结果状态(真/假到$wasSendingSuccessful)
        // I'm unsure if mail->send really returns true or false every time, tis method in PHPMailer is quite complex
        // 我不确定如果mail->send真的每次都返回真或假,这方法PHPMailer相当复杂
        $wasSendingSuccessful = $mail->Send();

        if ($wasSendingSuccessful) {
            return true;

        } else {

            // if not successful, copy errors into Mail's error property
            // 如果不成功,错误复制到邮件的错误性质
            $this->error = $mail->ErrorInfo;
            return false;
        }
    }

    /**
     * The main mail sending method, this simply calls a certain mail sending method depending on which mail provider
     * you've selected in the application's config.
     * 主要的邮件发送方法,这个简单的调用某个邮件发送方法根据邮件提供者中选择你应用程序的配置。
     *
     * @param $user_email string email 电子邮件
     * @param $from_email string sender's email 发送者的电子邮件
     * @param $from_name string sender's name 发送者的名字
     * @param $subject string subject 主题
     * @param $body string full mail body text 完整的邮件正文
     * @return bool the success status of the according mail sending method 的成功状态显示邮件发送方法
     */
    public function sendMail($user_email, $from_email, $from_name, $subject, $body)
    {
        if (Config::get('EMAIL_USED_MAILER') == "phpmailer") {

            // returns true if successful, false if not
            // 返回true,如果成功,如果不是假的
            return $this->sendMailWithPHPMailer(
                $user_email, $from_email, $from_name, $subject, $body
            );
        }

        if (Config::get('EMAIL_USED_MAILER') == "swiftmailer") {
            return $this->sendMailWithSwiftMailer();
        }

        if (Config::get('EMAIL_USED_MAILER') == "native") {
            return $this->sendMailWithNativeMailFunction();
        }
    }

    /**
     * The different mail sending methods write errors to the error property $this->error,
     * this method simply returns this error / error array.
     * 不同的邮件发送方法写错误错误属性$this->error,此方法仅返回这个错误/错误数组。
     *
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }
}
