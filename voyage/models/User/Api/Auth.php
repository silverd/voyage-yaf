<?php

/**
 * 用户认证模型
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Auth.php 11337 2014-06-10 05:31:48Z jiangjian $
 */

class Model_User_Api_Auth extends Core_Model_Abstract
{
    public static $cookieName = '__vuser';
    public static $cookieTtl  = 7776000;    // 3个月

    public static $uniqUidKey = 'XXXXXXXXXXXXX';

    /**
     * 登录
     *
     * @param string $email
     * @param string $password
     * @return array/true
     */
    public static function login($email, $password)
    {
        // 邮箱统一转为小写
        $email = strtolower($email);

        if (! $email || ! Com_Validate::email($email)) {
            return array('email' => _('请输入正确的邮箱地址'));
        }

        if (strlen($email) > 100) {
            return array('email' => _('邮箱长度不能大于100字符'));
        }

        if (! $password) {
            return array('password' => _('请输入密码'));
        }

        // 查看用户信息
        $userIndex = Dao('Ucenter_UserIndex')->getUserByEmail($email);

        if (! $userIndex)  {
            return array('email' => _('该邮箱未注册'));
        }

        if (sha1($password) != $userIndex['password']) {
            return array('password' => _('密码输入错误'));
        }

        $uid = $userIndex['id'];

        return $uid;
    }

    /**
     * 根据 email/password 返回 userIndex
     *
     * @return array
     */
    public static function getUserIndexByEmailPw($email, $password)
    {
        // 邮箱统一转为小写
        $email = strtolower($email);

        if (! $email || ! Com_Validate::email($email)) {
            throws(_('请输入正确的邮箱地址'));
        }

        if (strlen($email) > 100) {
            throws(_('邮箱长度不能大于100字符'));
        }

        if (! $password) {
            throws(_('请输入密码'));
        }

        // 查看用户信息
        $userIndex = Dao('Ucenter_UserIndex')->getUserByEmail($email);

        if (! $userIndex)  {
            throws(_('该邮箱未注册'));
        }

        if (sha1($password) != $userIndex['password']) {
            throws(_('密码输入错误'));
        }

        return $userIndex;
    }

    /**
     * 注销
     *
     * @return void
     */
    public static function logout()
    {
        F('Cookie')->del(self::$cookieName);
    }

    /**
     * 注册
     *
     * @param array $postData
     * @return array/int $uid
     */
    public static function register(array $postData)
    {
        $inviteCode = strtoupper($postData['invite_code']);
        $email      = strtolower($postData['email']);
        $password   = $postData['password'];
        $mobile     = isset($postData['mobile']) ? $postData['mobile'] : '';

        // 注册需要邀请码
        if (INVITE_CODE_NEED) {
            if (! $inviteCode) {
                return array('invite_code' => _('请输入邀请码'));
            }
            $inviteCodeInfo = Dao('Ucenter_InviteCode')->get($inviteCode);
            if (! $inviteCodeInfo) {
                return array('invite_code' => _('邀请码输入不正确'));
            }
            elseif ($inviteCodeInfo['uid']) {
                return array('invite_code' => _('邀请码已被使用'));
            }
        }

        if (! $email || ! Com_Validate::email($email)) {
            return array('email' => _('请输入正确的邮箱地址'));
        }

        if (strlen($email) > 100) {
            return array('email' => _('邮箱长度不能大于100字符'));
        }

        // 检查密码长度
        $pwdLength = Helper_String::strlen($password);
        if ($pwdLength < 6 || $pwdLength > 16) {
            return array('password' => _('6到16个字符，区分大小写'));
        }

        if (Dao('Ucenter_UserIndex')->getUidByEmail($email)) {
            return array('email' => _('该邮箱已经被注册，请更换'));
        }

        // 创建新用户
        $data = array(
            'email'    => $email,
            'password' => sha1($password),
            'mobile'   => $mobile,
        );

        $uid = self::createUcenterUser($data);

        if ($uid < 1) {
            return array('email' => _('网络繁忙，请稍后重试'));
        }

        // 邀请码设置为已用
        if (INVITE_CODE_NEED) {
            Dao('Ucenter_InviteCode')->setInviteCodeUsed($inviteCode, $uid, $GLOBALS['_DATE']);
        }

        return $uid;
    }

