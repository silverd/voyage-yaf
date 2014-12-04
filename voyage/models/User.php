<?php

/**
 * 用户模型基类
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: User.php 12178 2014-09-26 02:10:30Z jiangjian $
 */

class Model_User extends Core_Model_Abstract
{
    protected $_uid;

    const
        STATUS_IN_PORT = 0, // 港口中
        STATUS_SAILING = 1; // 航行中

    public function __construct($uid, $extendInit = true)
    {
        $this->_prop = self::getUser($uid, true);
        $this->_uid  = $this->_prop['id'] = $this->_prop['uid'];

        // 更多玩家数据的初始化
        // 某些场合为了节约性能，不需要这些，例如战斗玩家列表
        if ($extendInit) {

            // 生命值、行动力、精力等自动恢复
            $this->restore->regular();

            // 更新我的航行信息、所处海域、在航线中的位置
            $this->isSailing() && $this->navigate->refresh();
        }

        // 终极hack
        // 解决经常性出现的 port_id 为0的情况，为了使玩家不报错，强制定位到“热那亚”
        if ($this->isInPort() && $this->_prop['port_id'] < 1) {
            $this->update(array('port_id' => 421003));
            // 记录调试日志
            Dao('Massive_DebugLog')->mark(array(
                'uid'       => $this->_uid,
                'user_name' => $this->_prop['user_name'],
            ), 1);
        }
    }

    // 根据联盟号创建用户实例
    public static function getInstanceByUserCode($userCode, $extendInit = true)
    {
        if (! $userCode) {
            throws(_('联盟号不能为空'));
        }

        // 根据联盟号找uid
        if (! $uid = Dao('Share_UserIndex')->getUidByCode($userCode)) {
            throws(_('该联盟号不存在'));
        }

        return new self($uid, $extendInit);
    }

    public function __get($var)
    {
        // 我的扩展行为
        static $_traits = array(
            'base'        => 1,  // 基本操作
            'ship'        => 1,  // 我的舰船
            'captain'     => 1,  // 我的船长
            'item'        => 1,  // 我的物品:通用
            'battle'      => 1,  // 我的战斗
            'navigate'    => 1,  // 我的航海行为
            'restore'     => 1,  // 我的属性自动回复机制
            'tavern'      => 1,  // 我的酒馆数据
            'treasure'    => 1,  // 我的宝物
            'achievement' => 1,  // 我的成就
            'goods'       => 1,  // 我的贸易品
            'stats'       => 1,  // 我的统计数据
            'feed'        => 1,  // 我的动态
            'event'       => 1,  // 我的突发事件
            'invest'      => 1,  // 我的港口投资
            'pm'          => 1,  // 我的私信
            'gmNotice'    => 1,  // 我的GM通知
            'sysNotice'   => 1,  // 我的系统公告
            'title'       => 1,  // 我的称号
            'discovery'   => 1,  // 我的发现物
            'friend'      => 1,  // 我的好友
            'driftBottle' => 1,  // 我的漂流瓶
            'dailyStats'  => 1,  // 我的每日行为统计次数
            'hourlyStats' => 1,  // 我的每小时行为统计次数
            'dailyTask'   => 1,  // 我的每日任务
            'mainTask'    => 1,  // 我的主线任务
            'tips'        => 1,  // 我的实时提示框
            'intelligence'=> 1,  // 我的情报系统
            'tutorial'    => 1,  // 我的新手引导
            'unlock'      => 1,  // 我的模块解锁
            'award'       => 1,  // 我的奖励
            'settings'    => 1,  // 我的设置
            'info'        => 1,  // 我的扩展信息
            'survey'      => 1,  // 我的问卷调查
            'vPaid'       => 1,  // 我的付费虚拟道具 (侦测之镜、微光水晶等)
            'bargain'     => 1,  // 我的港口讨价还价行为模型
            'buff'        => 1,  // 我的主人公BUFF
            'guideTips'   => 1,  // 初次使用的引导提示
            'bank'        => 1,  // 我的银行
            'cruise'      => 1,  // 我的离线巡航
            'grownTask'   => 1,  // 我的成长任务
            'tempPackage' => 1,  // 我的临时包裹
            'supply'      => 1,  // 我的自动补充装备
            'vip'         => 1,  // 我的VIP特权
            'chapman'     => 1,  // 我的神秘商人
            'blacklist'   => 1,  // 我的黑名单
            'lotCounter'  => 1,  // 我的抽奖计数器
            'shipTech'    => 1,  // 我的船只科技
            'cumCharge'   => 1,  // 我的累计充值活动
            'payment'     => 1,  // 我的充值行为
            'keelFactory' => 1,  // 我的龙骨工厂
            'fishing'     => 1,  // 我的北海渔场
        );

        if (isset($_traits[$var])) {
            $class = 'Model_User_' . ucfirst($var);
            return $this->{$var} = new $class($this);
        }

        // 我的扩展数据
        static $_extends = array(
            'level'        => 1,    // 我的等级信息
            'position'     => 1,    // 我的官爵信息
            'nation'       => 1,    // 我的国家信息
            'seaArea'      => 1,    // 我的海域信息
            'flagship'     => 1,    // 我的旗舰信息
            'data'         => 1,    // 我的扩展大数据字段
            'titleTrade'   => 1,    // 我的贸易称号
            'titleExplore' => 1,    // 我的探险称号
            'titleBattle'  => 1,    // 我的战斗称号
            'bountyInfo'   => 1,    // 我的被悬赏信息
            'port'         => 1,    // 我的所在港口信息
        );

        if (isset($_extends[$var])) {
            $method = '_loadUser' . ucfirst($var);
            return $this->{$var} = $this->$method();
        }

        return parent::__get($var);
    }

