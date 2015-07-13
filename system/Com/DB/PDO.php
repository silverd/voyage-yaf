<?php

/**
 * PDO 数据库操作基类
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: PDO.php 7568 2013-12-25 06:54:01Z jiangjian $
 */

class Com_DB_PDO
{
    /**
     * 存放主库的连接
     *
     * @var object
     */
    protected $_writeDbConn = null;

    /**
     * 存放从库的连接
     *
     * @var object
     */
    protected $_readDbConn = null;

    /**
     * 存放当前DB连接
     *
     * @var object
     */
    protected $_db = null;

    /**
     * 主库连接配置信息
     *
     * @var array
     */
    protected $_writeConf = [];

    /**
     * 从库连接配置信息
     *
     * @var object
     */
    protected $_readConf = [];

    /**
     * 是否强制连接主库
     *
     * @var bool
     */
    protected $_forceMaster = false;

    /**
     * 是否进行长连接
     *
     * @var bool
     */
    protected $_persistent = false;

    /**
     * 是否启用仿真 (emulate) 预备义语句 (true)，否则使用原生 (native) 的预备义语句 (false)
     * 原生更加安全，确保语句在发送给 MySQL 服务器执行前被分析，如有语法错误会在 prepare 阶段就报错
     * 但仿真性能会更快，但有 SQL 注入风险 (例如表名为 ? 的情况)
     * 缺省设为 null 表示不改变 PDO::ATTR_EMULATE_PREPARES
     *
     * @var bool/null
     */
    protected $_emulatePrepare = null;

    /**
     * 构造函数，初始化配置
     *
     * @param array $writeConf
     * @param array $readConf
     * @param bool $forceMaster
     * @param bool $persistent
     * @param bool $emulatePrepare
     */
    public function __construct($writeConf, $readConf, $forceMaster = false, $persistent = false, $emulatePrepare = false)
    {
        $this->_writeConf       = $writeConf;
        $this->_readConf        = $readConf;
        $this->_forceMaster     = $forceMaster;
        $this->_persistent      = $persistent;
        $this->_emulatePrepare  = $emulatePrepare;
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * 获取主库的“写”数据连接
     *
     * @return PDO Object
     */
    protected function _getDbWriteConn()
    {
        // 判断是否已经连接
        if ($this->_writeDbConn && is_object($this->_writeDbConn)) {
            return $this->_writeDbConn;
        }

        $db = $this->_connect(parse_url($this->_writeConf));
        if (! $db || ! is_object($db)) {
            return false;
        }

        $this->_writeDbConn = $db;
        return $this->_writeDbConn;
    }

    /**
     * 获取从库的“读”数据连接
     *
     * @return PDO Object
     */
    protected function _getDbReadConn()
    {
        // 判断是否已经连接
        if ($this->_readDbConn && is_object($this->_readDbConn)) {
            return $this->_readDbConn;
        }

        // 没有从库配置则直接连主库
        if (! $this->_readConf){
            return $this->_getDbWriteConn();
        }

        // 乱序随机选择从库
        shuffle($this->_readConf);
        foreach ($this->_readConf as $slave) {
            $db = $this->_connect(parse_url($slave));
            if ($db && is_object($db)){
                $this->_readDbConn = $db;
                return $this->_readDbConn;
            }
        }
    }

    /**
     * 连接数据库
     *
     * @param array $conf
     * @return PDO Object
     */
    protected function _connect(array $conf)
    {
        try {

            $conf['path'] = trim($conf['path'], '/');
            ! isset($conf['port']) && $conf['port'] = '3306';

            $dsn = 'mysql:dbname=' . $conf['path'] . ';host=' . $conf['host'] . ';port=' . $conf['port'];

            $params = [];

            // 持久连接
            if ($this->_persistent) {
                $params[PDO::ATTR_PERSISTENT] = true;
            }

            $db = new PDO($dsn, $conf['user'], $conf['pass'], $params);

            // 仿真预备义语句（实际PDO默认为true）
            if ($this->_emulatePrepare != null) {
                $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, $this->_emulatePrepare);
            }

            // 设置编码
            $db->exec("SET NAMES UTF8");

            $db->dsn = $conf;

        } catch (PDOException $e) {

            Com_DB_Exception::process($e, '[Connection Failed] ' . $dsn);
            return false;
        }

        return $db;
    }

