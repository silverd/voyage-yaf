<?php

/**
 * 文件缓存封装类
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: File.php 12 2012-11-19 01:29:52Z jiangjian $
 */

class Com_Cache_File
{
    /**
     * 缓存文件存储目录
     *
     * @var string
     */
    private $_saveDir;

    /**
     * 构造器
     */
    public function __construct()
    {
        $this->_saveDir = CACHE_PATH . 'Cache' . DIRECTORY_SEPARATOR . 'File' . DIRECTORY_SEPARATOR;
        @chmod($this->_saveDir, 0777);
        if (! is_writable($this->_saveDir)) {
            throw new Core_Exception_Fatal('缓存文件存储目录 ' . $this->_saveDir . ' 不可写');
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
        $file = $this->_saveDir . md5($key) . '.cache';
        if (file_put_contents($file, serialize($value), LOCK_EX)) {
            @touch($file, time() + $ttl);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取一个已经缓存的变量
     *
     * @param string $key  缓存Key
     * @return mixed       缓存内容
     */
    public function get($key)
    {
        $file = $this->_saveDir . md5($key) . '.cache';

        // 没有找到缓存
        if (! is_file($file)) {
            return false;
        }

        // 已过期（删除缓存）
        if (time() > filemtime($file)) {
            @unlink($file);
            return false;
        }

        return unserialize(file_get_contents($file));
    }

    /**
     * 删除一个已经缓存的变量
     *
     * @param  string $key   缓存Key
     * @return boolean       是否删除成功
     */
    public function del($key)
    {
        $file = $this->_saveDir . md5($key) . '.cache';
        return @unlink($file);
    }

    /**
     * 删除全部缓存变量
     *
     * @return boolean       是否删除成功
     */
    public function delAll()
    {
        $files = scandir($this->_saveDir);
        $files = array_diff($files, array('.', '..'));

        foreach ($files as $file) {
            @unlink($file);
        }

        return true;
    }

    /**
     * 检测是否存在对应的缓存
     *
     * @param string $key   缓存Key
     * @return boolean      是否存在key
     */
    public function has($key)
    {
        return (is_file($this->_saveDir . md5($key) . '.cache') === null ? false : true);
    }
}