    public function DaoDs($class)
    {
        return Dao('Dist_' . $class)->loadDs($this->_uid);
    }

    public static function getUser($uid, $halted = false)
    {
        $user = array();

        // 基本信息
        if ($uid < 1) {
            if ($halted) {
                throws403('Invalid Uid:' . $uid);
            }
        }

        if (! $user = Dao('Dist_User')->loadDs($uid)->get($uid)) {
            if ($halted) {
                throws(_('用户信息读取失败。' . 'Uid:' . $uid));
            }
        }

        return $user;
    }

    /**
     * 加载 我的扩展数据
     *
     * @return array
     */
    protected function _loadUserData()
    {
        return $this->DaoDs('UserData')->get($this->_uid);
    }

    /**
     * 加载 我的贸易称号
     *
     * @return array
     */
    protected function _loadUserTitleTrade()
    {
        if ($this->_prop['title_trade_id'] < 1) {
            return null;
        }

        return Dao('Static_TitleTrade')->get($this->_prop['title_trade_id']);
    }

    /**
     * 加载 我的探险称号
     *
     * @return array
     */
    protected function _loadUserTitleExplore()
    {
        if ($this->_prop['title_explore_id'] < 1) {
            return null;
        }

        return Dao('Static_TitleExplore')->get($this->_prop['title_explore_id']);
    }

    /**
     * 加载 我的战斗称号
     *
     * @return array
     */
    protected function _loadUserTitleBattle()
    {
        if ($this->_prop['title_battle_id'] < 1) {
            return null;
        }

        return Dao('Static_TitleBattle')->get($this->_prop['title_battle_id']);
    }

    /**
     * 加载 我的所在港口信息
     *
     * @return object Model_Port
     */
    protected function _loadUserPort()
    {
        if ($this->_prop['port_id'] < 1) {
            return null;
        }

        return new Model_Port($this->_prop['port_id']);
    }

    /**
     * 加载 我的旗舰信息
     *
     * @return Model_Ship
     */
    protected function _loadUserFlagship()
    {
        if ($this->_prop['flagship_id'] < 1) {
            return null;
        }

        return $this->ship->getOneShip($this->_prop['flagship_id']);
    }

    /**
     * 加载 我的海域信息
     *
     * @return array
     */
    protected function _loadUserSeaArea()
    {
        if ($this->_prop['sea_area_id'] < 1) {
            return null;
        }

        return Dao('Static_SeaArea')->get($this->_prop['sea_area_id']);
    }

