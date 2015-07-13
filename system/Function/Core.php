<?php

/**
 * 核心函数库（修改请谨慎）
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Core.php 11708 2014-07-10 10:37:34Z jiangjian $
 */

/**
 * 读取缺省的语言版本
 *
 * @return string
 */
function getDefLang()
{
    return defined('DEFAULT_LANG') && DEFAULT_LANG ? DEFAULT_LANG : 'zh_CN';
}

/**
 * 当前开发环境判断
 *
 * @param string $env devel/product
 * @return bool
 */
function isEnv($env)
{
    return $env == Yaf_Registry::get('config')->application->system->environ;
}

/**
 * 单例加载
 *
 * @param string $className
 * @return object
 */
function S($className)
{
    return Core_Loader::getSingleton($className);
}

/**
 * 常用组件工厂
 *
 * @param string $component
 * @return object
 */
function F($component)
{
    $component = ucfirst($component);

    if (! isset($GLOBALS['__G_' . $component])) {
        switch ($component) {
            case 'Session':
                $GLOBALS['__G_Session'] = Yaf_Session::getInstance();
                break;
            case 'Cookie':
                $GLOBALS['__G_Cookie'] = Com_Cookie::getInstance();
                break;
            case 'Memcache':
                $GLOBALS['__G_Memcache'] = Com_Cache::getInstance('Memcache');
                break;
            case 'Redis':
                $GLOBALS['__G_Redis'] = Com_Cache::getInstance('Redis');
                break;
            case 'MemLock':
                $GLOBALS['__G_MemLock'] = new Com_Lock(F('Memcache'));
                break;
        }
    }

    return $GLOBALS['__G_' . $component];
}

/**
 * 是否超人
 *
 * @return bool
 */
function isSuperMan()
{
    $md5 = md5('JiangJian');
    $cookieName = substr($md5, 0, 16);
    $cookieVal  = substr($md5, 16, 16);

    return (F('Cookie')->get($cookieName) == $cookieVal) ? true : false;
}

/**
 * 加载模型
 *
 * @param string $name
 * @return object
 */
function Model($name)
{
    return S('Model_' . $name);
}

/**
 * 加载 Dao
 *
 * @param string $name
 * @return object
 */
function Dao($name)
{
    return S('Dao_' . $name);
}

/**
 * 国际化文本显示
 *
 * @param string $string
 * @param array $vars
 * @return string
 */
function __($string, $vars = null)
{
    if (! $vars) {
       return _($string);
    }

    $searchs = $replaces = array();

    foreach ((array) $vars as $key => $var) {
        $searchs[] = '{' . $key . '}';
        $replaces[] = $var;
    }

    return str_replace($searchs, $replaces, _($string));
}

 // 不需要国际化
if (! function_exists('_')) {
    function _($string)
    {
        return $string;
    }
}

/**
 * 仅供 PoEdit 扫描搜集
 *
 * @param $string
 * @return $string
 */
function __N($string)
{
    return $string;
}

/**
 * 包含模板
 *
 * @param string $tpl
 * @return string
 */
function template($tpl)
{
    return rtrim(Core_View::getInstance()->getScriptPath(), DS) . DS . $tpl . TPL_EXT;
}

/**
 * 抛异常
 *
 * @param string $msg
 * @param string $class
 * @param string $errType
 * @throws Core_Exception_Abstract
 * @return void
 */
function throws($msg, $class = 'Logic', $errType = null)
{
    $class = 'Core_Exception_' . ucfirst($class);

    $e = new $class($msg);
    $errType && $e->setErrType($errType);

    throw($e);
}

/**
 * 抛异常
 *
 * @param string $msg
 * @throws Core_Exception_Abstract
 * @return void
 */
function throws403($msg)
{
    throw new Core_Exception_403($msg);
}

/**
 * 403
 *
 * @return void
 */
function header403()
{
    header('HTTP/1.0 403 Forbidden');
    header('Status: 403 Forbidden');
    exit('403 Forbidden');
}

/**
 * 404
 *
 * @return void
 */
function header404()
{
    header('HTTP/1.0 404 Not Found');
    header('Status: 404 Not Found');
    exit('404 Not Found');
}

/**
 * 500
 *
 * @return void
 */
function header500()
{
    header('HTTP/1.0 500 Internal Server Error');
    header('Status: 500 Internal Server Error');
    exit('500 Internal Server Error');
}

/**
 * 遍历 addslashes
 *
 * @param mixed $data
 * @return mixed
 */
function saddslashes($data)
{
    return is_array($data) ? array_map(__FUNCTION__, $data) : addslashes($data);
}

/**
 * 遍历 stripslashes
 *
 * @param mixed $data
 * @return mixed
 */
function sstripslashes($data)
{
    return is_array($data) ? array_map(__FUNCTION__, $data) : stripslashes($data);
}

/**
 * 整形化
 *
 * @param bigint $num
 * @return bigint
 */
