<?php

/**
 * 页面防刷组件
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: AntiRefresh.php 10385 2014-04-10 06:11:57Z jiangjian $
 */

class Com_AntiRefresh
{
    /**
     * 白名单（全小写）
     * 格式：module/controller/action
     *
     * @var array
     */
    private $_whiteList = array();

    public function __construct(array $whiteList = array())
    {
        $this->_whiteList = $whiteList;
    }

    /**
     * 两次请求间隔不得小于N毫秒
     *
     * @param  int $uid
     * @param  Yaf_Request_Abstract $request
     * @param  int $minInterval 两次请求最小间隔（单位：毫秒）
     * @param  string $namespace
     * @return bool
     */
    public function intervalReqLimit($uid, Yaf_Request_Abstract $request, $minInterval = 300, $namespace = null)
    {
        if ($uid < 1) {
            return true;
        }

        // 构造请求标识
        $routeStr = $this->_buildRouteStr($request);

        // 白名单忽略
        if ($this->_inWhiteList($routeStr)) {
            return true;
        }

        // 当前时间
        $currentTime = floor(microtime(true) * 1000);

        // 获取上次请求时间
        $cacheKey = md5('LastReqTime:' . $uid . ':' . $routeStr . ($namespace ? ':' . $namespace : ''));
        $lastReqTime = F('Memcache')->get($cacheKey);

        // 两次请求间隔不得少于N毫秒
        if ($lastReqTime && $currentTime - $lastReqTime < $minInterval) {
            return false;
        }

        // 更新请求时间
        F('Memcache')->set($cacheKey, $currentTime);

        return true;
    }

    /**
     * N秒最多累计接受M次请求
     * 例如：5秒内1个玩家最多访问10次
     *
     * @param  int $uid
     * @param  Yaf_Request_Abstract $request
     * @param  int $N 5秒内
     * @param  int $M 最多访问10次
     * @return bool
     */
    public function cumulativeReqLimit($uid, Yaf_Request_Abstract $request, $N = 1, $M = 5)
    {
        if ($uid < 1) {
            return true;
        }

        // 构造请求标识
        $routeStr = $this->_buildRouteStr($request);

        // 白名单忽略
        if ($this->_inWhiteList($routeStr)) {
            return true;
        }

        // 累加访问次数
        $cacheKey = md5('cumlReqCnt:' . $uid);
        $reqCount = F('Memcache')->increment($cacheKey, 1, $N);

        // 超过限制：N秒内1个设备号最多访问M次
        if ($reqCount > $M) {
            return false;
        }

        return true;
    }

    // 构造请求标识
    private function _buildRouteStr(Yaf_Request_Abstract $request)
    {
        $module     = $request->getModuleName();
        $controller = $request->getControllerName();
        $action     = $request->getActionName();

        return strtolower($module . '/' . $controller . '/' . $action);
    }

    // 白名单忽略
    private function _inWhiteList($routeStr)
    {
        if (! $this->_whiteList) {
            return false;
        }

        return isset($this->_whiteList[$routeStr]);
    }

    // 清除请求间隔
    public static function clearReqInterval($uid, Yaf_Request_Abstract $request)
    {
        if ($uid < 1) {
            return false;
        }

        $cacheKey = md5('LastReqTime:' . $uid);

        // 更新上次请求时间
        F('Memcache')->delete($cacheKey);
    }
}