<?php

/**
 * 加载类相关
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Loader.php 2307 2013-04-25 08:18:03Z jiangjian $
 */

class Core_Loader
{
    /**
     * 单例模式
     *
     * @param string $className
     * @return object
     */
    private static $_loadedClass = array();
    public static function getSingleton($className)
    {
        if (! isset(self::$_loadedClass[$className])) {
            self::$_loadedClass[$className] = new $className;
        }

        return self::$_loadedClass[$className];
    }
}