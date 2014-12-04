<?php

/**
 * 异常处理
 *
 * @author JianJian <silverd@sohu.com>
 * $Id: Abstract.php 9716 2014-03-11 12:12:41Z jiangjian $
 */

abstract class Core_Exception_Abstract extends Yaf_Exception
{
    protected $_errType = 'error';

    public function __toString()
    {
        return $this->getMessage();
    }

    // 设置异常类型
    public function setErrType($errType)
    {
        $this->_errType = $errType;
    }

    public function getErrType()
    {
        return $this->_errType;
    }
}