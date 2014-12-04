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
     * @return boolean
     */
    public static function process($e, $msg, $sqlInfo = array())
    {
        $msg .= "\n";

        if ($sqlInfo) {
            $msg .= self::_sqlInfoToString($sqlInfo) . "\n";
        }

        $msg .= $e->getMessage() . "\n";

        foreach ($e->getTrace() as $key => $trace) {
            if (! isset($trace['file']) && ! isset($trace['line'])) {
                continue;
            }
            $msg .= ($key + 1) . ' File:' . $trace['file']. ' Line:' . $trace['line'] . "\n";
        }

        if (isDebug()) {
            throw new self($msg);
        }
    }

    private static function _sqlInfoToString($sqlInfo)
    {
        if (! $sqlInfo) {
            return null;
        }

        $return = 'SQL: ' . $sqlInfo['sql'];

        if ($sqlInfo['params']) {
            $return .= ' [' . implode(',', $sqlInfo['params']) . ']';
            $return .= "\nRealSQL: " . $sqlInfo['realSql'];
        }

        return $return . "\nHost: " . $sqlInfo['host'] . ', Database: ' . $sqlInfo['dbName'];
    }
}