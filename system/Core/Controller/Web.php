<?php

/**
 * Web 控制器抽象父类
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Web.php 7047 2013-11-25 02:51:14Z jiangjian $
 */

abstract class Core_Controller_Web extends Core_Controller_Abstract
{
    /**
     * 自动加载视图
     *
     * @var bool
     */
    public $yafAutoRender = true;

    /**
     * 用于生成 formHash 以及URL加密的用户唯一值
     * 可以是 uid/user_code
     *
     * @var string
     */
    protected $_uniqUserKey = null;

    /**
     * 传出模板变量
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function assign($key, $value = null)
    {
        return $this->_view->assign($key, $value);
    }

    /**
     * 设置模板布局
     *
     * @param string $layout
     */
    public function setLayout($layout = null)
    {
        $this->_view->setLayout($layout);
    }

    /**
     * 是否自动渲染视图文件
     *
     * @param bool $bool
     */
    public function autoRender($bool = true)
    {
        $this->yafAutoRender = (bool) $bool;
    }

    public function alert($msg, $resultType = 'success', $url = '', $extra = '')
    {
        if (is_array($msg)) {
            $msg = implode('\n', $msg);
        }

        // Ajax
        if ($this->isAjax()) {
            $this->jsonx($msg, $resultType);
        }

        // 跳转链接
        if ($url == 'halt') {
            $jumpStr = '';
        } else {
            $url = $url ? $url : $this->refer();
            $url = $url ? $url : '/';
            $jumpStr = $url ? "top.location.href = '{$url}';" : '';
        }

        $this->js("top.alert('{$msg}'); {$extra} {$jumpStr}");
    }

    public function js($script, $exit = true)
    {
        echo('<script type="text/javascript">' . $script . '</script>');
        $exit && exit();
    }

    public function jump($url = '')
    {
        $url = $url ?: $this->refer();
        $this->js('top.location.href = \'' . $url . '\';');
    }

    public function refer()
    {
        return $this->getx('refer') ?: (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
    }

    /**
     * 获取当前用户盐
     *
     * @param string $extraKey 额外密钥
     * @return string
     */
    public function getSalt($extraKey = null)
    {
        // 今天凌晨5点以后~第二天凌晨5点前
        // 日期标示符为当天
        if (date('G') >= 5) {
            $dateString = date('md');
        }
        // 今天凌晨5点前
        // 日期标示符仍用昨天的
        else {
            $dateString = date('md', strtotime('-1 day'));
        }

        return md5('VoyageYAF:' . $dateString . ':' . $this->_uniqUserKey . ':' . $extraKey);
    }

    /**
     * 加密一个字符串
     *
     * @param string $content 待加密内容
     * @param string $extraKey 额外密钥
     * @return string
     */
    public function encrypt($content, $extraKey = null)
    {
        return Helper_Cryption_Rijndael::encrypt($content, $this->getSalt($extraKey));
    }

    /**
     * 加密一维数组的值
     *
     * @param array $array 待加密的数组
     * @param string $extraKey 额外密钥
     * @return array
     */
    public function encrypts(array $array, $extraKey = null)
    {
        if ($array) {
            foreach ($array as &$value) {
                $value = $this->encrypt($value, $extraKey);
            }
        }

        return $array;
    }

    /**
     * 解密一个字符串
     *
     * @param string $content 待解密内容
     * @param string $extraKey 额外密钥
     * @return string
     */
    public function decrypt($content, $extraKey = null)
    {
        return Helper_Cryption_Rijndael::decrypt($content, $this->getSalt($extraKey));
    }

    /**
     * 加密指定一行的某些字段
     *
     * @param array/object $row
     * @param string/array $idFields 待加密字段名，可设多个
     * @param string $extraKey 额外密钥
     * @return array
     */
    public function encryptId($row, $idFields = 'id', $extraKey = null, $overwrite = false)
    {
        if ($row) {
            foreach ((array) $idFields as $idField) {
                $result = $this->encrypt($row[$idField], $extraKey);
                if ($overwrite) {
                    // 把加密后的数据覆盖原字段值
                    $row[$idField] = $result;
                } else {
                    // 不覆盖原字段值，增加前置双下划线以区分
                    $row['__' . $idField] = $result;
                }
            }
        }

        return $row;
    }

    /**
     * 批量加密指定列表的某些字段
     *
     * @param array $list
     * @param string/array $idFields 待加密字段名，可设多个
     * @param string $extraKey 额外密钥
     * @return array
     */
    public function encryptIds($list, $idFields = 'id', $extraKey = null, $overwrite = false)
    {
        if ($list) {
            foreach ($list as &$row) {
                $row = $this->encryptId($row, $idFields, $extraKey, $overwrite);
            }
        }

        return $list;
    }

    /**
     * 批量加密指定列表的KEY下标
     *
     * @param array $list
     * @param string $extraKey 额外密钥
     * @return array
     */
    public function encryptKeys($list, $extraKey = null)
    {
        $newList = array();

        if ($list) {
            foreach ($list as $key => $value) {
                $__key = $this->encrypt($key, $extraKey);
                $newList[$__key] = $value;
            }
        }

        return $newList;
    }

    /**
     * 实时加密URL中的指定参数（们）
     * URL格式为: http://...../?id=[encrypt:原始ID]
     *
     * @param string $url
     * @return string
     */
    public function encryptUrl($url)
    {
        if (strpos($url, '[encrypt:') === false) {
            return $url;
        }

        return preg_replace_callback('/\[encrypt\:(.+?)\]/', array($this, '_encryptUrlCallback'), $url);
    }

    protected function _encryptUrlCallback($matches)
    {
        return $matches ? rawurlencode($this->encrypt($matches[1])) : '';
    }

    // 生成新的 formHash
    public function refreshFormHash($formName)
    {
        // 生成随机码
        $formHash = $this->getSalt(uniqid(mt_rand(1, 10000)));

        // 将 formHash 写入到 Memcache 中
        $cacheKey = md5('formHash:' . $formName . ':' . $this->_uiduniqUserKey);
        F('Memcache')->set($cacheKey, $formHash);

        // 传出到模板中
        $this->assign('formHash', $formHash);

        return $formHash;
    }

    /**
     * 验证 formHash 是否正确
     *
     * @param string $formName
     * @param mixed $formHashGet
     * @param bool $clear 是否取完即清除
     * @return bool
     */
    public function verifyFormHash($formName, $formHashGet = null, $clear = true)
    {
        if ($formHashGet === null) {
            $formHashGet = $this->getx('hash');
        }

        if (! $formHashGet) {
            return false;
        }

        // 从 Memcache 中读取 formHash
        $cacheKey = md5('formHash:' . $formName . ':' . $this->_uiduniqUserKey);

        // 取完即清除（formHash只能用一次）
        $formHash = F('Memcache')->get($cacheKey);
        $clear && F('Memcache')->delete($cacheKey);

        return $formHash == $formHashGet ? true : false;
    }

    public function getDx($key)
    {
        if (! $value = $this->getx($key)) {
            return null;
        }

        $value = $this->decrypt($value);

        header('X-Rijndael-' . $key . ':' . $value);

        return $value;
    }

    public function getDxs($key)
    {
        if (! $value = $this->get($key)) {
            return null;
        }

        $values = is_array($value) ? array_filter(array_map(array($this, 'decrypt'), $value)) : $this->decrypt($value);

        header('X-Rijndael-' . $key . ':' . implode(',', $values));

        return $values;
    }

    public function redirect($url)
    {
        parent::redirect($url);
        exit();
    }
}