    /**
     * 释放数据库连接（释放写连接、读连接、临时连接）
     */
    public function disconnect()
    {
        $this->_writeDbConn = $this->_readDbConn = $this->_db = null;
    }

    /**
     * 选择数据库连接
     *
     * @param bool $forceMaster 是否强制连接主库
     * @return void
     */
    protected function _getChoiceDbConnect($forceMaster = false)
    {
        $forceMaster = ($forceMaster || $this->_forceMaster);
        $this->_db = $forceMaster ? $this->_getDbWriteConn() : $this->_getDbReadConn();
    }

    /**
     * 执行操作的底层接口
     *
     * @param string $sql
     * @param array $params
     * @param bool $forceMaster 是否强制连接主库
     * @return PDO Statement
     */
    protected function _autoExecute($sql, $params = [], $forceMaster = false)
    {
        try {

            $this->_getChoiceDbConnect($forceMaster);
            if (! $this->_db) {
                throw new Com_DB_Exception('DB connection lost.');
            }

            // 调试模式打印SQL信息
            $explain = [];
            if (Com_DB::enableLogging() && DEBUG_EXPLAIN_SQL) {
                $explain = $this->_explain($sql, $params);
            }

            $sqlStartTime = microtime(true);

            // 预编译 SQL
            $stmt = $this->_db->prepare($sql);
            if (! $stmt) {
                throw new Com_DB_Exception(implode(',', $this->_db->errorInfo()));
            }

            // 绑定参数
            $params = $params ? (array) $params : [];

            // 执行 SQL
            if (! $stmt->execute($params)) {
                throw new Com_DB_Exception(implode(',', $stmt->errorInfo()));
            }

            $sqlCostTime = microtime(true) - $sqlStartTime;

            // 调试模式打印SQL信息
            if (Com_DB::enableLogging()) {
                Com_DB::sqlLog($this->_formatLogSql($sql, $params), $sqlCostTime, $explain);
            }

            return $stmt;

        } catch (Exception $e) {
            Com_DB_Exception::process($e, '[SQL Failed]', $this->_formatLogSql($sql, $params));
            return false;
        }
    }

    /**
     * 返回 Explain SQL 信息
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    protected function _explain($sql, $params)
    {
        if ('select' != strtolower(substr($sql, 0, 6))) {
            return [];
        }

        $sql = Com_DB::getRealSql($sql, $params);

        $explain = [];
        $stmt = $this->_db->query("EXPLAIN " . $sql);
        if ($stmt instanceof PDOStatement) {
            $explain = $stmt->fetch(PDO::FETCH_ASSOC);
            $explain['sql'] = $sql;
            $stmt->closeCursor();
        }

        return $explain;
    }

    /**
     * 把带参数的 SQL 的转换为可记录的 Log
     *
     * @param string $sql
     * @param array $params
     * @return string
     */
    protected function _formatLogSql($sql, $params)
    {
        return array(
            'sql'     => $sql,
            'params'  => $params,
            'realSql' => Com_DB::getRealSql($sql, $params),
            'host'    => isset($this->_db->dsn['host']) ? $this->_db->dsn['host'] : '',
            'dbName'  => isset($this->_db->dsn['path']) ? $this->_db->dsn['path'] : '',
        );
    }

    /**
     * 执行一条 SQL （一般针对写操作，如 insert/replace/update/delete）
     *
     * @param string $sql
     * @param array $params
     * @param bool $forceMaster 是否强制连接主库
     * @return
     *         int insertId 插入
     *         int rowCount 替换、更新、删除
     *         false SQL 执行失败
     */
    public function query($sql, $params = [], $forceMaster = true)
    {
        $stmt = $this->_autoExecute($sql, $params, $forceMaster);
        if (! $stmt) {
            return false;
        }

        // INSERT 语句返回 insertId
        if (strtoupper(substr(trim($sql), 0, 6)) == 'INSERT') {
            return $this->lastInsertId();
        }

        return $stmt->rowCount();
    }

