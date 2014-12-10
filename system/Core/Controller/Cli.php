<?php

/**
 * Cli 控制器抽象父类
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Cli.php 12347 2014-12-10 03:39:27Z jiangjian $
 */

abstract class Core_Controller_Cli extends Core_Controller_Abstract
{
    /**
     * 不加载视图（请勿修改）
     *
     * @var bool
     */
    public $yafAutoRender = false;

    public function init()
    {
        parent::init();

        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        // 不输出SQL调试信息
        // 防止本地浏览器模式运行时header超大
        Com_DB::enableLogging(false);

        if (! $this->_request->isCli() && ! isEnv('devel')) {
            // 开发环境下允许用URL访问执行
            throw new Core_Exception_403('必须在命令行模式下运行本脚本');
        }
    }

    public function log($method, $string)
    {
        if (is_array($string)) {
            $string = var_export($string, true);
        }

        $fileName = str_replace(array('::', 'Controller_', 'Action'), array('-', '', ''), $method);

        Com_Log::write('cronlog-' . $fileName, $string);
    }
}