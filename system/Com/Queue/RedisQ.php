<?php

/**
 * RedisQ
 *
 * @author Elaine Yu <14354475@qq.com>
 * @modifier JiangJian <silverd@sohu.com>
 * $Id: RedisQ.php 7896 2014-01-10 18:29:27Z jiangjian $
 */

class Com_Queue_RedisQ implements Com_Queue_Interface
{
    /**
     * 连接实例
     *
     * @var Com_Cache_Redis
     */
    private $_redis;

    /**
     * Redis 链表名
     *
     * @var string
     */
    private $_listName;

    /**
     * 构造函数
     *
     * @param string $queue 队列名
     * @param array $config 配置数组
     */
    public function __construct($queue, array $config)
    {
        if (! $queue) {
            throw new Core_Exception_Fatal('队列名不能为空');
        }

        if (! isset($config['module'])) {
            throw new Core_Exception_Fatal('没有为 RedisQ 队列 ' . $queue . ' 指定 Redis 分组，请检查 queue.conf.php');
        }

        // 连接实例
        $this->_redis = new Com_Cache_Redis($config['module']);

        // 初始化 List 键名
        $this->_listName = 'Queue:' . strtoupper($queue);
    }

    /**
     * 向队列尾部追加一个元素
     *
     * @param string $value
     * @return bool
     */
    public function push($value)
    {
        if (! $value) {
            return false;
        }

        return $this->_redis->rpush($this->_listName, $value);
    }

    /**
     * 取出队列头部的第一个元素
     *
     * @return string
     */
    public function pop()
    {
        return $this->_redis->lpop($this->_listName);
    }

    /**
     * 返回队列当前长度
     *
     * @return int
     */
    public function count()
    {
        return $this->_redis->llen($this->_listName);
    }

    /**
     * 清空队列
     *
     * @return string
     */
    public function clear()
    {
        return $this->_redis->del($this->_listName);
    }

    /**
     * 获取队列所有剩余元素
     *
     * @return array
     */
    public function view()
    {
        return $this->_redis->lrange($this->_listName, 0, -1);
    }
}