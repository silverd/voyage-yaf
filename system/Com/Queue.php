<?php

/**
 * 队列经理人
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Queue.php 12 2012-11-19 01:29:52Z jiangjian $
 */

class Com_Queue
{
    /**
     * 单例模式
     *
     * @var array
     */
    private static $_instances = array();

    /**
     * 获取队列实例
     *
     * @param string $queue 队列名
     * @return object Com_Queue_*
     */
    public static function getInstance($queue)
    {
        if (! isset(self::$_instances[$queue])) {

            $config = Core_Config::loadEnv('queue');

            if (! isset($config[$queue]) || ! isset($config[$queue]['class'])) {
                throw new Core_Exception_Fatal('队列 ' . $queue . ' 配置有误，请检查 queue.conf.php');
            }

            $className = 'Com_Queue_' . $config[$queue]['class'];

            self::$_instances[$queue] = new $className($queue, $config[$queue]);
        }

        return self::$_instances[$queue];
    }
}