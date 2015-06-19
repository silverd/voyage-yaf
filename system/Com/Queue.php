<?php

/**
 * 队列经理人
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Queue.php 12 2012-11-19 01:29:52Z jiangjian $
 */

class Com_Queue
{
    /**
     * 设置队列守护进程
     * 死循环弹出队列元素并依次处理
     *
     * @param Com_Queue_Abstract $queue 队列实例
     * @return null
     */
    public static function setDaemon(Com_Queue_Abstract $queue)
    {
        while (true) {

            // 从队列弹出前的一些检测操作
            $queue->prePop();

            // 从队列弹出一个任务（JSON）
            $oneTask = $queue->pop();

            // 队列里没有任务则休息一会儿
            if (! $oneTask) {
                sleep(10);
                continue;
            }

            try {
                // 处理从队列弹出的一个任务
                $result = $queue->postPop($oneTask);
            }
            catch (Exception $e) {
                $result = [
                    'is_ok'      => 0,
                    'return_msg' => $e->getMessage(),
                ];
            }

            // 记录一个任务的处理结果
            $queue->log($oneTask, $result);
        }
    }
}