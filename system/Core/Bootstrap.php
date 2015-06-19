<?php

/**
 * 框架初始化
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Bootstrap.php 7898 2014-01-11 05:29:41Z jiangjian $
 */

class Core_Bootstrap
{
    public static function init()
    {
        $self = new self();

        // 依次执行本类所有方法
        foreach (get_class_methods($self) as $method) {
            if ($method != __FUNCTION__) {  // 排除 init 方法本身
                $self->$method();
            }
        }
    }

    public function initGlobal()
    {
        // 定义路径常量
        if (! defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }

        define('WEB_PATH',      APP_PATH  . 'web'      . DS);
        define('TPL_PATH',      APP_PATH  . 'views'    . DS);
        define('DATA_PATH',     APP_PATH  . 'data'     . DS);
        define('CACHE_PATH',    DATA_PATH . 'cache'    . DS);
        define('LOCALE_PATH',   DATA_PATH . 'locale'   . DS);
        define('RESOURCE_PATH', DATA_PATH . 'resource' . DS);
        define('THRIFT_PATH',   DATA_PATH . 'thrift'   . DS . 'gen-php' . DS);

        // 系统、应用常量定义
        Yaf_Loader::import(CONF_PATH . 'system.php');
        Yaf_Loader::import(CONF_PATH . 'constant.php');

        // 设置编码
        header('Content-type: text/html; charset=UTF-8');

        // 设置时区
        date_default_timezone_set(CUR_TIMEZONE);

        // 性能测试 - 程序开始执行时间、消耗内存
        $GLOBALS['_START_TIME'] = microtime(true);
        $GLOBALS['_START_MEM']  = memory_get_usage();
        $GLOBALS['_TIME']       = $_SERVER['REQUEST_TIME'];
        $GLOBALS['_DATE']       = date('Y-m-d H:i:s');
        $GLOBALS['_SQLs']       = array();

        // 读取INI配置
        $config = Yaf_Application::app()->getConfig();

        // 文件日志目录
        define('LOG_PATH', $config->get('log_dir') . DS);

        // 把配置保存起来
        Yaf_Registry::set('config', $config);

        // 全局常量、函数等
        Yaf_Loader::import(SYS_PATH . 'Function/Core.php');
        Yaf_Loader::import(APP_PATH . 'library/Function/Core.php');
        Yaf_Loader::getInstance()->registerLocalNamespace(array('Dao', 'MyHelper'));

        // 全局过滤GPC
        if (! get_magic_quotes_gpc()) {
            $_GET && $_GET = saddslashes($_GET);
            $_POST && $_POST = saddslashes($_POST);
            $_COOKIE && $_COOKIE = saddslashes($_COOKIE);
            $_REQUEST && $_REQUEST = saddslashes($_REQUEST);
        }

        // 开启输出缓冲
        ob_start();
    }

    public function initDebugMode()
    {
        error_reporting(E_ALL | E_STRICT | E_DEPRECATED);

        // 调试、错误信息开关
        if (isDebug()) {
            ini_set('display_errors', 'On');
            // 打印调试信息
            // 非命令行模式才输出调试信息
            if (PHP_SAPI !== 'cli') {
                Yaf_Loader::import(SYS_PATH . 'Core/Debug.php');
                Yaf_Loader::import(SYS_PATH . 'Third/FirePHPCore/fb.php');
                register_shutdown_function(array('Core_Debug', 'firePHP'));
            }
            // XHProf 调试
            if (extension_loaded('xhprof')) {
                if (defined('XHPROF_DEGUG') && XHPROF_DEGUG) {
                    xhprof_enable();
                    register_shutdown_function(array('Third_XHProf_Output', 'getRunId'));
                }
            }
        } else {
            ini_set('display_errors', 'Off');
        }
    }

    /**
     * 将 XianglePHP 风格的 URI 转为 Yaf 风格
     * 例如：/index-test/hello-world => /indextest/helloworld
     */
    public function initRequestUri()
    {
        $request    = Yaf_Dispatcher::getInstance()->getRequest();
        $requestUri = $request->getRequestUri();

        if (strpos($requestUri, '-') !== false) {
            $request->setRequestUri(str_replace('-', '', $requestUri));
        }
    }
}