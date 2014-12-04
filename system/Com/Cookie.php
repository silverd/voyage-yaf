<?php

/**
 * Cookie 封装
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Cookie.php 848 2012-12-19 02:49:14Z jiangjian $
 */

class Com_Cookie extends Core_ArrayAccess
{
    private static $_instance;
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        $this->setArrayAccess($_COOKIE);
    }

    public function set($name, $value = null, $expire = null, $path = '/', $domain = null, $secure = false, $httponly = false)
    {
        setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        $_COOKIE[$name] = $value;
    }
}