<?php

/**
 * 通用日志处理器-存入Redis
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Com_Logger_Redis extends Com_Logger_Abstract
{
    const KEY_PREFIX = 'LOG:';

    protected static function _getStorage()
    {
        return F('Redis')->logs;
    }

    protected static function _log($logName, $content, $logLevel)
    {
        $data = json_encode([
            'created_at' => date('Y-m-d H:i:s'),
            'level'      => $logLevel,
            'content'    => $content,
        ], JSON_UNESCAPED_UNICODE);

        return self::_getStorage()->rpush(self::KEY_PREFIX . $logName, $data);
    }

    protected static function _getDaoName($logName)
    {
        $logName = str_replace(self::KEY_PREFIX, '', $logName);
        $daoName = 'Core_Log' . ucfirst($logName);

        return $daoName;
    }

    // 将日志导出到DB
    public static function exportToDb($logName)
    {
        // 防止误取，再次检测键名
        if (strpos($logName, self::KEY_PREFIX) !== 0) {
            return false;
        }

        $redis = self::_getStorage();

        $okCount = 0;

        // 每次处理1000条
        for ($i = 0; $i < 1000; $i++) {

            $data = $redis->lpop($logName);

            if (! $data = json_decode($data, true)) {
                break;
            }

            // 自定义日志表
            if ($data['level'] == 'CUSTOM') {

                // 自定义字段
                if (is_array($data['content'])) {

                    $setArr = $data['content'];

                    if (! isset($setArr['created_at'])) {
                        $setArr['created_at'] = $data['created_at'];
                    }
                }

                // 缺省字段
                else {

                    $setArr = [
                        'content'    => $data['content'] ? json_encode($data['content'], JSON_UNESCAPED_UNICODE) : '',
                        'created_at' => $data['created_at'],
                    ];
                }

                $daoName = self::_getDaoName($logName);

                // 如果对应DAO存在则执行入库
                if (@ class_exists('Dao_' . $daoName)) {
                    Dao($daoName)->insert($setArr);
                }
                // 否则塞回Redis然后报错中断
                else {
                    $redis->rpush($logName, json_encode($data, JSON_UNESCAPED_UNICODE));
                    throw new Exception('日志归档DAO不存在: ' . $daoName);
                }
            }

            // 通用日志表
            else {

                $setArr = [
                    'name'       => $logName,
                    'level'      => $data['level'] ?: '',
                    'content'    => $data['content'] ? json_encode($data['content'], JSON_UNESCAPED_UNICODE) : '',
                    'created_at' => $data['created_at'],
                ];

                Dao('Core_LogCommon')->insert($setArr);
            }

            $okCount++;
        }

        return $okCount;
    }

    public static function exportAllToDb()
    {
        $logNames = self::_getStorage()->keys(self::KEY_PREFIX . '*');

        if (! $logNames) {
            return false;
        }

        $result = [];

        foreach ($logNames as $logName) {
            try {
                if ($okCount = self::exportToDb($logName)) {
                    $result[$logName] = $okCount;
                }
            }
            catch (Exception $e) {
                Com_Logger_File::error('redisLogToDb', $e->getMessage());
            }
        }

        return $result;
    }
}