    /**
     * 加载 我的等级信息
     *
     * @return array
     */
    protected function _loadUserLevel()
    {
        if ($this->_prop['level_id'] < 1) {
            return null;
        }

        if ($level = Dao('Static_Level')->get($this->_prop['level_id'])) {
            // 下一级的升级线
            $level['exp_line_next'] = $level['exp_line'] + $level['exp_offset'];
        }

        return $level;
    }

    /**
     * 加载 我的官爵信息
     *
     * @return array
     */
    protected function _loadUserPosition()
    {
        if ($this->_prop['position_id'] < 1) {
            return null;
        }

        return Dao('Static_Position')->get($this->_prop['position_id']);
    }

    /**
     * 加载 我的国家信息
     *
     * @return array
     */
    protected function _loadUserNation()
    {
        if ($this->_prop['nation_id'] < 1) {
            return null;
        }

        return Dao('Static_Nation')->get($this->_prop['nation_id']);
    }

    /**
     * 加载 我的被悬赏信息
     *
     * @return array
     */
    protected function _loadUserBountyInfo()
    {
        return Dao('Share_BountyList')->get($this->_uid);
    }

    public function getStatus()
    {
        return $this->isSailing() ? 'sailing' : 'inPort';
    }

    // 是否被悬赏中
    public function isInBounty()
    {
        return $this->_loadUserBountyInfo() ? true : false;
    }

    /**
     * 检测当前用户是否正在指定港口中
     *
     * @param int $portId
     * @return bool
     */
    public function isInPort($portId = null)
    {
        $result = $this->_prop['status'] == self::STATUS_IN_PORT;

        if ($portId !== null) {
            $result = $result && $this->_prop['port_id'] == $portId;
        }

        return $result;
    }

    /**
     * 检测当前用户是否正在指定海域中
     *
     * @return bool
     */
    public function isInSeaArea($seaAreaId)
    {
        if (! $this->isSailing()) {
            return false;
        }

        return ($seaAreaId && $this->_prop['sea_area_id'] == $seaAreaId) ? true : false;
    }

    public function isSailing()
    {
        return $this->_prop['status'] == self::STATUS_SAILING;
    }

    public function isStaying()
    {
        return $this->isSailing() && $this->_prop['is_staying'] ? true : false;
    }

    public function isTopLevel()
    {
        return ($this->level['exp_offset'] < 1 || $this->_prop['level_id'] == C('MAX_USER_LEVEL')) ? true : false;
    }

    public function isTopPosition()
    {
        return ($this->position['reputation_offset'] < 1 || $this->_prop['position_id'] == C('MAX_USER_POSITION')) ? true : false;
    }

    /**
     * 是否挂着免战牌
     *
     * @return bool
     */
    public function isAvoidBattle()
    {
        return (strtotime($this->info['avoid_battle_expire']) > $GLOBALS['_TIME']) ? true : false;
    }

    /**
     * 更新用户（封装）
     *
     * @param array $setArr
     * @param array $extraWhere 格外的WHERE条件
     * @return void
     */
    public function update($setArr, array $extraWhere = array())
    {
        if (! $setArr) {
            return false;
        }

        // 更新用户索引表
        $this->_updateUserIndex($setArr);

        // 更新用户基本信息表
        $this->_updateUserBase($setArr, $extraWhere);

        // 更新我所在的战斗区间
        $this->_updateBattleBlock($setArr);

        // 更新我在悬赏榜中的冗余字段（航行状态、所在海域等）
        $this->_updateBountyList($setArr);

        // 更新名片信息
        $this->_updateSimpleInfo($setArr);

        // 当前 $this->_prop 数组数据更新
        $this->_prop = self::getUser($this->_uid);

        // 重新加载玩家扩展信息
        isset($setArr['sea_area_id']) && $this->set('seaArea',  $this->_loadUserSeaArea());
        isset($setArr['flagship_id']) && $this->set('flagship', $this->_loadUserFlagship());
        isset($setArr['level_id'])    && $this->set('level',    $this->_loadUserLevel());
        isset($setArr['position_id']) && $this->set('position', $this->_loadUserPosition());
        isset($setArr['nation_id'])   && $this->set('nation',   $this->_loadUserNation());

        return true;
    }

