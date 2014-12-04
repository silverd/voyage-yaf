<?php

/**
 * Api 错误异常处理器
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Error.php 7047 2013-11-25 02:51:14Z jiangjian $
 */

class Controller_Error extends Core_Controller_Api
{
    public function errorAction()
    {
        $e = $this->_request->getException();

        if (! $e instanceof Exception) {
            $this->output('Unknow Error/Warning', -999);
        }

        $this->output($e->getMessage(), $e->getCode());

        return false;
    }
}
