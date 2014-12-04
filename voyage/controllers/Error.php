<?php

/**
 * 错误异常处理器
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Error.php 9716 2014-03-11 12:12:41Z jiangjian $
 */

class Controller_Error extends Core_Controller_Web
{
    public function errorAction()
    {
        $e = $this->_request->getException();

        if (! $e instanceof Exception) {
            _exit('Access Denied');
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

                _exit($e->getMessage());
            }

        } catch (Core_Exception_404 $e) {

            if (! isDebug()) {
                header404();
            }

            _exit($e->getMessage());

        } catch (Core_Exception_Logic $e) {

            if ($this->isAjax()) {

                $this->jsonx($e->getMessage(), $e->getErrType());

            } else {

                _exit($e->getMessage());
            }

        } catch (Yaf_Exception_LoadFailed_View $e) {

            _exit($e->getMessage());

        } catch (Exception $e) {

            switch ($e->getCode()) {
                case YAF_ERR_NOTFOUND_MODULE:
                case YAF_ERR_NOTFOUND_CONTROLLER:
                case YAF_ERR_NOTFOUND_ACTION:
                    if (! isDebug()) {
                        header404();
                    }
                    break;
                default:
                    if (! isDebug()) {
                        header500();
                    }
            }

            _exit($e->getMessage());
        }

        return false;
    }
}
