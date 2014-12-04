<?php

/**
 * XCache 缓存封装类
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: XCache.php 12 2012-11-19 01:29:52Z jiangjian $
 */

class Com_Cache_XCache
{
    /**
     * 构造函数
     * 检测XCache扩展是否开启
     */
    public function __construct()
    {
        if (! extension_loaded('xcache')) {
            throw new Exception('The xcache extension must be loaded.');
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
        return xcache_set($key, $value, $ttl);
    }

    /**
     * 获取一个已经缓存的变量
     *
     * @param string $key  缓存Key
     * @return mixed       缓存内容
     * @access public
     */
    public function get($key)
    {
        return xcache_get($key);
    }

    /**
     * 删除一个已经缓存的变量
     *
     * @param  string $key   缓存Key
     * @return boolean       是否删除成功
     * @access public
     */
    public function del($key)
    {
        return xcache_unset($key);
    }

    /**
     * 删除全部缓存变量
     *
     * @return boolean       是否删除成功
     * @access public
     */
    public function delAll()
    {
        return xcache_clear_cache(XC_TYPE_VAR, 0);
    }

    /**
     * 检测是否存在对应的缓存
     *
     * @param string $key   缓存Key
     * @return boolean      是否存在key
     * @access public
     */
    public function has($key)
    {
        return xcache_isset($key);
    }
}