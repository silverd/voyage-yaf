<?php

/**
 * 控制器抽象父类
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Abstract.php 11672 2014-07-09 09:29:49Z jiangjian $
 */

abstract class Core_Controller_Abstract extends Yaf_Controller_Abstract
{
    public function init()
    {
        Yaf_Registry::set('controller', $this);
    }

    public function get($key)
    {
        return $this->_request->get($key);
    }

    public function getx($key)
    {
        $value = $this->_request->get($key);
        return Helper_String::deepFilterDatasInput($value);
    }

    public function getInt($key)
    {
        return intval($this->_request->get($key));
    }

    public function getInts($key)
    {
        $value = $this->get($key);
        return is_array($value) ? array_filter(array_map('intval', $value)) : intval($value);
    }

    public function getBool($key)
    {
        return $this->_request->get($key) ? true : false;
    }

    public function isGet()
    {
        return $this->_request->isGet();
    }

    public function isPost()
    {
        return $this->_request->isPost();
    }

    public function isSubmit()
    {
        return $this->isPost() || $this->getQuery('submit');
    }

    public function isAjax()
    {
        return $this->_request->isXmlHttpRequest() || $this->getBool('is_ajax');
    }

    public function getQuery($key = null, $default = null)
    {
        return $this->_request->getQuery($key, $default);
    }

    public function getPost($key = null, $default = null)
    {
        return $this->_request->getPost($key, $default);
    }

    public function getQueryx($key = null, $default = null)
    {
        return Helper_String::deepFilterDatas($this->getQuery($key, $default), array('strip_tags', 'trim'));
    }

    public function getPostx($key = null, $default = null)
    {
        return Helper_String::deepFilterDatas($this->getPost($key, $default), array('strip_tags', 'trim'));
    }

    public function getParam($key = null, $default = null)
    {
        return $this->_request->getParam($key, $default);
    }

    public function getParams()
    {
        return $this->_request->getParams();
    }

    public function getParamsx()
    {
        return Helper_String::deepFilterDatas($this->getParams(), array('strip_tags', 'trim'));
    }

    // $_GET + $_POST
    public function getQueryPostx($key = null, $default = null)
    {
        return array_merge($this->getQueryx($key, $default), $this->getPostx($key, $default));
    }

    public function getBaseUri()
    {
        return '/' . lcfirst($this->_request->getControllerName()) . '/' . $this->_request->getActionName();
    }

    public function isBaseUri($uris)
    {
        if (! is_array($uris)) {
            $uris = array($uris => 1);
        }

        $baseUri = strtolower(str_replace('-', '', $this->getBaseUri()));

        return isset($uris[$baseUri]) ? true : false;
    }

    public function isActions($actions)
    {
        if (! is_array($actions)) {
            $actions = array($actions);
        }

        $actions = array_map('strtolower', $actions);

        return in_array($this->_request->getActionName(), $actions) ? true : false;
    }

    // 设置当前语言环境
    public function setLocale($lang = null)
    {
        if (null === $lang) {
            $lang = $this->getLocale();
        }

        // 设置环境变量
        putenv('LANG=' . $lang);
        putenv('LC_ALL=' . $lang);

        // 设置场景信息
        setlocale(LC_ALL, $lang);

        // 设置要绑定的语言包的目录
        bindtextdomain(CUR_TEXT_DOMAIN, LOCALE_PATH);
        bind_textdomain_codeset(CUR_TEXT_DOMAIN, 'UTF-8');

        // 设置默认的包
        textdomain(CUR_TEXT_DOMAIN);

        // 当前语言版本
        define('CUR_LANG', $lang);

        // CSS存放目录（全局）
        define('PUBLIC_CSS_DIR', CDN_PATH . 'public/css');

        // CSS存放目录（当前语言版本）
        define('LOCALE_CSS_DIR', CDN_PATH . CUR_LANG . '/css');

        // 图片存放目录（全局）
        define('PUBLIC_IMG_DIR', CDN_PATH . 'public/img');

        // 图片存放目录（当前语言版本）
        define('LOCALE_IMG_DIR', CDN_PATH . CUR_LANG . '/img');

        // JS存放目录（全局）
        define('PUBLIC_JS_DIR',  CDN_PATH . 'public/js');

        // JS存放目录（当前语言版本）
        define('LOCALE_JS_DIR',  CDN_PATH . CUR_LANG . '/js');
    }

    // 获取当前语言
    public function getLocale()
    {
        // 优先从URL参数中读取
        if ($curLang = $this->getQueryx('lang')) {
            F('Cookie')->set('__lang', $curLang);
        }
        // 如果没有则从cookie中读取
        else {
            $curLang = F('Cookie')->get('__lang');
        }

        $supportLangs = explode(',', SUPPORT_LANGS);

        if (! $curLang || ! in_array($curLang, $supportLangs)) {
            $curLang = $supportLangs[0];
        }

        return $curLang;
    }

    public function json($output)
    {
        header('Content-type: text/json');
        header('Content-type: application/json; charset=UTF-8');
        exit(json_encode($output));
    }

    /**
     * 用于 AJAX 响应输出 JSON
     *
     * @param string $msg
     * @param string $resultType success|error|warnings|tips
     * @param array $extra
     * @param bool $obClean 是否先清除之前的缓冲区
     */
    public function jsonx($msg, $resultType = 'success', array $extra = array(), $obClean = false)
    {
        // 清除之前的缓冲区，防止多余输出
        $obClean && ob_clean();

        if ($globalExtra = Yaf_Registry::get('jsonRespExtra')) {
            $extra = array_merge($globalExtra, $extra);
        }

        $output = array('msg' => $msg, 'status' => $resultType);

        if ($extra) {
            $output['extra'] = $extra;
        }

        $this->json($output);
    }

    public function setJsonExtra(array $extra)
    {
        return Yaf_Registry::set('jsonRespExtra', $extra);
    }
}