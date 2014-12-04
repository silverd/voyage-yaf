<?php

/**
 * Cli 控制器抽象父类
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Cli.php 9876 2014-03-15 16:29:06Z jiangjian $
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