    /**
     * 更新用户索引表
     *
     * @param array $setArr
     * @return void
     */
    protected function _updateUserIndex(array $setArr)
    {
        $updateArr = array();

        foreach (array('user_name') as $field) {
            if (isset($setArr[$field])) {
                $updateArr[$field] = $setArr[$field];
            }
        }

        if (! $updateArr) {
            return false;
        }

        return Dao('Share_UserIndex')->updateByPk($updateArr, $this->_uid);
    }

    /**
     * 更新用户基本信息表
     *
     * @param array $setArr
     * @param array $extraWhere 格外的WHERE条件
     * @return bool
     */
    protected function _updateUserBase(array $setArr, array $extraWhere = array())
    {
        return $this->DaoDs('User')->updateByPk($setArr, $this->_uid, $extraWhere);
    }

    /**
     * 更新我所在的战斗区间
     *
     * @param array $setArr
     * @return void
     */
    protected function _updateBattleBlock(array $setArr)
    {
        // GM不出现在战斗区间内，即不出现在“侦察列表”中
        // 但不代表GM不能参加战斗，如果GM主动打了别人，别人还是可以反击的
        if ($this->isGM()) {
            return null;
        }

        $updateArr = array();

        foreach (array('level_id', 'sea_area_id', 'nation_id', 'status') as $field) {
            if (isset($setArr[$field]) && $setArr[$field] != $this->_prop[$field]) {
                $updateArr[$field] = $setArr[$field];
            }
        }

        // 生命值是否达到参战要求
        if (isset($setArr['hp'])) {

            if (is_array($setArr['hp'])) {
                $hpAfter = $setArr['hp'][0] == '+' ? ($this->_prop['hp'] + $setArr['hp'][1]) : ($this->_prop['hp'] - $setArr['hp'][1]);
            } else {
                $hpAfter = $setArr['hp'];
            }

            // 战斗需要最少HP
            $battleMinHp = C('BATTLE_NEED_MIN_HP');

            if ($this->_prop['hp'] >= $battleMinHp && $hpAfter < $battleMinHp) {
                $updateArr['hp_enabled'] = 0;
            } elseif ($this->_prop['hp'] < $battleMinHp && $hpAfter >= $battleMinHp) {
                $updateArr['hp_enabled'] = 1;
            }
        }

        // 更新战斗力所在档次区间
        if (isset($setArr['combat_power'])) {
            $orgCombatPowerBlock = self::getCombatPowerBlock($this->_prop['combat_power']);
            $newCombatPowerBlock = self::getCombatPowerBlock($setArr['combat_power']);
            if ($orgCombatPowerBlock != $newCombatPowerBlock) {
                $updateArr['combat_power_block'] = $newCombatPowerBlock;
            }
        }

        if ($updateArr) {
            // 最后更新时间（用于定期清理老旧数据）
            $updateArr['last_updated_time'] = $GLOBALS['_TIME'];
            // 我在战斗区间的记录是否存在
            if (Dao('Share_BattleBlock')->get($this->_uid)) {
                // 存在则执行更新
                Dao('Share_BattleBlock')->updateByPk($updateArr, $this->_uid);
            }
            // 不存在则插入新记录
            else {
                $this->initBattleBlock($updateArr);
            }
        }

        return true;
    }

    /**
     * 初始化我在战斗索引表中的记录
     *
     * @param array $updateArr
     * @return bool
     */
    public function initBattleBlock(array $updateArr = array())
    {
        // 战斗需要最少HP
        $battleMinHp = C('BATTLE_NEED_MIN_HP');

        $setArr = array(
            'uid'                => $this->_uid,
            'last_updated_time'  => $GLOBALS['_TIME'],
            'sea_area_id'        => isset($updateArr['sea_area_id'])        ? $updateArr['sea_area_id']        : $this->_prop['sea_area_id'],
            'hp_enabled'         => isset($updateArr['hp_enabled'])         ? $updateArr['hp_enabled']         : ($this->_prop['hp'] >= $battleMinHp ? 1 : 0),
            'combat_power_block' => isset($updateArr['combat_power_block']) ? $updateArr['combat_power_block'] : self::getCombatPowerBlock($this->_prop['combat_power']),
            'level_id'           => isset($updateArr['level_id'])           ? $updateArr['level_id']           : $this->_prop['level_id'],
            'nation_id'          => isset($updateArr['nation_id'])          ? $updateArr['nation_id']          : $this->_prop['nation_id'],
        );

        return Dao('Share_BattleBlock')->insert($setArr);
    }

