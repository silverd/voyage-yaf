<?php

Yaf_Loader::import(SYS_PATH . 'Third/PHPMailer/class.phpmailer.php');

class Com_Email
{
    /**
     * 发送邮件
     *
     * @return bool
     */
    public static function send($email, $title, $content)
    {
        $mail = new PHPMailer();
        $mail->CharSet = "UTF-8";                       // 这里指定字符集！如果是utf-8则将gb2312修改为utf-8
        $mail->Encoding = "base64";

        $mail->IsSMTP();                                // 启用SMTP
        $mail->Host = "smtp.voyagemobile.com";          // SMTP服务器
        $mail->SMTPAuth = true;                         // 开启SMTP认证
        $mail->Username = "noreply@voyagemobile.com";   // SMTP用户名
        $mail->Password = "!qaz2wsx";                   // SMTP密码
        $mail->IsHTML(true);                            // 是否HTML格式邮件

        $mail->From = "noreply@voyagemobile.com";       // 发件人地址
        $mail->FromName = "noreply";                    // 发件人
        $mail->AddAddress($email);                      // 添加收件人

        $mail->Subject = $title;                        // 邮件主题
        $mail->Body    = $content;                      // 邮件内容

        return $mail->Send();
    }
}