    /**
     * 快速注册
     *
     * @return array/int $uid
     */
    public static function quickRegister()
    {
        // 生成一个随机邮箱
        do {
            $email = uniqid() . mt_rand(1, 10000) . '@voyage.qreg';
            $isExist = Dao('Ucenter_UserIndex')->getUidByEmail($email);
        } while ($isExist);

        // 创建新用户
        $data = array(
            'email'        => $email,
            'password'     => '',
            'mobile'       => '',
            'is_quick_reg' => 1,
        );

        $uid = self::createUcenterUser($data);

        if ($uid < 1) {
            return false;
        }

        return $uid;
    }

    /**
     * 创建“用户中心”的一个新用户
     *
     * @param array $data
     * @param bool $isNeedRefreshToken
     * @return int $uid
     */
    public static function createUcenterUser(array $data, $isNeedRefreshToken = false)
    {
        $email      = $data['email'];
        $password   = isset($data['password'])     ? $data['password']   : '';
        $mobile     = isset($data['mobile'])       ? $data['mobile']     : '';
        $regSource  = isset($data['reg_source'])   ? $data['reg_source'] : '';
        $isQuickReg = isset($data['is_quick_reg']) ? intval($data['is_quick_reg']) : 0;

        // 插“用户中心”索引表
        $setArr = array(
            'email'        => $email,
            'password'     => $password,
            'mobile'       => $mobile,
            'reg_source'   => $regSource,
            'is_quick_reg' => $isQuickReg,
        );

        $uid = Dao('Ucenter_UserIndex')->insert($setArr);

        // 刷新用户凭证（但不设置cookie）
        if ($isNeedRefreshToken) {
            Model_User_Api_Auth::refreshUserToken($uid, false);
        }

        return $uid;
    }

