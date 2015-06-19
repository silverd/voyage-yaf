<?php

/**
 * 通用日志处理器-存入文件
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Com_Logger_File extends Com_Logger_Abstract
{
    protected static function _log($logName, $content, $logLevel)
    {
        // 日志存放目录
        $LOG_DIR = LOG_PATH . date('Y-m-d');

        if (! is_dir($LOG_DIR)) {
            mkdir($LOG_DIR, 0777, true);
        }

        if (is_array($content)) {
            $content = var_export($content, true);
        }

        $content  = '[' . date('Y-m-d H:i:s') . '] ' . '[' . $logLevel . '] ' . $content . PHP_EOL;

        $fileName = $LOG_DIR . '/' . $logName . '.log';
        return file_put_contents($fileName, $content, FILE_APPEND | LOCK_EX);
    }
}