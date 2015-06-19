<?php

/**
 * 控制器抽象父类
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Abstract.php 2696 2012-07-05 05:20:09Z jiangjian $
 */

abstract class Core_Controller_Admincp extends Core_Controller_Web
{
    protected $_admin;
    protected $_global;

    /**
     * 构造函数
     */
    public function init()
    {
        parent::init();

        // 当前登录管理员
        if ($adminId = Model_System_Auth::getSession()) {

            // 实例化管理员
            $this->_admin = new Model_System_Admin($adminId);
            $this->assign('admin', $this->_admin);

            // 定义全局数组
            $this->_initGlobalVar();
        }

        // 当前 ACL 权限检测
        $this->_checkAuth();
    }

    /**
     * 检测权限
     */
    protected function _checkAuth()
    {
        $controller = $this->getRequest()->getControllerName();
        $action     = $this->getRequest()->getActionName();

        // 当前 URI 是否需要 ACL 检测
        if (! Model_System_Auth::needAuthCheck($controller, $action)) {
            return null;
        }

        // 未登录的跳转到登录页
        if (! $this->_admin) {
            $this->xRedirect('/system/auth/login');
        }

        // 当前 ACL 检测, 根管理员免检
        if (! Model_System_Auth::isRoot($this->_admin)) {
            if (! Model_System_Auth::checkPriv($this->_admin['role']['privileges'], $controller, $action)) {
                $this->showMsg('对不起，您无权访问本页，如有疑问，请联系管理员。', 'error');
            }
        }
    }

    /**
     * 定义全局数组
     */
    protected function _initGlobalVar()
    {
        // 角色
        $this->_global['roleList'] = Dao('AdminCP_Role')->fetchPairs();

        // 状态
        $this->_global['statusList'] = array(
            0 => '禁用',
            1 => '启用',
        );

        // 传出模板全局变量
        $this->assign($this->_global);
    }

    public function alert($msg, $status = 'success', $url = '', $extra = '')
    {
        $extra .= ' if (parent.resetSumbitBtn != undefined) { parent.resetSumbitBtn(); }';

        if (is_array($msg)) {
            $msg = implode('\n', $msg);
        }

        // Ajax
        if ($this->isAjax()) {
            $this->jsonx($msg, $resultType);
        }

        // 跳转链接
        if ($url == 'halt') {
            $jumpStr = '';
        } else {
            $url = $url ? $url : $this->refer();
            $url = $url ? $url : '/';
            $jumpStr = $url ? "top.document.getElementById('inner_page_ifrm').src = '{$url}';" : '';
        }

        $this->js("alert('{$msg}'); {$extra} {$jumpStr}");
    }

    /**
     * 页顶提示信息
     *
     * @param string $msg
     * @param string $status success|error|warning
     * @param string $url
     * @param int $hideTimeout 提示信息后自动消失（毫秒）
     */
    public function tips($msg, $status = 'success', $url = '', $hideTimeout = 3000)
    {
        if (is_array($msg)) {
            $msg = implode('<br />', $msg);
        }

        $jumpStr = $url ? "top.location.href = '{$url}'" : '';
        $script = <<<SILVER
            parent.$('ul.breadcrumb').next('div.alert').remove();
            msgCont = parent.$('<div class="alert alert-{$status}"><a class="close">×</a>{$msg}</div>').insertAfter('ul.breadcrumb');
            setTimeout(function(){
                msgCont.slideUp();
                {$jumpStr}
            }, {$hideTimeout});
SILVER;
        $this->js($script);
    }

    /**
     * 新提示页面
     *
     * @param string $msg
     * @param string $status success|error|warning|info
     * @param string $url 自动跳转
     * @param int $seconds 页面停留几秒
     */
    public function showMsg($msg, $status = 'success', $url = '', $seconds = 5)
    {
        if ($this->isAjax()) {
            $this->jsonx($msg, $status);
        }

        $this->assign('url', $url);
        $this->assign('msg', $msg);
        $this->assign('status', $status);
        $this->assign('seconds', $seconds);

        $this->getView()->display('_inc/msg');
        exit();
    }

    public function xRedirect($url = '', $isJs = true)
    {
        $url = $url ? $url : $_SERVER['HTTP_REFERER'];

        if ($isJs) {
            exit('<script type="text/javascript">top.location.href = \'' . $url . '\';</script>');
        }

        header('Location: ' . $url);
        exit();
    }
}