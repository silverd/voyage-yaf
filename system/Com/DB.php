<?php

/**
 * 数据库工厂
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: DB.php 8115 2014-01-22 03:52:43Z jiangjian $
 */

class Com_DB
{
    private static $_dbs = array();

    /**
     * 是否写SQL日志
     *
     * @var bool
     */
    private static $_enableLogging = true;

    /**
     * 取得DB连接
     *
     * @param string $dbName
     * @param string $hashKey
     * @param bool $forceMaster 强制主库
     * @return void
     */
    public static function get($dbName, $hashKey = '', $forceMaster = false)
    {
        // 连接配置
        $dbConf = Core_Config::loadEnv('db');

        if (! $dbConf) {
            throw new Com_DB_Exception('Empty dbconf, plz check: db.conf.php');
        }

        // 数据库连接配置信息
        $dbServers = isset($dbConf[$dbName]) ? $dbConf[$dbName] : array();

        // 如果分库，那某个分库可指定独立的连接配置，不指定则仍用主配置
        $dbName = Com_DB_Hash::dbName($dbName, $hashKey);
        if (isset($dbConf[$dbName])) {
            $dbServers = $dbConf[$dbName];
        }

        if (! $dbServers || ! is_array($dbServers)) {
            throw new Com_DB_Exception('Invalid DB configuration [' . $dbName . '], plz check: db.conf.php');
        }

        // 已创建的实例
        $dbKey = $dbName . '_' . intval($forceMaster);
        if (! isset(self::$_dbs[$dbKey])) {

            ! isset($dbServers['master']) && $dbServers['master'] = array();
            ! isset($dbServers['slave'])  && $dbServers['slave']  = array();

            // 创建数据库连接实例
            self::$_dbs[$dbKey] = new Com_DB_PDO(
                $dbServers['master'],
                $dbServers['slave'],
                $forceMaster,
                $dbConf['persistent'],
                $dbConf['emulate_prepare']
            );
        }

        return self::$_dbs[$dbKey];
    }

    /**
     * 设置是否写SQL日志
     *
     * @return bool
     */
    public static function enableLogging($bool = null)
    {
        if (null !== $bool) {
            self::$_enableLogging = (bool) $bool;
        }

        // 非调试模式下永远为否
        return ! isDebug() ? false : self::$_enableLogging;
    }

    /**
     * 释放所有DB连接
     *
     * @return void
     */
    public static function disconnect()
    {
        if (self::$_dbs && is_array(self::$_dbs)) {
            foreach (self::$_dbs as $db) {
                $db->disconnect();
            }
            self::$_dbs = null;
        }
    }

    /**
     * 记录 SQL 执行日志（作用：供开发环境下打印SQL语句）
     *
     * @param array $sqlInfo
     * @param int $sqlCostTime
     * @param array $explainResult
     * @return void
     */
    public static function sqlLog($sqlInfo, $sqlCostTime, $explainResult = array())
    {
        $sqlInfo['time']    = $sqlCostTime;
        $sqlInfo['explain'] = $explainResult;
        $GLOBALS['_SQLs'][] = $sqlInfo;
    }

    /**
     * 将 SQL 语句中的 ? 替换为实际值
     *
     * @param string $sql
     * @param array $params
     * @return string
     */
    public static function getRealSql($sql, $params = array())
    {
        if ($params && is_array($params)) {
            while (strpos($sql, '?') > 0) {
                $sql = preg_replace('/\?/', "'" . array_shift($params) . "'", $sql, 1);
            }
        }

        return $sql;
    }

    /**
     * 对 MYSQL LIKE 的内容进行转义
     * @thanks ZhangYanJiong
     *
     * @param string string
     * @return string
     */
    public static function likeQuote($str)
    {
        return strtr($str, array("\\\\" => "\\\\\\\\", '_' => '\_', '%' => '\%', "\'" => "\\\\\'"));
    }

    /**
     * 高级DB查询
     *
     * @param array $data
     * @return mixed
     */
    public static function advQuery(array $data)
    {
        // 强制调试模式
        $_REQUEST['__debug'] = true;

        $dbName = $data['db_name'];
        $sql = stripslashes($data['sql']);
        $method = $data['method'];
        $forceMaster = isset($data['is_master']) && $data['is_master'] ? true : false;

        if (! $dbName || ! $sql || ! $method) {
            exit('Invalid advQuery Params');
        }

        $limit = isset($data['limit']) ? ($data['limit']) : 0;
        $limitSql = $limit > 0 ? ' LIMIT ' . $limit : ' LIMIT 1';
        $limitSql = $limit == -99 ? '' : $limitSql;

        try {
            $db = Com_DB::get($dbName);
            $result = $db->$method($sql . $limitSql, array(), $forceMaster);
        }
        catch (Exception $e) {
            print($e);
            exit;
        }

        // 打印结果
        $output = (isset($data['output']) && $data['output']) ? $data['output'] : 'print_r';

        echo '<pre>';
        $output($result);
        exit();
    }
}