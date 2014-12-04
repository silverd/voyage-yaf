<?php

/**
 * 调试信息打印
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Debug.php 5560 2013-09-13 16:16:06Z jiangjian $
 */

class Core_Debug
{
    /**
     * Output for FirePHP
     * @uses http://www.firephp.org
     *
     * @return void
     */
    public static function firePHP()
    {
        // 页面汇总信息
        $table = array();
        $table[] = array('Total Time: '   . round(microtime(true) - $GLOBALS['_START_TIME'], 3));
        $table[] = array('Total Memory: ' . self::_size(round(memory_get_usage() - $GLOBALS['_START_MEM'], 3)));
        $table[] = array('Timezone: '     . date_default_timezone_get());
        isset($_SERVER['SERVER_ADDR'])    && $table[] = array('Server IP: ' . $_SERVER['SERVER_ADDR']);
        isset($_SERVER['HTTP_HOST'])      && $table[] = array('Server Host: ' . $_SERVER['HTTP_HOST']);
        FB::table('Debug Info', $table);

        // SQL
        if (isset($GLOBALS['_SQLs']) && ! empty($GLOBALS['_SQLs'])) {

            // SQL Info
            $table = array();
            $table[] = array('Elapse', 'Host', 'Database', 'SQL', 'Params', 'RealSQL');
            foreach ($GLOBALS['_SQLs'] as $v) {
                $elapsed = round(($v['time'] * 1000), 3);   // SQL 耗时
                if ($v['params']) {
                    $table[] = array($elapsed . 'ms', $v['host'], $v['dbName'], $v['sql'], $v['params'], $v['realSql']);
                } else {
                    $table[] = array($elapsed . 'ms', $v['host'], $v['dbName'], $v['sql']);
                }
            }
            FB::table('SQL Info (' . count($GLOBALS['_SQLs']) . ' SQLs)', $table);

            // SQL Explain
            if (DEBUG_EXPLAIN_SQL) {
                $titles = array('id', 'select_type', 'table', 'type', 'possible_keys', 'key', 'key_len', 'ref', 'rows', 'Extra', 'sql');
                $table = array($titles);
                foreach ($GLOBALS['_SQLs'] as $v) {
                    if ($v['explain']) {
                        $line = array();
                        foreach ($titles as $title) {
                            $line[] = isset($v['explain'][$title]) ? $v['explain'][$title] : '-';
                        }
                        $table[] = $line;
                    }
                }
                FB::table('SQL Explain', $table);
            }
        }

        // 引入文件
        /*
        if ($files = get_included_files()) {
            $table = array();
            foreach ($files as $file) {
                $table[] = array($file);
            }
            FB::table('Included Files (' . count($files) . ' PHP files)', $table);
        }
        */

        // 全局变量、预定义变量
        FB::group('Predefined Variables', array('Collapsed' => true));
        FB::info($_COOKIE, '_COOKIE');
        FB::info($_ENV, '_ENV');
        FB::info($_FILES, '_FILES');
        FB::info($_GET, '_GET');
        FB::info($_POST, '_POST');
        FB::info($_REQUEST, '_REQUEST');
        FB::info($_SERVER, '_SERVER');
        isset($_SESSION) && FB::info($_SESSION, '_SESSION');
        FB::groupEnd();
    }

    /**
     * Output for ChromePHP
     * @uses www.chromephp.com
     *
     * @todo
     * @return void
     */
    public static function chromePHP()
    {
    }

    /**
     * 格式化文件大小
     *
     * @param int $size
     * @return string
     */
    private static function _size($size)
    {
        if ($size >= 1073741824) {
            $size = round($size / 1073741824 * 100) / 100 . ' GB';
        } elseif ($size >= 1048576) {
            $size = round($size / 1048576 * 100) / 100 . ' MB';
        } elseif ($size >= 1024) {
            $size = round($size / 1024 * 100) / 100 . ' KB';
        } else {
            $size = $size . ' Bytes';
        }
        return $size;
    }
}