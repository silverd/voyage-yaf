<?php

/**
 * 发送邮件
 * SendCloud WebApi 云服务
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Com_Mailer_SendCloud
{
    const
        API_USER = 'morecruit',
        API_KEY  = 'Du5zW3h7XYV1S8fa',
        API_URL  = 'http://sendcloud.sohu.com/webapi/mail.send.json';

    /*
        参数格式示例：
        $params = [
             'from'     => 'sendcloud@sendcloud.org',
             'fromname' => 'SendCloud',
             'to'       => 'to1@domain.com;to2@domain.com',   // 多个用半角分号分隔
             'subject'  => 'Sendcloud php webapi example',
             'html'     => "<html><head></head><body><p>欢迎使用<a href=\'http://sendcloud.sohu.com\'>SendCloud</a></p></body></html>",
             'files'    => '@./test.txt',
        ];
    */
    public static function send(array $params)
    {
        $params['api_user'] = self::API_USER;
        $params['api_key']  = self::API_KEY;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL, self::API_URL);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        $result = curl_exec($ch);

        if ($result === false) {
            $ret = [
                'status' => 0,
                'return_msg' => '发送邮件失败：' . curl_error($ch)
            ];
        } else {
            $result = json_decode($result, true);
            if ($result['message'] == 'error') {
                 $ret = [
                    'status' => 0,
                    'return_msg' => $result['errors']
                ];
            } else {
                 $ret = [
                    'status' => 1,
                    'return_msg' => ''
                ];
            }
        }

        curl_close($ch);

        return $ret;
    }
}