<?php

/**
 * 操作Linux系统相关命令
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Daemon.php 12 2012-11-19 01:29:52Z jiangjian $
 */

class Com_Daemon
{
    /**
     * 执行 nohup 操作
     *
     * @param string $bin         命令路径，如 /usr/bin/php
     * @param string $script      脚本路径，如 /xianglephp/cli.php
     * @param string $outputPath  输入日志路径
     * @return void
     */
    public static function nohup($bin, $script, $outputPath = '')
    {
        if (! empty($bin) && ! empty($script)) {
            $count = self::unixProcessCount($bin, $script);
            if ($count < 1) {
                $execStr = "nohup $bin $script " . ($outputPath ? " > $outputPath " : '') . ' &';
                echo $execStr . PHP_EOL;
                self::cmd($execStr);
            }
        }
    }

    /**
     * 执行终端命令
     *
     * @param string $command
     * @return array
     */
    public static function cmd($command)
    {
        $output    = 'Command execution not possible on this system';
        $returnVar = 1;

        // system
        if (function_exists('system')) {

            ob_start();
            system($command, $returnVar);
            $output = ob_get_contents();
            ob_end_clean();

        // passthru
        } elseif (function_exists('passthru')) {

            ob_start();
            passthru($command, $returnVar);
            $output = ob_get_contents();
            ob_end_clean();

        // exec
        } elseif (function_exists('exec')) {

            exec($command, $output, $returnVar);
            $output = implode(PHP_EOL, $output);

        // shell_exec
        } elseif (function_exists('shell_exec')) {

            $output = shell_exec($command);
        }

        return array('output' => $output, 'status' => $returnVar);
    }

    /**
     * UNIX/LINUX 下获取正在运行的进程数量
     *
     * @param string $bin
     * @param string $script
     * @return int
     */
    public static function unixProcessCount($bin, $script)
    {
        self::cmd("ps -ef | grep '$script'", $output);

        $count = 0;
        if ($output) {
            foreach ($output as $value) {
                if (strstr($value, "$bin $script")) {
                    $count++;
                }
            }
        }

        return $count;
    }
}