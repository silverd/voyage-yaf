<?php

/**
 * 公用加密、解密类
 *
 * @author Discuz! <silverd@sohu.com>
 * $Id: AuthCode.php 2 2012-11-14 07:10:07Z jiangjian $
 */

class Helper_Cryption_AuthCode
{
    /**
     * 缺省私钥
     */
    private static $_defaultKey = 'a6e7allz265eb728silverdz5a3301tf';

    public static function encrypt($string, $key = '', $expiry = 0)
    {
        return self::_authCode($string, 'ENCODE', $key, $expiry);
    }

    public static function decrypt($string, $key = '', $expiry = 0)
    {
        return self::_authCode($string, 'DECODE', $key, $expiry);
    }

    /**
     * 加密、解密函数 (UCenter 1.5.2)
     *
     * @param string    $string     连接后的字符串
     * @param string    $operation  ENCODE|DECODE 加密或解密操作
     * @param string    $key        密钥
     * @param int       $expiry     过期时间
     * @return string
     */
    private static function _authCode($string, $operation = 'DECODE', $key = '', $expiry = 0)
    {
        $ckey_length = 4;    // 随机密钥长度 取值 0-32;
        // 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
        // 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
        // 当此值为 0 时，则不产生随机密钥

        $key = md5($key ? $key : self::$_defaultKey);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

        $cryptkey = $keya.md5($keya.$keyc);
        $key_length = strlen($cryptkey);

        $string = $operation == 'DECODE'
            ? base64_decode(substr($string, $ckey_length))
            : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string.$keyb), 0, 16) . $string;

        $string_length = strlen($string);

        $result = '';
        $box = range(0, 255);

        $rndkey = array();
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if ($operation == 'DECODE') {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc.str_replace('=', '', base64_encode($result));
        }
    }
}