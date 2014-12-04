<?php

/**
 * 控制器抽象父类
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Abstract.php 11636 2014-07-08 02:41:43Z jiangjian $
 */

abstract class Controller_Abstract extends Core_Controller_Web
{
    /**
     * 当前用户 uid
     *
     * @var int
     */
    protected $_uid;

    /**
     * 当前用户实例对象
     *
     * @var Model_User
     */
    protected $_user;

    /**
     * 是否检测登陆
     *
     * @var bool
     */
    protected $_checkAuth = true;

    /**
     * 构造函数
     */
    public function init()
    {
        parent::init();
    }
}