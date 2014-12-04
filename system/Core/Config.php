<?php

/**
 * 配置类相关
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Config.php 2 2012-11-14 07:10:07Z jiangjian $
 */

class Core_Config
{
    public static function load($name)
    {
        $config = Yaf_Registry::get('config')->get($name);
        return $config ? $config->toArray() : array();
    }

    public static function loadEnv($name)
    {
        return self::load($name);
    }
}