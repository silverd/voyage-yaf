<?php

/**
 * 3DES 加密解密
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: TripleDES.php 2 2012-11-14 07:10:07Z jiangjian $
 */

class Helper_Cryption_TripleDES
{
    /**
     * 加密向量（8位）
     *
     * @var string
     */
    private static $_defaultIV = '12345678';

    /**
     * 使用3DES加密源数据
     *
     * @param string $orgSource 源数据
     * @param string $key       密钥
     * @param string $iv        加密向量
     * @return string $result   密文
     */
    public static function encrypt($orgSource, $key, $iv = null)
    {
        $iv = self::_iv($iv);
        $orgSource = self::_addPKCS7Padding($orgSource);

        $td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');

        mcrypt_generic_init($td, $key, $iv);
        $result = mcrypt_generic($td, $orgSource);
        mcrypt_generic_deinit($td);

        mcrypt_module_close($td);

        return $result;
    }

    /**
     * 使用3DES解密密文
     *
     * @param string $encryptedData 密文
     * @param string $key           密钥
     * @param string $iv            加密向量
     * @return string $result       解密后的原文
     */
    public static function decrypt($encryptedData, $key, $iv = null)
    {
        $iv = self::_iv($iv);

        $td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');

        mcrypt_generic_init($td, $key, $iv);
        $result = mdecrypt_generic($td, $encryptedData);
        mcrypt_generic_deinit($td);

        mcrypt_module_close($td);

        $result = self::_stripPKSC7Padding($result);
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

    /**
     * 为字符串添加PKCS7 Padding
     *
     * @param string $source 源字符串
     * @return string
     */
    private static function _addPKCS7Padding($source)
    {
        $block = mcrypt_get_block_size('tripledes', 'cbc');
        $pad = $block - (strlen($source) % $block);
        if ($pad <= $block) {
            $char = chr($pad);
            $source .= str_repeat($char, $pad);
        }
        return $source;
    }

    /**
     * 去除字符串末尾的 PKCS7 Padding
     *
     * @param string $source 带有padding字符的字符串
     * @return string
     */
    private static function _stripPKSC7Padding($source)
    {
        $block = mcrypt_get_block_size('tripledes', 'cbc');
        $char = substr($source, -1, 1);
        $num = ord($char);
        if ($num > 8) {
            return $source;
        }

        $len = strlen($source);
        for ($i = $len - 1; $i >= $len - $num; $i--) {
            if (ord(substr($source, $i, 1)) != $num) {
                return $source;
            }
        }

        $source = substr($source, 0, -$num);
        return $source;
    }
}