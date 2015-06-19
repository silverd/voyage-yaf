<?php

/**
 * 通用日志处理器
 *
 * @author JiangJian
 * $Id: Exception.php 7959 2014-01-13 13:18:56Z jiangjian $
 */

abstract class Com_Logger_Abstract
{
    /**
     * 写日志
     *
     * @param string $logName
     * @param string $content
     * @param string $logLevel
     *
     * @return bool
     */
    protected static function _log($logName, $content, $logLevel)
    {
    }

    // Emergency: system is unusable
    public static function emergency($logName, $content)
    {
        return static::_log($logName, $content, 'EMERGENCY');
    }

    // Alert: action must be taken immediately
    public static function alert($logName, $content)
    {
        return static::_log($logName, $content, 'ALERT');
    }

    // Critical: critical conditions
    public static function critical($logName, $content)
    {
        return static::_log($logName, $content, 'CRITICAL');
    }

    // Error: error conditions
    public static function error($logName, $content)
    {
        return static::_log($logName, $content, 'ERROR');
    }

    // Warning: warning conditions
    public static function warning($logName, $content)
    {
        return static::_log($logName, $content, 'WARNING');
    }

    // Notice: normal but significant condition
    public static function notice($logName, $content)
    {
        return static::_log($logName, $content, 'NOTICE');
    }

    // Informational: informational messages
    public static function info($logName, $content)
    {
        return static::_log($logName, $content, 'INFO');
    }

    // Debug: debug messages
    public static function debug($logName, $content)
    {
        return static::_log($logName, $content, 'DEBUG');
    }

    // 自定义日志类型
    public static function custom($logName, $content)
    {
        return static::_log($logName, $content, 'CUSTOM');
    }
}