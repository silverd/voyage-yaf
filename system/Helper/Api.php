<?php

/**
 * Api 相关助手函数
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Api.php 7057 2013-11-25 08:41:34Z jiangjian $
 */

class Helper_Api
{
    // 服务端用：验证签名
    public static function verifySign(array $params, $secretKey)
    {
        if (! isset($params['sign']) || ! $params['sign']) {
            return false;
        }

        $orgSign = $params['sign'];
        unset($params['sign']);

        // 重新构造签名
        $sign = self::_buildSign($params, $secretKey);

        // 比对签名
        return $sign == $orgSign ? true : false;
    }

    // 客户端用：发起请求
    public static function request($url, array $params = array(), $secretKey = null)
    {
        if ($secretKey) {
            $params['sign'] = self::_buildSign($params, $secretKey);
        }

        $result = Com_Http::sendRequest($url, $params, 'CURL-POST');

        return json_decode($result, true);
    }

    // 构造签名字符串
    protected static function _buildSign(array $params, $secretKey)
    {
        ksort($params);

        $sign = sha1(http_build_query($params) . '|' . $secretKey);

        return $sign;
    }
}