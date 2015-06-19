<?php

/**
 * 发送邮件
 * PHPMailer STMP
 *
 * @author JiangJian <silverd@sohu.com>
 */

Yaf_Loader::import(SYS_PATH . 'Third/PHPMailer/class.phpmailer.php');

class Com_Mailer_PHPMailer
{
    const
        SMTP_HOST = 'smtp.qq.com',
        SMTP_USER = 'noreply@morecruit.cn',
        SMTP_PWD  = 'Noreply1234';

    public static function send(array $params)
    {
        $mail = new PHPMailer();

        $mail->CharSet  = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->IsSMTP();
        $mail->SMTPAuth  = true;
        $mail->SMTPDebug = false;
        $mail->Host      = self::SMTP_HOST;
        $mail->Username  = self::SMTP_USER;
        $mail->Password  = self::SMTP_PWD;

        $mail->From      = $params['from'];
        $mail->FromName  = $params['fromname'];

        // 指定收件人
        foreach (explode(';', $params['to']) as $email) {
            $mail->AddAddress($email);
        }

        $mail->IsHTML(true);
        $mail->Subject = $params['subject'];
        $mail->Body    = $params['html'];

        if (! $mail->Send()) {
            $ret = [
                'status' => 0,
                'return_msg' => $mail->ErrorInfo
            ];
        } else {
            $ret = [
                'status' => 1,
                'return_msg' => ''
            ];
        }

        return $ret;
    }
}