    /**
     * 创建“游戏内”的一个新用户
     *
     * @param int $uid 平台uid
     * @param array $data
     * @param bool $giftShip 是否送船
     * @return string $userCode
     */
    public static function createGameUser($uid, array $data, $giftShip = true, $verifyUserName = true)
    {
        if ($uid < 1) {
            return false;
        }

        $avatarId  = isset($data['avatar_id'])  ? $data['avatar_id']  : 0;
        $nationId  = isset($data['nation_id'])  ? $data['nation_id']  : 0;
        $userName  = isset($data['user_name'])  ? $data['user_name']  : '';
        $regSource = isset($data['reg_source']) ? $data['reg_source'] : '';

        if ($avatarId < 1 || ! $avatar = Dao('Static_Avatar')->get($avatarId)) {
            throws('Invaild AvatarId');
        }

        if ($nationId < 1 || ! $nation = Dao('Static_Nation')->get($nationId)) {
            throws('Invalid NationId');
        }

        if (! $userName && $userName !== '0') {
            throws(_('角色名不能为空'));
        }

        if ($verifyUserName) {
            if (! Com_Validate::userName($userName)) {
                throws(_('角色名不符合规则') . ': ' . $userName);
            }
            // 检测敏感词
            $censor = new Model_Censor();
            if (! $censor->verify($userName)) {
                throws(_('角色名含有敏感词，请重新输入'));
            }
        }

        // 用户名是否已存在
        if (Dao('Share_UserIndex')->getUidByName($userName)) {
            throws(_('角色名已经被占用，请更换'));
        }

        // 去“用户中心”生成一个联盟号
        $userCode = self::getUserCode();

        // 建立“联盟号”和“游戏服”绑定关系
        $setArr = array(
            'user_code'      => $userCode,
            'uid'            => $uid,
            'game_server_id' => CUR_GAME_SERVER_ID,
            'user_name'      => $userName,
        );
        Dao('Ucenter_UserCodeIndex')->insert($setArr);

        // 插游戏用户索引表
        $setArr = array(
            'uid'       => $uid,
            'user_code' => $userCode,
            'user_name' => $userName,
            'db_suffix' => self::getDbSuffixForNewUser($uid),   // 用户库后缀
        );
        Dao('Share_UserIndex')->insert($setArr);

        // 初始银币
        $initSilver = C('INIT_SILVER');

        // 加上各国的国家启动资金
        $initSilver += Dao('Static_NationConfig')->getField($nationId, 'init_silver');

        // 插入用户基本信息
        $setArr = array(
            'uid'              => $uid,
            'user_code'        => $userCode,
            'silver'           => $initSilver,
            'gold'             => C('INIT_GOLD'),
            'gift_gold'        => C('INIT_GOLD'),
            'hp'               => C('INIT_HP'),
            'hp_max'           => C('INIT_HP'),
            'move'             => C('INIT_MOVE'),
            'move_max'         => C('INIT_MOVE'),
            'energy'           => C('INIT_ENERGY'),
            'energy_max'       => C('INIT_ENERGY'),
            'exp'              => 0,
            'level_id'         => 1,
            'position_id'      => 1,      // 默认官爵：学徒
            'nation_id'        => 0,
            'avatar_id'        => 0,
            'status'           => 0,      // 默认状态：港口中
            'port_id'          => 421003, // 默认在“热内亚”
            'in_tutorial'      => Model_User_Tutorial::VIRGIN,  // 新手进场
            'create_time'      => $GLOBALS['_DATE'],
            'avatar_id'        => $avatarId,
            'nation_id'        => $nationId,
            'sex'              => $avatar['sex'],
            'user_name'        => $userName,
        );
        Dao('Dist_User')->loadDs($uid)->insert($setArr);

        // 用户扩展基本信息
        $setArr = array(
            'uid'        => $uid,
            'dock_max'   => C('INIT_DOCK_MAX'),             // 码头默认最多存放2艘船
            'reg_source' => $regSource,                     // 注册渠道 (如:91/机锋/360)
            'create_ip'  => Helper_Client::getUserIp(),     // 注册IP
        );
        Dao('Dist_UserInfo')->loadDs($uid)->insert($setArr);

        // 用户航行信息
        $setArr = array(
            'uid' => $uid,
        );
        Dao('Dist_UserNavigate')->loadDs($uid)->insert($setArr);

        // 用户统计信息
        $setArr = array(
            'uid' => $uid,
        );
        Dao('Dist_UserStats')->loadDs($uid)->insert($setArr);

        // 用户扩展大字段
        $setArr = array(
            'uid' => $uid,
        );
        Dao('Dist_UserData')->loadDs($uid)->insert($setArr);

        // 用户设置信息
        $setArr = array(
            'uid'          => $uid,
            'sound_on'     => 1,    // 缺省开启音效
            'music_on'     => 1,    // 缺省开启音乐
            'animation_on' => 1,    // 缺省开启动画
            'scout_sea_on' => 0,    // 缺省关闭侦察特效
        );
        Dao('Dist_UserSettings')->loadDs($uid)->insert($setArr);

        // 新用户送船
        if ($giftShip) {

            // 赠送新用户一艘“轻木帆船”
            $user = new Model_User($uid, false);
            $ship = new Model_Ship(201001);

            // 执行赠送：送的船默认进码头，不入舰队
            $userShipId = $user->ship->insert($ship);
        }

        // 增加国家人口数（冗余）
        Dao('Share_NationStats')->incrByPk($nationId, 'population');

        return $userCode;
    }

    /**
     * 去“用户中心”生成一个联盟号
     *
     * @return string
     */
    public static function getUserCode()
    {
        do {
            $sourceStr = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
            $userCode = Helper_String::random(6, false, $sourceStr);
            if (! Dao('Ucenter_UserCodeIndex')->get($userCode)) {
                return $userCode;
            }
        } while (true);
    }

    public static function getUidHash($uid)
    {
        return sha1($uid . '|' . self::$uniqUidKey);
    }

    /**
     * 生成唯一的随机 userToken
     *
     * @param int $uid
     * @return string
     */
    public static function genUserToken($uid)
    {
        return sha1(uniqid($uid) . mt_rand(1, 10000));
    }

    /**
     * 获取新用户使用的分库
     *
     * @param int $uid
     * @return int
     */
    public static function getDbSuffixForNewUser($uid)
    {
        $suffixes = explode(',', DB_SUFFIX_NEW_USER);

        if (count($suffixes) < 2) {
            return current($suffixes);
        }

        // 奇偶分库
        return $suffixes[(($uid + 1 ) % 2)];
    }

