<?php

/**
 * DES 加密解密(CBC)
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: DES.php 2 2012-11-14 07:10:07Z jiangjian $
 */

class Helper_Cryption_DES
{
    /**
     * 加密向量（8位）
     *
     * @var string
     */
    private static $_defaultIV = '12345678';

    /**
     * 加密
     *
     * @param string $string    源数据
     * @param string $key       密钥（8位）
     * @param string $iv        加密向量（8位）
     * @return string $result   密文
     */
    public static function encrypt($string, $key, $iv = null)
    {
        $iv = self::_iv($iv);

        $size = mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_CBC);
        $string = self::_pkcs5Pad($string, $size);
        $data = mcrypt_encrypt(MCRYPT_DES, $key, $string, MCRYPT_MODE_CBC, $iv);

        return $data;
    }

    /**
     * 解密
     *
     * @param string $string    密文
     * @param string $key       密钥（8位）
     * @param string $iv        加密向量（8位）
     * @return string $result   原文
     */
    public static function decrypt($string, $key, $iv = null)
    {
        $iv = self::_iv($iv);

        $result = mcrypt_decrypt(MCRYPT_DES, $key, $string, MCRYPT_MODE_CBC, $iv);
        $result = self::_pkcs5Unpad($result);

        return $result;
    }

    private static function _iv($iv)
    {
        if ($iv === null) {
            return self::$_defaultIV;
        }

        if (is_array($iv)) {
            $s = '';
            foreach ($iv as $s) {
                $s .= chr($iv);
            }
            return $s;
        }

        return $iv;
    }

    private static function _pkcs5Pad($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    private static function _pkcs5Unpad($text)
    {
        $pad = ord($text {strlen($text) - 1});
        if ($pad > strlen($text)) {
            return false;
        }

        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
            return false;
        }

        return substr($text, 0, - 1 * $pad);
    }
}