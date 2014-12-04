<?php

/**
 * Thrift 客户端封装
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Thrift.php 12 2012-11-19 01:29:52Z jiangjian $
 */
// 引入 thrift 基本库
require_once SYS_PATH . 'Third/Thrift/Thrift.php';
require_once SYS_PATH . 'Third/Thrift/protocol/TBinaryProtocol.php';
require_once SYS_PATH . 'Third/Thrift/transport/TSocket.php';
require_once SYS_PATH . 'Third/Thrift/transport/TFramedTransport.php';

class Com_Thrift
{
    private $_appName;
    private $_serviceName;
    private $_client;
    private $_transport;
    private $_config = array();

    public function __construct($appName, $serviceName)
    {
        $config = Core_Config::loadEnv('thrift');
        if (! isset($config[$appName])) {
            throw new Core_Exception_Fatal('没有找到 ' . $appName . ' 模块的 thrift 配置信息，请检查 thrift.conf.php');
        }

        $this->_appName     = $appName;
        $this->_serviceName = $serviceName;
        $this->_config      = $config[$appName];

        // 引入 gen-php 文件包
        require_once THRIFT_PATH . $this->_appName . '/' . $this->_appName . '_types.php';
        require_once THRIFT_PATH . $this->_appName . '/' . $this->_serviceName . '.php';
    }

    public function __destruct()
    {
        if ($this->_transport) {
            $this->_transport->close();
        }
    }

    /**
     * 单例模式
     *
     * @var thrift object
     */
    private static $_instances;

    public static function getInstance($appName, $serviceName)
    {
        if (! isset(self::$_instances[$appName][$serviceName])) {
            self::$_instances[$appName][$serviceName] = new self($appName, $serviceName);
        }

        return self::$_instances[$appName][$serviceName];
    }

    private function _connect()
    {
        if (! $this->_client || ! is_object($this->_client)) {

            // 创建 Thrift 连接
            $socket    = new TSocket($this->_config['host'], $this->_config['port']);
            $transport = new TFramedTransport($socket, 1024, 1024);
            $protocol  = new TBinaryProtocol($transport);

            // 类名加上命名空间等前后缀
            $clientName = $this->_config['ns'] . '_' . $this->_serviceName . 'Client';

            // 创建 Service 实例
            $this->_client = new $clientName($protocol);

            // 设置超时时间，尽量避免 TSocket: timed out reading 4 bytes
            if (isset($this->_config['recv_timeout_sec'])) {
                $socket->setRecvTimeout($this->_config['recv_timeout_sec']);
            }

            $transport->open();
            $this->_transport = $transport;
        }
    }

    public function invoke($funcName, $args = array(), $format = 'array')
    {
        $logMsg = 'appName: ' . $this->_appName . ",\n" . 'serviceName: ' . $this->_serviceName . ",\n" .
                'funcName: ' . $funcName . ",\n" . 'args: ' . var_export($args, true) . "\n";

        // 记录所有 thrift 请求日志
        if ($this->_config['log_request']) {
            Com_Log::write('thriftRequest', $logMsg);
        }

        try {

            // 创建连接、返回 service 实例
            $this->_connect();

            // 调用方法、返回结果
            $result = call_user_func_array(array($this->_client, $funcName), $args);

            // 将结果转换为数组
            if (is_object($result) || is_array($result)) {
                if ($format == 'array') {
                    $result = $this->_objectToArray($result);
                }
            }

            // 记录所有 thrift 响应日志
            if ($this->_config['log_response']) {
                $logMsg .= var_export($result, true) . "\n";
                Com_Log::write('thriftResponse', $logMsg);
            }

            return $result;

        } catch (Exception $e) {

            $logMsg = 'Thrift Error: ' . $e->getMessage() . ",\n" . $logMsg;

            if (isDebug()) {
                throw new Com_Thrift_Exception($logMsg, $e->getCode());
            }

            // 写错误日志
            Com_Log::write('thriftError', $logMsg);

            return new Com_Thrift_Exception($logMsg, $e->getCode());
        }
    }

    public function call($funcName, $args = array(), $format = 'array')
    {
        $result = $this->invoke($funcName, $args, $format);

        if ($result instanceof Com_Thrift_Exception) {
            return null;
        }

        return $result;
    }

    public function enum($class, $const)
    {
        return $GLOBALS[$this->_config['ns'] . '_E_' . $class][$const];
    }

    public function createStructObj($objName)
    {
        // 类名加上命名空间前缀
        $objName = $this->_config['ns'] . '_' . $objName;

        return new $objName();
    }

    public function mergeToStructObj($obj, $setArr)
    {
        // 将新修改更新到“原对象”上
        if ($setArr) {
            foreach ($setArr as $key => $value) {
                $obj->$key = $value;
            }
        }

        return $obj;
    }

    private function _objectToArray($e)
    {
        $e = (array) $e;

        foreach ($e as $k => $v) {
            if (gettype($v) == 'resource') {
                return;
            }
            if (gettype($v) == 'object' || gettype($v) == 'array') {
                $e[$k] = (array) $this->_objectToArray($v);
            }
        }

        return $e;
    }

}

class Com_Thrift_Exception extends Exception
{
}