    /**
     * 刷新 userToken
     *
     * @param int $uid
     * @param bool $isNeedSetCookie
     * @throws Core_Exception_Logic
     * @return void
     */
    public static function refreshUserToken($uid, $isNeedSetCookie = true)
    {
        $uidHash   = self::getUidHash($uid);
        $userToken = self::genUserToken($uid);

        if (! Dao('Ucenter_UserIndex')->updateByPk(array('user_token' => $userToken), $uid)) {
            throws(_('登陆失败，请稍候再试'));
        }

        $__vuser = $uidHash . '-' . $userToken;

        // 设置用户凭证到 cookie
        if ($isNeedSetCookie) {
            self::setUserCookie($__vuser);
        }

        return $__vuser;
    }

    /**
     * 保存用户凭证
     *
     * @return void
     */
    public static function setUserCookie($userToken)
    {
        F('Cookie')->set(self::$cookieName, $userToken, $GLOBALS['_TIME'] + self::$cookieTtl);
    }

    /**
     * 读取用户凭证
     *
     * @return string
     */
    public static function getUserCookie()
    {
        return F('Cookie')->get(self::$cookieName);
    }

    /**
     * 获取当前已登录用户信息
     *
     * @param string $userTokensFromUrl
     * @return false/array
     */
    public static function getUserByToken($userTokensFromUrl = null)
    {
        if (! $userTokensFromUrl) {
            // 从 Cookie 中读取 userTokens
            if (! $userTokens = self::getUserCookie()) {
                return false;
            }
        }
        else {
            $userTokens = $userTokensFromUrl;
        }

        $userTokens = explode('-', $userTokens);
        $uidHash    = isset($userTokens[0]) ? $userTokens[0] : '';
        $userToken  = isset($userTokens[1]) ? $userTokens[1] : '';

        // 根据token获取用户信息
        if (! $user = Dao('Ucenter_UserIndex')->getUserByToken($userToken)) {
            return false;
        }

        if ($uidHash != self::getUidHash($user['id'])) {
            return false;
        }

        // 把URL中的 __vuser 塞回COOKIE
        if ($userTokensFromUrl) {
            // 将 userTokens 写回 Cookie 中
            self::setUserCookie($userTokensFromUrl);
        }

        return $user;
    }

    /**
     * 获取当前登陆的游戏分区ID
     *
     * @param int $__rgsId
     * @return $int
     */
    public static function getRecentGameServerId($__rgsId = null)
    {
        // 从URL中接收并塞回COOKIE
        if (null !== $__rgsId) {
            // 记录最近登陆的游戏分区ID
            F('Cookie')->set('__rgsid', $__rgsId, $GLOBALS['_TIME'] + self::$cookieTtl);
            // 标记为本次已选过分区
            F('Cookie')->set('__selsv', $GLOBALS['_TIME'], 0);  // 有效期同SESSION
        }
        else {
            $__rgsId = F('Cookie')->get('__rgsid');
            if (null === $__rgsId) {
                return false;
            }
        }

        if (! F('Cookie')->get('__selsv')) {
            return false;
        }

        if (! $gameServer = Model_Ucenter_Server::getServerInfo($__rgsId)) {
            return false;
        }

        return $__rgsId;
    }

    /**
     * 发送重置密码的验证码
     *
     * @return bool
     */
    public static function sendCheckCode($email)
    {
        if (! $email || ! Com_Validate::email($email)) {
            throws(_('请输入正确的邮箱地址'));
        }

        $userIndex = Dao('Ucenter_UserIndex')->getUserByEmail($email);

        if (! $userIndex) {
            throws(_('该用户邮箱不存在'));
        }

        // 密码找回功能，每天仅可使用一次
        if (strtotime($userIndex['last_find_password_time']) >= strtotime('today')) {
            throws(_('密码找回功能，每天仅可使用一次<br />如有问题，请联系客服：service@voyagemobile.com'));
        }

        // 生成随机的6位验证码
        $checkCode = mt_rand(100000, 999999);

        // 发送邮件
        $title   = _('航海争霸_手机大航海 -- 找回密码');
        $content = __('<p>亲爱的{email}：</p>您此次找回密码的验证码是：{checkCode}，请在30分钟内在找回密码页填入此验证码', array(
            'email'     => $email,
            'checkCode' => $checkCode,
        ));

        if (! Com_Email::send($email, $title, $content)) {
            return false;
        }

        // 将验证码存表里
        // 更新最后找回密码时间（用于做限制，例如：一天只能找回一次）
        $setArr = array(
            'last_check_code'         => $checkCode,
            'last_find_password_time' => $GLOBALS['_DATE'],
            'error_check_code_times'  => 0,
        );

        return Dao('Ucenter_UserIndex')->updateByPk($setArr, $userIndex['id']);
    }

