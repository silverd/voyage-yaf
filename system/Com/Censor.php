<?php

/**
 * 敏感词过滤
 *
 * @author Wangxudong
 * @modifier JiangJian <silverd@sohu.com>
 * $Id: Censor.php 10101 2014-03-27 09:18:17Z jiangjian $
 */

class Com_Censor
{
    private $_dict = array();

    /**
     * 初始化词典
     *
     * @param array $keywords 一维数组
     */
    public function __construct(array $keywords)
    {
        foreach ($keywords as $keyword) {
            $key = substr($keyword, 0, 2);
            $this->_dict[$key]['list'][] = $keyword;
            $this->_dict[$key]['max']    = isset($this->_dict[$key]['max']) ? max($this->_dict[$key]['max'], strlen($keyword)) : strlen($keyword);
        }
    }

    /**
     * 过滤
     *
     * @param string $resource
     * @return string
     */
    public function filter($resource)
    {
        $result = '';

        $len = strlen($resource);
        for ($i = 0; $i < $len; $i++) {
            $key = substr($resource, $i, 2);
            if (isset($this->_dict[$key])) {
                $result .= $this->_deal(substr($resource, $i, $this->_dict[$key]['max']), $key, $af);
                $i += $af;
            } else {
                $result .= substr($resource, $i, 1);
            }
        }

        return $result;
    }

    /**
     * 匹配到了关键字时的处理
     *
     * @param string $res 源字符串
     * @param array  $key 关键字数组
     * @param int $af
     */
    private function _deal($res, $key, &$af)
    {
        $af = 0;

        foreach ($this->_dict[$key]['list'] as $keyword) {
            if (stripos($res, $keyword) !== false) {
                $len = strlen($keyword);
                $af  = $len - 1;
                return str_repeat('*', ceil($len / 3));
            }
        }

        return substr($res, 0, 1);
    }
}