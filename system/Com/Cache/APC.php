<?php

/**
 * APC 缓存封装类
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: APC.php 12 2012-11-19 01:29:52Z jiangjian $
 */

class Com_Cache_APC
{
    /**
     * 构造函数
     * 检测APC扩展是否开启
     */
    public function __construct()
    {
        if (! extension_loaded('apc')) {
            throw new Exception('The apc extension must be loaded.');
        }
    }

    /**
     * 设置一个缓存变量
     *
     * @param string $key    缓存Key
     * @param mixed $value   缓存内容
     * @param int $ttl       缓存时间(秒)
     * @return boolean       是否缓存成功
     */
    public function set($key, $value, $ttl = 60)
    {
        return apc_store($key, $value, $ttl);
    }

    /**
     * 获取一个已经缓存的变量
     *
     * @param string $key  缓存Key
     * @return mixed       缓存内容
     */
    public function get($key)
    {
        return apc_fetch($key);
    }

    /**
     * 删除一个已经缓存的变量
     *
     * @param  string $key   缓存Key
     * @return boolean       是否删除成功
     */
    public function del($key)
    {
        return apc_delete($key);
    }

    /**
     * 删除全部缓存变量
     *
     * @return boolean       是否删除成功
     */
    public function delAll()
    {
        return apc_clear_cache();
    }

    /**
     * 检测是否存在对应的缓存
     *
     * @param string $key   缓存Key
     * @return boolean      是否存在key
     */
    public function has($key)
    {
        return (apc_fetch($key) === false ? false : true);
    }
}