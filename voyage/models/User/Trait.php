<?php

/**
 * 我的关系模型抽象父类
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Trait.php 2147 2013-04-18 04:00:23Z jiangjian $
 */

abstract class Model_User_Trait extends Core_Model_Abstract
{
    protected $_uid;
    protected $_user;

    public function __construct(Model_User $user)
    {
        $this->_user = $user;
        $this->_uid  = $this->_user['uid'];

        // 相当于子类构函
        $this->_initTrait();
    }

    public function DaoDs($class)
    {
        return $this->_user->DaoDs($class);
    }

    /**
     * 子类构函
     *
     * @return void
     */
    protected function _initTrait()
    {
        // 方法体由子类继承重写
    }
}