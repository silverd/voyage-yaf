<?php

/**
 * 用户中心专用控制器
 *
 * @author sunli
 * $Id: Auth.php 11447 2014-06-17 09:20:37Z zhengjiang $
 */

class Controller_Auth extends Controller_Abstract
{
    protected $_checkAuth = false;

    /**
     * 服务器列表
     */
    public function serverListAction()
    {
        if (! $this->_uid) {
            $this->vRedirect('/auth/login/?is_logout=1');
        }

        // 最近登陆的游戏服ID
        $recentGameServerId = F('Cookie')->get('__rgsid');
        $this->assign('recentGameServerId', $recentGameServerId);

        // 服务器列表
        $serverList = Model_Ucenter_Server::getServerList();
        $this->assign('serverList', $serverList);

        // 默认选中的服务器
        if (isset($serverList[$recentGameServerId])) {
            $defGameServerId = $recentGameServerId;
        }
        else {
            $defGameServerId = key($serverList);
        }
        $this->assign('defGameServerId', $defGameServerId);

        $this->assign('bodyClass', 'login_bg');
    }

    /**
     * 跳转进入指定游戏分区
     */
    public function serverIntroAction()
    {
        if (! $this->_uid) {
            $this->vRedirect('/auth/login/?is_logout=1');
        }

        $gameServerId = $this->getInt('sid');

        if (! $gameServer = Model_Ucenter_Server::getServerInfo($gameServerId)) {
            throws('Invalid GameServerId');
        }

        $__vuser = Model_User_Api_Auth::getUserCookie();

        // 构造当前渠道的“游戏分区”访问网址
        $gameServerHost = Model_Ucenter_Server::buildGameServerHost($gameServer['host']);

        // 跳转进入指定游戏分区
        $this->vRedirect('http://' . $gameServerHost . '/main/?__vuser=' . $__vuser . '&__rgsid=' . $gameServerId . '&is_login=1');
    }

    /**
     * 注册
     */
    public function registerAction()
    {
        // 已登录的跳走
        if ($this->_uid) {
            $this->vRedirect('/main');
        }

        if (! $this->isSubmit()) {

            $email = rawurldecode($this->getx('email'));

            $this->assign('email', $email);
            $this->assign('bodyClass', 'login_bg');

        } else {

            $postData = $this->getQueryx();
            $uid = Model_User_Api_Auth::register($postData);

            if (is_array($uid)) {
                $errMsg = $uid;
                $this->jsonx('errTips', 'error', $errMsg);
            }

            // 刷新并保存用户凭证到cookie
            Model_User_Api_Auth::refreshUserToken($uid);

            // 成功则将 __vuser 输出
            $this->jsonx(Model_User_Api_Auth::getUserCookie());
        }
    }

    /**
     * 快速注册
     */
    public function quickRegAction()
    {
        // 已登录的跳走
        if ($this->_uid) {
            $this->vRedirect('/main');
        }

        if (! $sHash = $this->getx('shash')) {
            throws403('Empty QuickRegHash');
        }

        if ($sHash != F('Session')->get('quick_reg_hash')) {
            throws403('Invalid QuickRegHash');
        }

        // 用过即删
        F('Session')->del('quick_reg_hash');

        $uid = Model_User_Api_Auth::quickRegister();

        if ($uid < 1) {
            $this->minaSays(_('网络繁忙，请稍后重试'), '/main');
        }

        // 刷新并保存用户凭证到cookie
        Model_User_Api_Auth::refreshUserToken($uid);

        $this->vRedirect('/auth/serverlist');
    }

    /**
     * 登陆
     */
    public function loginAction()
    {
        // 已登录的跳走
        if ($this->_uid) {
            $this->vRedirect('/main');
        }

        if (! $this->isSubmit()) {

            // 防CSRF凭证
            $quickRegHash = md5(uniqid(mt_rand(1, 10000)));
            F('Session')->set('quick_reg_hash', $quickRegHash);
            $this->assign('quickRegHash', $quickRegHash);

            $this->assign('bodyClass', 'login_bg');

        } else {

            $email    = $this->getx('email');
            $password = $this->getx('password');

            $uid = Model_User_Api_Auth::login($email, $password);

            if (is_array($uid)) {
                $errMsg = $uid;
                $this->jsonx('errTips', 'error', $errMsg);
            }

            // 刷新并保存用户凭证到cookie
            Model_User_Api_Auth::refreshUserToken($uid);

            // 成功则将 __vuser 输出
            $this->jsonx(Model_User_Api_Auth::getUserCookie());
        }
    }

    /**
     * 登出
     */
    public function logoutAction()
    {
        if ($this->_uid) {
            Model_User_Api_Auth::logout();
        }

        $this->vRedirect('/auth/login/?is_logout=1');
    }

    /**
     * 发送验证码
     */
    public function sendCheckCodeAction()
    {
        // 防刷机制，每两次请求必须间隔X毫秒
        $this->_assertReqInterval(2000);

        $email = $this->getx('email');

        // 执行发送
        Model_User_Api_Auth::sendCheckCode($email);

        $this->jsonx(_('验证码已发送成功'));
    }

    /**
     * 找回密码
     */
    public function findPasswordAction()
    {
        if (! $this->isSubmit()) {

            // 直接渲染模板

        } else {

            $email     = $this->getx('email');
            $checkCode = $this->getx('check_code');

            // 验证邮箱和验证码的合法性
            Model_User_Api_Auth::verifyCheckCode($email, $checkCode);

            $this->jsonx('OK');
        }
    }

    /**
     * 重置密码
     */
    public function resetPasswordAction()
    {
        $email     = $this->getx('email');
        $checkCode = $this->getx('check_code');

        // 验证邮箱和验证码的合法性
        Model_User_Api_Auth::verifyCheckCode($email, $checkCode);

        if (! $this->isSubmit()) {

            $this->assign('email', $email);
            $this->assign('checkCode', $checkCode);

        } else {

            // 新的密码
            $password = $this->getx('password');
            $repasswd = $this->getx('repasswd');

            // 执行重置
            Model_User_Api_Auth::resetPassword($email, $password, $repasswd);

            $this->jsonx(_('密码重置成功'));
        }
    }

    /**
     * 后台免密码模拟登陆
     */
    public function emuLoginAction()
    {
        $uid = $this->getInt('uid');

        if ($uid < 1) {
            throws403('Invalid EmuLogin Uid');
        }

        // 模拟登陆方式 (1:无痕登陆 2:真实登陆-会更新玩家最后登录时间)
        $loginType = $this->getInt('login_type') ?: 1;

        // 验证签名
        if (! Model_User_Api_EmuLogin::verifySign($uid, $loginType, $this->getx('sign'))) {
            throws403('Invalid EmuLogin Sign');
        }

        if (! $userIndex = Dao('Ucenter_UserIndex')->get($uid)) {
            throws403('Not Exists UserIndex');
        }

        if (! $userToken = $userIndex['user_token']) {
            throws403('Invalid EmuLogin UserToken');
        }

        // 设置 Cookie
        Model_User_Api_EmuLogin::setCookie($uid, $userToken);

        // 无痕登陆标记
        if ($loginType == 1) {
            F('Cookie')->set('isQaLogin', true);
        }
        else {
            F('Cookie')->set('isQaLogin', null);
        }

        $this->redirect('/main');
    }
}