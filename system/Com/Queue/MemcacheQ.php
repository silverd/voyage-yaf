<?php

/**
 * MemcacheQ
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: MemcacheQ.php 7896 2014-01-10 18:29:27Z jiangjian $
 */

class Com_Queue_MemcacheQ implements Com_Queue_Interface
{
    /**
     * 连接实例
     *
     * @var Com_Cache_Memcache
     */
    private $_memcache;

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
            throw new Core_Exception_Fatal('没有为 MemcacheQ 队列 ' . $queue . ' 指定 Memcache 分组，请检查 queue.conf.php');
        }

        // 连接实例
        $this->_memcache = new Com_Cache_Memcache($config['module']);

        // 初始化键名
        $this->_pushedCountKey = 'Queue:' . strtoupper($queue) . ':PushedCount'; // 已压进元素数
        $this->_popedCountKey  = 'Queue:' . strtoupper($queue) . ':PopedCount';  // 已弹出元素数
        $this->_queueDataKey   = 'Queue:' . strtoupper($queue) . ':Data';        // 队列数据前缀
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

        $pushed = intval($this->_memcache->get($this->_pushedCountKey));

        // 压进
        $key = $this->_queueDataKey . ':' . $pushed;
        if (! $this->_memcache->set($key, $value)) {
            return false;
        }

        // 累加已压进了几个元素
        if (! $this->_memcache->increment($this->_pushedCountKey)) {
            $this->_memcache->set($this->_pushedCountKey, 1);
        }

        return true;
    }

    public function pop()
    {
        $poped = intval($this->_memcache->get($this->_popedCountKey));

        // 弹出
        $key = $this->_queueDataKey . ':' . $poped;
        $value = $this->_memcache->get($key);

        // 如队列已全部弹出，则跳出
        if ($value === false) {
            return false;
        }

        $this->_memcache->delete($key);

        // 累加弹出了几个元素
        if (! $this->_memcache->increment($this->_popedCountKey)) {
            $this->_memcache->set($this->_popedCountKey, 1);
        }

        return $value;
    }

    /**
     * 返回队列当前长度
     *
     * @return int
     */
    public function count()
    {
        $pushed = intval($this->_memcache->get($this->_pushedCountKey));
        $poped  = intval($this->_memcache->get($this->_popedCountKey));
        return max(0, $pushed - $poped);
    }

    /**
     * 清空队列
     *
     * @return void
     */
    public function clear()
    {
        $this->_iterate(function ($key, $value) {
            $this->_memcache->delete($key);
        });

        $this->_memcache->delete($this->_pushedCountKey);
        $this->_memcache->delete($this->_popedCountKey);
    }

    /**
     * 获取队列所有剩余元素
     *
     * @return array
     */
    public function view()
    {
        $list = array();

        $this->_iterate(function ($key, $value) {
            $list[] = $value;
        });

        return $list;
    }

    /**
     * 遍历队列中的剩余元素
     *
     * @param $callback
     * @return void
     */
    private function _iterate($callback)
    {
        $pushed = intval($this->_memcache->get($this->_pushedCountKey));
        $poped  = intval($this->_memcache->get($this->_popedCountKey));

        for (; $poped < $pushed; $poped++) {

            $key   = $this->_queueDataKey . ':' . $poped;
            $value = $this->_memcache->get($key);

            $callback($key, $value);
        }
    }
}