    /**
     * 获取所有记录
     *
     * @param string $sql
     * @param array $params
     * @param bool $forceMaster 是否强制连接主库
     * @return array
     */
    public function fetchAll($sql, $params = [], $forceMaster = false)
    {
        $stmt = $this->_autoExecute($sql, $params, $forceMaster);

        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        return [];
    }

    /**
     * 获取第一列
     *
     * @param string $sql
     * @param array $params
     * @param bool $forceMaster 是否强制连接主库
     * @return array
     */
    public function fetchCol($sql, $params = [], $forceMaster = false)
    {
        $stmt = $this->_autoExecute($sql, $params, $forceMaster);

        if ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
        }

        return [];
    }

    /**
     * 获取键值对
     *
     * @param string $sql
     * @param array $params
     * @param bool $forceMaster 是否强制连接主库
     * @return array
     */
    public function fetchPairs($sql, $params = [], $forceMaster = false)
    {
        $stmt = $this->_autoExecute($sql, $params, $forceMaster);

        if ($stmt) {
            $data = [];
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $data[$row[0]] = $row[1];
            }
            return $data;
        }

        return [];
    }

    /**
     * 获取关联数组
     *
     * @param string $sql
     * @param array $params
     * @param bool $forceMaster 是否强制连接主库
     * @return array
     */
    public function fetchAssoc($sql, $params = [], $forceMaster = false)
    {
        $stmt = $this->_autoExecute($sql, $params, $forceMaster);

        if ($stmt) {
            $data = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $key = current($row);
                $data[$key] = $row;
            }
            return $data;
        }

        return [];
    }

    /**
     * 获取一个单元格
     *
     * @param string $sql
     * @param array $params
     * @param bool $forceMaster 是否强制连接主库
     * @return array
     */
    public function fetchOne($sql, $params = [], $forceMaster = false)
    {
        $stmt = $this->_autoExecute($sql, $params, $forceMaster);

        if ($stmt) {
            return $stmt->fetchColumn() ?: null;
        }

        return null;
    }

    /**
     * 获取单条记录
     *
     * @param string $sql
     * @param array $params
     * @param bool $forceMaster 是否强制连接主库
     * @return array
     */
    public function fetchRow($sql, $params = [], $forceMaster = false)
    {
        $stmt = $this->_autoExecute($sql, $params, $forceMaster);

        if ($stmt) {
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        }

        return [];
    }

    /**
     * PDO fetch method
     *
     * @param PDO Statement $stmt
     * @return arary
     */
    public function fetchArray($stmt)
    {
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 执行 SQL 并返回 PDO Statement
     *
     * @param string $sql
     * @param array $params
     * @param bool $forceMaster
     * @return PDO Statement
     */
    public function execute($sql, $params = [], $forceMaster = true)
    {
        $stmt = $this->_autoExecute($sql, $params, $forceMaster);
        return $stmt ? $stmt : false;
    }

    /**
     * 获取自增ID
     *
     * @return lastInsertId
     */
    public function lastInsertId()
    {
        return $this->_db->lastInsertId();
    }

    /**
     * 事务开始
     */
    public function beginTransaction()
    {
        $this->_getChoiceDbConnect(true);
        $this->_db->beginTransaction();
    }

    /**
     * 事务提交
     */
    public function commit()
    {
        $this->_getChoiceDbConnect(true);
        $this->_db->commit();
    }

    /**
     * 事务回滚
     */
    public function rollBack()
    {
        $this->_getChoiceDbConnect(true);
        $this->_db->rollBack();
    }

    /**
     * 检查一个表是否存在
     *
     * @param string $tblName
     * @return bool
     */
    public function isTableExist($tblName)
    {
        return (bool) $this->fetchRow("SHOW TABLES LIKE '{$tblName}'");
    }
}