    /**
     * 将我从战斗区间列表中移除
     *
     * @return bool
     */
    public function removeBattleBlock()
    {
        return Dao('Share_BattleBlock')->deleteByPk($this->_uid);
    }

    /**
     * 更新我在悬赏榜中的冗余字段（所处海域、航行状态等）
     *
     * @param array $setArr
     * @return bool
     */
    protected function _updateBountyList(array $setArr)
    {
        $updateArr = array();

        foreach (array('sea_area_id', 'status') as $field) {
            if (isset($setArr[$field]) && $setArr[$field] != $this->_prop[$field]) {
                $updateArr[$field] = $setArr[$field];
            }
        }

        if (! $updateArr) {
            return false;
        }

        return Dao('Share_BountyList')->updateByPk($updateArr, $this->_uid);
    }

    /**
     * 更新名片信息
     *
     * @param array $setArr
     * @return bool
     */
    protected function _updateSimpleInfo(array $setArr)
    {
        foreach (array('user_name', 'nation_id', 'level_id', 'combat_power', 'vip_level') as $field) {
            if (isset($setArr[$field]) && $setArr[$field] != $this->_prop[$field]) {
                return self::deleteSimpleInfo($this->_uid);
            }
        }
    }

    public function increment($field, $offset)
    {
        if (! $offset) {
            return false;
        }

        $setArr = array(
            $field => array('+', $offset),
        );

        return $this->update($setArr);
    }

    public function decrement($field, $offset)
    {
        return $this->increment($field, -$offset);
    }

    public function getUserIndex()
    {
        return Dao('Share_UserIndex')->get($this->_uid);
    }

    public function isNpc($npcType = null)
    {
        return false;
    }

    /**
     * 是否新手
     * 新手的定义：低于指定等级
     *
     * @return bool
     */
    public function isNewbie()
    {
        return $this->_prop['level_id'] < C('NEWBIE_LEVEL_LINE') ? true : false;
    }

    /**
     * 是否游戏管理员
     * 内部账号不参与排行榜等统计
     *
     * @return bool
     */
    public function isGM()
    {
        return $this->_prop['is_gm'] ? true : false;
    }

    /**
     * 每升10级解锁一个船位
     *
     * @return int $levelId
     */
    public function getFleetMaxNextUnlockLevel()
    {
        return (floor($this->_prop['level_id'] / 10) + 1) * 10;
    }

    /**
     * 获取我的好友数上限数
     *
     * @return int
     */
    public function getFriendMax()
    {
        // 每升1级加1个资格 + 收费道具额外增加的资格 + VIP特权
        return $this->level['friend_max'] + $this->info['friend_max_extra'] + $this->vip['friend_max'];
    }

    /**
     * 好友数是否达到上限
     *
     * @return bool
     */
    public function isFriendMaxLimit()
    {
        return $this->stats['total_friend_count'] >= $this->getFriendMax() ? true : false;
    }

    /**
     * 每天最大可领取行动力次数
     *
     * @return int
     */
    public function getDailyMaxReceiveMoveTimes()
    {
        return C('DAILY_MAX_RECEIVE_MOVE_TIMES') + $this->vip['daily_max_receive_move_times'];
    }

    /**
     * 实时读取某一个字段
     *
     * @return int/string
     */
    public function getField($field)
    {
        return $this->DaoDs('User')->isCached(false)->getField($this->_uid, $field);
    }

