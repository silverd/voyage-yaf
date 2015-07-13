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

        // 解决 Redis 队列 blpop 阻塞超时问题：read error on connection
        ini_set('default_socket_timeout', -1);

        // 不输出SQL调试信息
        // 防止本地浏览器模式运行时header超大
        Com_DB::enableLogging(false);

        if (! $this->_request->isCli() && ! isEnv('devel')) {
            // 开发环境下允许用URL访问执行
            throw new Core_Exception_403('必须在命令行模式下运行本脚本');
        }
    }

    public function log($method, $content)
    {
        if (is_array($content)) {
            $content = var_export($content, true);
        }

        $fileName = str_replace(array('::', 'Controller_', 'Action'), array('-', '', ''), $method);

        return Com_Logger_File::info('cli-log-' . $fileName, $content);
    }
}