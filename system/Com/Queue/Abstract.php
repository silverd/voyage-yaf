<?php

/**
 * 队列实例-抽象父类
 *
 * @author JiangJian <silverd@sohu.com>
 */

abstract class Com_Queue_Abstract
{
    /**
     * 队列名称
     * Redis List 键名
     *
     * @var string
     */
    protected $_queueName;

    /**
     * 队列使用哪一组 Redis/Memcache 配置
     *
     * @var string
     */
    protected $_configGroup = 'queue';

    /**
     * 队列存储器名称
     *
     * @var string
     */
    protected $_adapter = 'RedisQ';

    /**
     * 队列存储器实例
     *
     * @var Com_Queue_Adapter_RedisQ
     */
    protected $_storage;

    public function __construct()
    {
        $className = 'Com_Queue_Adapter_' . $this->_adapter;

        $this->_storage = new $className($this->_queueName, $this->_configGroup);
    }

    public function push($value)
    {
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $this->_storage->push($value);
    }

    public function pop()
    {
        return $this->_storage->pop();
    }

    public function count()
    {
        return $this->_storage->count();
    }

    public function clear()
    {
        return $this->_storage->clear();
    }

    public function view()
    {
        return $this->_storage->view();
    }

    public function getName()
    {
        return $this->_queueName;
    }

    // 从队列弹出前的一些检测操作
    // 例如：红包队列每天0点~8点开始睡眠，不弹出处理任务（因为微信API规定这段时间禁止发红包）
    public function prePop()
    {

    }

    // 处理从队列弹出的一个任务
    public function postPop($oneTask)
    {
        return [
            'is_ok'      => 0,
            'return_msg' => '',
        ];
    }

    // 记录一个任务的处理结果
    public function log($oneTask, array $result)
    {
        $logInfo = [
            'model_name' => get_called_class(),
            'queue_name' => $this->_queueName,
            'org_infos'  => $oneTask,
            'is_ok'      => intval($result['is_ok']),
            'return_msg' => $result['return_msg'] ? json_encode($result['return_msg'], JSON_UNESCAPED_UNICODE) : '',
            'created_at' => date('Y-m-d H:i:s'),    // 必须实时获取，因为队列是守护进程
        ];

        return Com_Logger_Redis::custom('queue', $logInfo);
    }
}