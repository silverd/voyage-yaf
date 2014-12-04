<?php

/**
 * 字符串处理
 *
 * @author JiangJian <silverd@sohu.com>
 * @modifier ZhangYanJiong <zhangyanjiong@q.com.cn>
 * $Id: String.php 12178 2014-09-26 02:10:30Z jiangjian $
 */

class Helper_String
{
    /**
     * 产生随机字符
     *
     * @param int $length
     * @param bool $numeric 是否为纯数字
     * @return string
     */
    public static function random($length, $numeric = false, $sourceStr = '')
    {
        if ($numeric) {
            $hash = sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
        } else {
            $hash  = '';
            $chars = $sourceStr ? $sourceStr : 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
            $max   = strlen($chars) - 1;
            for ($i = 0; $i < $length; $i++) {
                $hash .= $chars[mt_rand(0, $max)];
            }
        }
        return $hash;
    }

    /**
     * 截取字符串
     *
     * @param string $string
     * @param int $length
     * @param string $dot
     * @param string $charset
     * @return string
     */
    public static function cut($string, $length, $dot = ' ...', $charset = 'UTF-8')
    {
        if (strlen($string) <= $length) {
            return $string;
        }
        $string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array('&', '"', '<', '>'), $string);
        $strcut = '';
        if (strtolower($charset) == 'utf-8') {
            $n   = $tn  = $noc = 0;
            while ($n < strlen($string)) {
                $t = ord($string[$n]);
                if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                    $tn = 1;
                    $n++;
                    $noc++;
                } elseif (194 <= $t && $t <= 223) {
                    $tn = 2;
                    $n += 2;
                    $noc += 2;
                } elseif (224 <= $t && $t <= 239) {
                    $tn = 3;
                    $n += 3;
                    $noc += 2;
                } elseif (240 <= $t && $t <= 247) {
                    $tn = 4;
                    $n += 4;
                    $noc += 2;
                } elseif (248 <= $t && $t <= 251) {
                    $tn = 5;
                    $n += 5;
                    $noc += 2;
                } elseif ($t == 252 || $t == 253) {
                    $tn = 6;
                    $n += 6;
                    $noc += 2;
                } else {
                    $n++;
                }
                if ($noc >= $length) {
                    break;
                }
            }
            if ($noc > $length) {
                $n -= $tn;
            }
            $strcut = substr($string, 0, $n);
        } else {
            for ($i = 0; $i < $length; $i++) {
                $strcut .= ord($string[$i]) > 127 ? $string[$i] . $string[++$i] : $string[$i];
            }
        }
        $strcut = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);
        return $strcut . $dot;
    }

    /**
     * 遍历处理数组
     *
     * @param mixed $data
     * @param string $function
     * @return mixed
     */
    public static function deepFilterData($data, $function)
    {
        if (! $data || ! $function) {
            return $data;
        }
        if (is_array($data) || is_object($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::deepFilterData($value, $function);
            }
        } else {
            $data = $function($data);
        }
        return $data;
    }

    /**
     * 遍历处理数组（可同时MAP多个函数）
     *
     * @param mixed $data
     * @param array $functions array('trim', 'strip_tags')
     * @return mixed
     */
    public static function deepFilterDatas($data, $functions)
    {
        if (! $data || ! $functions || ! is_array($functions)) {
            return $data;
        }
        foreach ($functions as $function) {
            $data = self::deepFilterData($data, $function);
        }
        return $data;
    }

    /**
     * 遍历转义处理字符串
     *
     * @param mixed $data
     * @return mixed
     */
    public static function deepFilterDatasInput($data)
    {
        if ($data) {
            $data = self::deepFilterDatas($data, array('trim', 'strip_tags'));
            $data = self::shtmlspecialchars($data);
        }

        return $data;
    }

    /**
     * 取消HTML代码
     *
     * @param string $string
     * @return string
     */
    public static function shtmlspecialchars($string)
    {
        if (is_array($string)) {
            foreach ($string as $key => $val) {
                $string[$key] = self::shtmlspecialchars($val);
            }
        } else {
            $string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4})|[a-zA-Z][a-z0-9]{2,5});)/', '&\\1', str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string));
        }
        return $string;
    }

    /**
     * 格式化文件大小
     *
     * @param int $size
     * @return string
     */
    public static function sizeCount($size)
    {
        if ($size >= 1073741824) {
            $size = round($size / 1073741824 * 100) / 100 . ' GB';
        } elseif ($size >= 1048576) {
            $size = round($size / 1048576 * 100) / 100 . ' MB';
        } elseif ($size >= 1024) {
            $size = round($size / 1024 * 100) / 100 . ' KB';
        } else {
            $size = $size . ' Bytes';
        }
        return $size;
    }

    /**
     * 计算字符串长度
     *
     * @param string $str
     * @return string
     */
    public static function strlen($str)
    {
        return (strlen($str) + mb_strlen($str, 'UTF8')) / 2;
    }


    /**
     * 计算字符串长度(中文算一个)
     *
     * @param string $str
     * @return string
     */
    public static function stringLen($str)
    {
        return (mb_strlen($str, 'UTF8'));
    }

    /**
     * 转换为 UTF-8 编码
     *
     * @param string $string
     * @return string
     */
    public static function strToUtf8($string)
    {
        $encode = mb_detect_encoding($string, array("ASCII", "UTF-8", "GB2312", "GBK", "BIG5"));
        if ($encode != "UTF-8" && ! empty($string)) {
            $string = iconv($encode, "UTF-8//TRANSLIT//IGNORE", $string);
        }
        return $string;
    }

    /**
     * 特殊的算法：计算字符串长度（囧哥）
     *
     * @param string $str
     * @param int $type
     *      1=>英文1个字符，中文1个字符
     *      2=>英文1个字符，中文2个字符
     *      3=>英文0.5个字符(出现小数四舍五入)，中文1个字符
     *      4=>英文0.5个字符(出现小数, 去除小数)，中文1个字符
     * @param bool $isRound
     * @return int
     */
    public static function strlenJong($str, $type = 1, $isRound = true, $len = 0)
    {
        $enNum = 1;
        $cnNum = 1;
        if ($type == 2) {
            $enNum = 1;
            $cnNum = 2;
        } elseif ($type == 3) {
            $enNum = 0.5;
            $cnNum = 1;
        } elseif ($type == 4) {
            $enNum = 0.5;
            $cnNum = 1;
        }

        $strLen = 0;
        for ($i = 0; $i < strlen($str); $i++) {
            if (intval(bin2hex($str[$i]), 16) < 0x80) {
                $strLen += $enNum;
            } else {
                $strLen += $cnNum;
                $i += 2;
            }
            if ($len && $strLen > $len) {
                return true;
            }
        }

        if ($isRound) {
            if ($type == 3) {
                $strLen = round($strLen);
            } elseif ($type == 4) {
                $strLen = floor($strLen);
            }
        }

        return $strLen;
    }

    /**
     * 截取字符串（囧哥）
     *
     * @param string $str
     * @param int $len
     * @param string $dot
     * @param int $type
     *      1=>英文1个字符，中文1个字符
     *      2=>英文1个字符，中文2个字符
     *      3=>英文0.5个字符(出现小数四舍五入)，中文1个字符
     *      4=>英文0.5个字符(出现小数, 去除小数)，中文1个字符
     * @return string
     */
    public static function cutJong($str, $len, $dot = '...', $type = 1)
    {
        $enNum = 1;
        $cnNum = 1;
        if ($type == 2) {
            $enNum = 1;
            $cnNum = 2;
        } elseif ($type == 3) {
            $enNum = 0.5;
            $cnNum = 1;
        } elseif ($type == 4) {
            $enNum = 0.5;
            $cnNum = 1;
        }

        $isMoreChar = self::getStrLen($str, $type, true, $len);
        if ($isMoreChar === true) {
            if ($dot) {
                $len -= self::getStrLen($dot, $type, false);
            }
        } else {
            return $str;
        }

        $strLen  = 0;
        $cutWord = '';
        for ($i = 0; $i < strlen($str); $i++) {
            if (intval(bin2hex($str[$i]), 16) < 0x80) {
                $cutWord .= $str[$i];
                $strLen += $enNum;
            } else {
                $cutWord .= $str[$i] . $str[$i + 1] . $str[$i + 2];
                $strLen += $cnNum;
                $i += 2;
            }

            if ($strLen >= $len) {
                if ($strLen > $len && $type == 4 && intval(bin2hex($str[$i]), 16) >= 0x80) {
                    $cutWord = substr($cutWord, 0, strlen($cutWord) - 3);
                }
                $cutWord .= $dot;
                break;
            }
        }

        return $cutWord;
    }
}