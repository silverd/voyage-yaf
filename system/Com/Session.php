<?php

/**
 * Session 封装
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Session.php 70 2012-11-21 06:47:11Z jiangjian $
 */

class Com_Session extends Core_ArrayAccess
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
        if (! headers_sent() && ! isset($_SESSION)) {
            session_start();
        }

        $this->setArrayAccess($_SESSION);
    }
}