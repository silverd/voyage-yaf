<?php

/**
 * 错误异常处理器
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Error.php 9716 2015-05-31 12:12:41Z jiangjian $
 */

abstract class Core_Controller_Error extends Core_Controller_Web
{
    public function errorAction()
    {
        $e = $this->_request->getException();

        if (! $e instanceof Exception) {
            $this->_errMsg('What Ghost?');
        }

        try {

            throw $e;

        } catch (Core_Exception_403 $e) {

            if ($this->isAjax()) {

                $this->jsonx($e->getMessage(), '403', array('isDebug' => isDebug()));

            } else {

                if (! isDebug()) {
                    header403();
                }

                $this->_errMsg($e->getMessage());
            }

        } catch (Core_Exception_Logic $e) {

            if ($this->isAjax()) {

                $this->jsonx($e->getMessage(), $e->getErrType());

            } else {

                $this->_errMsg($e->getMessage());
            }

        } catch (Exception $e) {

            header('HTTP/1.0 500 Internal Server Error');
            header('Status: 500 Internal Server Error');

            $this->_errMsg($e->getMessage());
        }

        return false;
    }

    protected function _errMsg($message, $title = 'Oops ...')
    {
        _exit($title . ' ' . $message);
    }
}