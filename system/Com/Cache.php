<?php

/**
 * 缓存经理人
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Cache.php 7896 2014-01-10 18:29:27Z jiangjian $
 */

class Com_Cache
{
    /**
     * 使用的缓存类名
     *
     * @var string
     */
    private $_className;

    /**
     * 默认分组
     *
     * @var string
     */
    private $_defaultGroup = 'default';

    /**
     * 单例模式
     *
     * @var array
     */
    private static $_instances = array();

    public static function getInstance($className = 'Memcache')
    {
        if (! isset(self::$_instances[$className])) {
            self::$_instances[$className] = new self($className);
        }

        return self::$_instances[$className];
    }

    private function __construct($className)
    {
        $this->_className = 'Com_Cache_' . $className;
    }

    /**
     * 每个分组都有一个缓存实例
     *
     * @param string $group
     * @return object Com_Cache_*
     */
    public function __get($group)
    {
        return $this->{$group} = new $this->_className($group);
    }

    /**
     * 默认分组的魔术方法（那么调用时可以省略默认分组名）
     * 例如：$this->get('key') 等价于 $this->default->get('key')
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        $cacheObj = $this->{$this->_defaultGroup};
        return call_user_func_array(array($cacheObj, $method), $args);
    }

    /**
     * 获取指定缓存分了几个组
     *
     * @param string $className
     * @return array
     */
    public static function getGroups($className = 'Memcache')
    {
        $config = Core_Config::loadEnv(strtolower($className));

        return array_keys($config);
    }
}