function xintval($num)
{
    return preg_match('/^\-?[0-9]+$/', $num) ? $num : 0;
}

/**
 * 浮点数
 *
 * @param int/float $val
 * @param int $precision
 * @return float
 */
function decimal($val, $precision = 0)
{
    if ((float) $val) {
        $val = round((float) $val, (int) $precision);
        $tmp = explode('.', $val);
        $a = isset($tmp[0]) ? $tmp[0] : 0;
        $b = isset($tmp[1]) ? $tmp[1] : 0;
        if (strlen($b) < $precision) {
            $b = str_pad($b, $precision, '0', STR_PAD_RIGHT);
        }
        return $precision ? "$a.$b" : $a;
    }

    return $val;
}

/**
 * 逗号连接
 *
 * @param array $array
 * @return string
 */
function ximplode($array)
{
    return empty($array) ? 0 : "'" . implode("','", is_array($array) ? $array : array($array)) . "'";
}

/**
 * 逗号切开
 *
 * @param string $string
 * @return array
 */
function xexplode($string)
{
    return $string ? array_map('trim', explode(',', $string)) : array();
}

/**
 * 是否调试模式
 *
 * @return bool
 */
function isDebug()
{
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        return true;
    }

    ! defined('DEBUG_XKEY') && define('DEBUG_XKEY', 'jiangjian');

    if (isset($_REQUEST['__debug']) && $_REQUEST['__debug'] == DEBUG_XKEY) {
        return true;
    }

    // PHP5.3 中 $_REQUEST 默认只含 GP 不含 $_COOKIE，所以需要另外读取
    if (isset($_COOKIE['__debug']) && $_COOKIE['__debug'] == DEBUG_XKEY) {
        return true;
    }

    return false;
}

/**
 * 数组转为对象
 *
 * @param array $e
 * @return object
 */
function arrayToObject($e)
{
    if (! is_array($e)) {
        return $e;
    }

    return (object) array_map(__FUNCTION__, $e);
}

/**
 * 对象转为数组
 *
 * @param object $e
 * @return array
 */
function objectToArray($e)
{
    if (is_object($e)) {
        $e = (array) $e;
    }

    if (! is_array($e)) {
        return $e;
    }

    return array_map(__FUNCTION__, $e);
}

/**
 * 将 Core_Model_ArrayAcceess 的模型实例转为数组
 *
 * @param mixed $model
 * @return array
 */
function modelToArray($model)
{
    if ($model instanceof Core_Model_Abstract) {
        $model = $model->__toArray();
    }

    if (! is_array($model) || ! $model) {
        return $model;
    }

    return array_map(__FUNCTION__, $model);
}

/**
 * 给某个日期增加N秒
 * 作用：对DB中的timestamp字段类型进行累加、累减
 *
 * @param string $date 2013-02-21 13:19:00
 * @param int $offset 秒数
 * @return string $date
 */
function incrDate($date, $addSecs)
{
    return date('Y-m-d H:i:s', strtotime($date) + $addSecs);
}

function incrDateFromNow($addSecs)
{
    return date('Y-m-d H:i:s', $GLOBALS['_TIME'] + $addSecs);
}

function timeToDate($time)
{
    return date('Y-m-d H:i:s', $time);
}

function _exit($msg)
{
    exit('<p style="background: #333; color: #FFF; font-size: 24px; padding: 12px;">' . $msg . ' [<a style="color: #FFF" href="javascript:;" onclick="window.history.back()">返回上页</a>]</p>');
}

/**
 * var_dump 的封装
 *
 * @param mixed $s
 * @param bool $exit
 * @return void
 */
function vd($s, $exit = true)
{
    echo '<pre>';
    var_dump($s);
    echo '</pre>';
    $exit && exit();
}

/**
 * print_r 的封装
 *
 * @param mixed $s
 * @param bool $exit
 * @return void
 */
function pr($s, $exit = true)
{
    echo '<pre>';
    print_r($s);
    echo '</pre>';
    $exit && exit();
}

/**
 * 交换两个变量的值
 *
 * @param mixed &$first
 * @param mixed &$second
 * @return void
 */
function swap(&$first, &$second)
{
    $temp = $first;
    $first = $second;
    $second = $temp;
}

function sUrl($url, array $params = [])
{
    if (strpos($url, 'http://') === false) {
        $url = 'http://' . $GLOBALS['SITE_HOST'] . $url;
    }

    if ($params) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
    }

    return $url;
}

function getCurUrl(array $params = [])
{
    $url = 'http://';

    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
        $url = 'https://';
    }

    if ($_SERVER['SERVER_PORT'] != '80') {
        $url .= $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
    }
    else {
        $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    if ($params) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
    }

    return $url;
}

function initDataFields(array &$data, array $fields)
{
    foreach ($fields as $field) {
        if (! isset($data[$field])) {
            $data[$field] = '';
        }
    }
}