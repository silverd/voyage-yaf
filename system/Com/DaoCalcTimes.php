<?php

/**
 * Data Access Object
 * 增强功能：统计各DAO的读写次数
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: DaoCalcTimes.php 7070 2013-11-26 02:15:42Z jiangjian $
 */

class Com_DaoCalcTimes extends Com_Dao
{
    // 本DAO是否统计Memcache读写次数
    protected $_isCalcMemcacheWRTimes = true;

    /**
     * 根据主键 fetchRow
     *
     * @param mixed $pk
     * @return array
     */
    public function get($pk)
    {
        $row = parent::get($pk);

        // 统计Memcache读写次数
        if ($this->_isCalcMemcacheWRTimes) {
            Dao('Massive_MemcacheRecord')->mark($this->_dbName, $this->_tableName, __FUNCTION__, 0);
        }

        return $row;
    }

    public function incrByPk($pk, $field, $step = 1)
    {
        if (! $result = parent::incrByPk($pk, $field, $step)) {
            return $result;
        }

        // 统计Memcache读写次数
        if ($this->_isCalcMemcacheWRTimes) {
            Dao('Massive_MemcacheRecord')->mark($this->_dbName, $this->_tableName, __FUNCTION__, 1);
        }

        return $result;
    }

    public function replaceByPk(array $setArr, $pk)
    {
        if (! $result = parent::replaceByPk($setArr, $pk)) {
            return $result;
        }

        // 统计Memcache读写次数
        if ($this->_isCalcMemcacheWRTimes) {
            Dao('Massive_MemcacheRecord')->mark($this->_dbName, $this->_tableName, __FUNCTION__, 1);
        }

        return $result;
    }

    /**
     * 更新（根据主键）
     *
     * @param array $setArr
     * @param mixed $pk
     * @param array $extraWhere 格外的WHERE条件
     * @return bool/int
     */
    public function updateByPk(array $setArr, $pk, array $extraWhere = array())
    {
        if (! $result = parent::updateByPk($setArr, $pk, $extraWhere)) {
            return $result;
        }

        // 统计Memcache读写次数
        if ($this->_isCalcMemcacheWRTimes) {
            Dao('Massive_MemcacheRecord')->mark($this->_dbName, $this->_tableName, __FUNCTION__, 1);
        }

        return $result;
    }

    /**
     * 批量更新（根据主键）
     * 注：本方法不支持复合主键
     *
     * @param array $setArr
     * @param array $pks
     * @param array $extraWhere 格外的WHERE条件
     * @return bool/int
     */
    public function updateByPks(array $setArr, array $pks, array $extraWhere = array())
    {
        if (! $result = parent::updateByPks($setArr, $pks, $extraWhere)) {
            return $result;
        }

        // 统计Memcache读写次数
        if ($this->_isCalcMemcacheWRTimes) {
            Dao('Massive_MemcacheRecord')->mark($this->_dbName, $this->_tableName, __FUNCTION__, 1);
        }

        return $result;
    }

    /**
     * 删除（根据主键）
     *
     * @param mixed $pk
     * @param array $extraWhere 格外的WHERE条件
     * @return bool
     */
    public function deleteByPk($pk, array $extraWhere = array())
    {
        if (! $result = parent::deleteByPk($pk, $extraWhere)) {
            return $result;
        }

        // 统计Memcache读写次数
        if ($this->_isCalcMemcacheWRTimes) {
            Dao('Massive_MemcacheRecord')->mark($this->_dbName, $this->_tableName, __FUNCTION__, 1);
        }

        return $result;
    }

    /**
     * 批量删除（根据主键）
     * 注：本方法不支持复合主键
     *
     * @param array $pks
     * @param array $extraWhere 格外的WHERE条件
     * @return bool
     */
    public function deleteByPks(array $pks, array $extraWhere = array())
    {
        if (! $result = parent::deleteByPks($pks, $extraWhere)) {
            return $result;
        }

        // 统计Memcache读写次数
        if ($this->_isCalcMemcacheWRTimes) {
            Dao('Massive_MemcacheRecord')->mark($this->_dbName, $this->_tableName, __FUNCTION__, 1);
        }

        return $result;
    }
}