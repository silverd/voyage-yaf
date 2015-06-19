<?php

/**
 * DB 异常处理
 *
 * @author JiangJian
 * $Id: Exception.php 7959 2014-01-13 13:18:56Z jiangjian $
 */

class Com_DB_Exception extends Core_Exception_SQL
{
    /**
     * 记录异常处理日志
     *
     * @param Exception $e
     * @param string $msg
     * @param array $sqlInfo
     * @return bool
     */
    public static function process(Exception $e, $msg, array $sqlInfo = [])
    {
        $msg .= PHP_EOL;

        if ($sqlInfo) {
            $msg .= self::__sqlInfoToString($sqlInfo) . PHP_EOL;
        }

        $msg .= $e->getMessage() . PHP_EOL;

        foreach ($e->getTrace() as $key => $trace) {
            if (! isset($trace['file']) && ! isset($trace['line'])) {
                continue;
            }
            $msg .= ($key + 1) . ' File:' . $trace['file']. ' Line:' . $trace['line'] . PHP_EOL;
        }

        // 写错误日志
        Com_Logger_Redis::error('sqlErrors', $msg);

        // 调试模式直接输出
        if (isDebug()) {
            throw new self($msg);
        }
    }

    private static function __sqlInfoToString(array $sqlInfo)
    {
        if (! $sqlInfo) {
            return null;
        }

        $return = 'SQL: ' . $sqlInfo['sql'];

        if ($sqlInfo['params']) {
            $return .= ' [' . implode(',', $sqlInfo['params']) . ']';
            $return .= PHP_EOL . 'RealSQL: ' . $sqlInfo['realSql'];
        }

        return $return . PHP_EOL . 'Host: ' . $sqlInfo['host'] . ', Database: ' . $sqlInfo['dbName'];
    }
}