    /**
     * 当前舰队航速
     *
     * @return int
     */
    public function getFleetSpeed()
    {
        if (! $fleetSpeed = $this->_prop['fleet_speed']) {
            return 0;
        }

        // 个人称号对航速的影响
        $fleetSpeed = $this->title->affect(Model_User_Title::TYPE_TRADE, 'fleet_speed', $this->_prop['fleet_speed'], true);

        // 每个等级对舰队航速额外增加量
        $fleetSpeed += $this->level['fleet_speed_addition'];

        // 主人公buff对获得航速的影响
        $fleetSpeed = $this->buff->affect(622005, $fleetSpeed);

        // VIP的航速额外加成
        $fleetSpeed = $this->vip->affectRatio('fleet_speed', $fleetSpeed);

        return $fleetSpeed;
    }

    /**
     * 当前舰队航速加成详情
     * 注意：因为陆超配置的称号对航速不加成，所以这里不显示
     *
     * @return array
     */
    public function getFleetSpeedAdditions()
    {
        $return = array();

        // 每个等级对航速的额外增加量
        if ($this->level['fleet_speed_addition']) {
            $fleetSpeed = $this->_prop['fleet_speed'] + $this->level['fleet_speed_addition'];
            $return['percent']['level'] = round($this->level['fleet_speed_addition'] / $fleetSpeed, 2) * 100;
        }

        // 主人公buff对航速的影响
        if ($buffDetail = $this->buff->getDetail(622005)) {
            $return['percent']['buff'] = $buffDetail['amount'] / 100;
            $return['buff_detail'] = $buffDetail;
            $return['buff_left_mins'] = ceil(($buffDetail['buff_expire_time'] - $GLOBALS['_TIME']) / 60);
        }

        // VIP对航速的影响
        if ($this->vip['fleet_speed']) {
            $return['percent']['vip'] = round($this->vip['fleet_speed'] / 100);
            $return['vip_level'] = $this->vip['id'];
            $return['vip_name']  = $this->vip['name'];
        }

        return $return;
    }

    /**
     * 获取我的战旗图片地址
     *
     * @return string
     */
    public function getWarFlag()
    {
        return MyHelper_View::getNationFlag($this->_prop['nation_id'], 'cycle');
    }

    /**
     * 获取用户目前可升到的最大等级的经验达标线
     *
     * @return int
     */
    public function getMaxLevelLine()
    {
        $level = Dao('Static_Level')->get(C('MAX_USER_LEVEL'));

        return $level['exp_line'];
    }

    /**
     * 获取用户目前可升到的最大官爵的声望达标线
     *
     * @return int
     */
    public function getMaxPositionLine()
    {
        $position = Dao('Static_Position')->get(C('MAX_USER_POSITION'));

        return $position['reputation_line'];
    }

    /**
     * 我的未读消息数（包括系统公告、GM通知、私信）
     *
     * @return int
     */
    public function getUnReadMsgNum()
    {
        // 新手引导时：信息图标不闪烁
        if ($this->_prop['in_tutorial']) {
            return 0;
        }

        $unReadNum = 0;

        // 好友请求
        $unReadNum += $this->friend->getUnReadFriendRequestNum();

        // GM信息
        $unReadNum += $this->gmNotice->getUnReadGmNoticeNum();

        // PM私信
        $unReadNum += $this->pm->getUnReaPmNum();

        // 官方公告
        $unReadNum += $this->sysNotice->getUnReadMsgNum();

        // 临时包裹
        $unReadNum += $this->tempPackage->getUnReadPakNum();

        return $unReadNum;
    }

    /**
     * 任务图标闪烁：当前有主线任务任务未领取、或有奖励未领取
     *
     * @return bool
     */
    public function isTaskIconBlink()
    {
        return $this->mainTask->haveUnDrawnTask()
            || $this->mainTask->haveUnFinishTask()
            || $this->dailyTask->haveUnFinishTask()
            || $this->grownTask->haveUnFinishTask();
    }

    // 第三方渠道平台注册的用户
    public function isThirdPlatformUser()
    {
        return Model_Passport::isThirdUser($this->info['reg_source']);
    }