    /**
     * 找回密码
     *
     * @return bool
     */
    public static function verifyCheckCode($email, $checkCode)
    {
        if (! $email || ! Com_Validate::email($email)) {
            throws(_('请输入正确的邮箱地址'));
        }

        $userIndex = Dao('Ucenter_UserIndex')->getUserByEmail($email);

        if (! $userIndex) {
            throws(_('该用户邮箱不存在'));
        }

        if (! $checkCode) {
            throws(_('请输入验证码'));
        }

        // 检测验证码是否相同
        if (! $checkCode || $checkCode != $userIndex['last_check_code']) {
            Dao('Ucenter_UserIndex')->incrByPk($userIndex['id'], 'error_check_code_times');
            throws(_('您输入的验证码不正确'));
        }

        // 检测验证码是否过期
        if (strtotime($userIndex['last_find_password_time']) + 1800 < $GLOBALS['_TIME'] ) {
            throws(_('验证码已过期'));
        }

        // 检测验证码输入错误次数
        if ($userIndex['error_check_code_times'] >= 5) {
            throws(_('验证码错误次数超过限制，请明日再试'));
        }

        return true;
    }

    /**
     * 重置密码
     *
     * @return bool
     */
    public static function resetPassword($email, $password, $repasswd)
    {
        if (! $email || ! Com_Validate::email($email)) {
            throws(_('请输入正确的邮箱地址'));
        }

        if (! $password) {
            throws(_('请输入新密码'));
        }

        if ($password != $repasswd) {
            throws(_('两次输入的密码不一致，请重试'));
        }

        // 检查密码长度
        $pwdLength = Helper_String::strlen($password);
        if ($pwdLength < 6 || $pwdLength > 16) {
            throws(_('密码输入有误，要求6到16个字符，区分大小写'));
        }

        $uid = Dao('Ucenter_UserIndex')->getUidByEmail($email);

        if (! $uid) {
            throws(_('该用户邮箱不存在'));
        }

        // 执行更新
        return self::changePassword($uid, $password);
    }

    /**
     * 修改我的登陆密码
     *
     * @param int $uid
     * @param string $newPassword
     * @return bool
     */
    public static function changePassword($uid, $newPassword)
    {
        return Dao('Ucenter_UserIndex')->updateByPk(array('password' => sha1($newPassword)), $uid);
    }

    /**
     * 修改我的登陆邮箱
     *
     * @param int $uid
     * @param string $newEmail
     * @return bool
     */
    public static function changeEmail($uid, $newEmail)
    {
        $setArr = array(
            'email'                  => $newEmail,
            'last_change_email_time' => $GLOBALS['_DATE']
        );

        return Dao('Ucenter_UserIndex')->updateByPk($setArr, $uid);
    }

    /**
     * 快速注册后需要重填邮箱、密码
     *
     * @param int $uid
     * @param string $email
     * @param string $password
     * @return array/bool
     */
    public static function rebindAccount($uid, $email, $password)
    {
        if (! $email || ! Com_Validate::email($email)) {
            throws(_('请输入正确的邮箱地址'));
        }

        if (strlen($email) > 100) {
            throws(_('邮箱长度不能大于100字符'));
        }

        // 检查密码长度
        $pwdLength = Helper_String::strlen($password);
        if ($pwdLength < 6 || $pwdLength > 16) {
            throws(_('密码输入有误，要求6到16个字符，区分大小写'));
        }

        if (Dao('Ucenter_UserIndex')->getUidByEmail($email)) {
            throws(_('该邮箱已经被注册，请更换'));
        }

        $setArr = array(
            'email'        => $email,
            'password'     => sha1($password),
            'is_quick_reg' => 2,    // 快速注册标记 0:非快速注册 1:快注未绑邮箱 2:快注已绑邮箱
        );

        return Dao('Ucenter_UserIndex')->updateByPk($setArr, $uid);
    }
}