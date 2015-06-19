<?php

/**
 * 通用日志处理器-存入MySQL
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Com_Logger_MySQL extends Com_Logger_Abstract
{
    protected static function _log($logName, $content, $logLevel)
    {
        $data = [
            'name'       => $logName,
            'level'      => $logLevel,
            'content'    => is_array($content) ? json_encode($content, JSON_UNESCAPED_UNICODE) : $content,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        return Dao('Core_LogCommon')->insert($data);
    }
}