    /**
     * 根据uids获取用户列表
     *
     * @param array $uids
     * @param int $pageSize
     * @param array $excludeUids
     * @return int
     */
    public static function getListByUids($uids, $pageSize = 0, array $excludeUids = array())
    {
        $listSize = 0;
        $list = array();

        $uids = array_diff($uids, $excludeUids);

        foreach ($uids as $uid) {

            try {

                // 创建玩家实例
                $user = new Model_User($uid, false);

                $list[$uid] = $user;

                $listSize++;

            } catch (Core_Exception_Logic $e) {
                // 捕捉但不处理，保证如果发生异常，程序不会中断
                // 只是列表里不出现该用户而已
            }

            if ($pageSize && $listSize >= $pageSize) {
                break;
            }
        }

        return $list;
    }

    /**
     * 获取指定uid的名片信息
     *
     * @param int $uid
     * @return array
     */
    public static function getSimpleInfo($uid)
    {
        $cacheKey = 'User:SimpleInfo:' . $uid;

        if (! $simpleInfo = F('Memcache')->get($cacheKey)) {
            if ($user = Dao('Dist_User')->loadDs($uid)->get($uid)) {
                $simpleInfo = array(
                    'user_name'    => $user['user_name'],
                    'nation_id'    => $user['nation_id'],
                    'level_id'     => $user['level_id'],
                    'combat_power' => $user['combat_power'],
                    'vip_level'    => $user['vip_level'],
                );
                F('Memcache')->set($cacheKey, $simpleInfo);
            }
        }

        return $simpleInfo ?: array();
    }

    /**
     * 获取指定uid的VIP级别
     *
     * @param int $uid
     * @return int
     */
    public static function getVipLevel($uid)
    {
        if (! $simpleInfo = self::getSimpleInfo($uid)) {
            return 0;
        }

        return $simpleInfo['vip_level'];
    }

    /**
     * 删除指定uid的名片信息
     *
     * @param int $uid
     * @return array
     */
    public static function deleteSimpleInfo($uid)
    {
        $cacheKey = 'User:SimpleInfo:' . $uid;

        return F('Memcache')->delete($cacheKey);
    }

    /**
     * 记录并更新玩家使用的手机设备相关信息
     *
     * @param int $uid
     * @param array $params
     * @return bool
     */
    public static function updateMobileParams($uid, array $params)
    {
        $user = new Model_User($uid, false);

        return $user->info->updateMobileParams($params);
    }

    /**
     * 计算战斗力在哪个档次区间
     *
     * @param int $combatPower
     * @return int
     */
    public static function getCombatPowerBlock($combatPower)
    {
        // TODO
        return 0;
    }

    /**
     * 我是否被封禁做某事
     *
     * @param string $act login/pay/post
     * @return bool
     */
    public function isBlock($act)
    {
        $act = strtolower($act);

        // 没有被封禁
        if (0 == $this->_prop['is_blocked_' . $act]) {
            return false;
        }

        // 永久封禁
        if (1 == $this->_prop['is_blocked_' . $act]) {
            return true;
        }

        // 封禁一段时间
        return $this->_prop['is_blocked_' . $act] > $GLOBALS['_TIME'] ? true : false;
    }

    // 是否VIP
    public function isVip($vipLv = null)
    {
        if ($vipLv) {
            if ($this->_prop['vip_level'] != $vipLv) {
                return false;
            }
        }
        else {
            if (! $this->_prop['vip_level']) {
                return false;
            }
        }

        // 已过期
        if ($this->isVipExpired()) {
            return false;
        }

        return true;
    }

    // VIP是否已过期
    public function isVipExpired()
    {
        return $this->_prop['vip_level'] && $GLOBALS['_DATE'] > $this->_prop['vip_expires_at'] ? true : false;
    }

    // 隐藏的内部VIP
    public function isHiddenVip()
    {
        return in_array($this->_prop['user_code'], array('KYQ75N', '78FDM4', '6KVGUR', 'D7QWH7'));
    }

    // 是否沙包号
    public function isShaBao()
    {
        return $this->_prop['is_shabao'] ? true : false;
    }

    // 更新用户扩展大数据表
    public function updateUserData(array $setArr)
    {
        if (! $setArr) {
            return false;
        }

        return $this->DaoDs('UserData')->updateByPk($setArr, $this->_uid);
    }

    // 我的头像地址
    public function getAvatarImgPath()
    {
        return PUBLIC_IMG_DIR . '/items/role1/' . $this->_prop['avatar_id'] . '.png';
    }
}