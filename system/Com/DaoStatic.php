<?php

/**
 * 用于静态资源表的DAO子类
 * 扩展了缓存列表功能
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Dao.php 11240 2014-05-29 12:23:07Z jiangjian $
 */

class Com_DaoStatic extends Com_Dao
{
    protected function _init()
    {
        $this->_cache = F('Memcache')->static;
    }

    /**
     * 魔术缓存拦截读取
     *
     * @param string $method
     * @param mixed $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        // 缓存指定函数的结果
        if (method_exists($this, '__CACHE__' . $method)) {

            $cacheKey = md5($this->_dbName . ':' . $this->_tableName . ':' . $method . ($args ? ':' . serialize($args) : ''));

            $data = $this->_cache->get($cacheKey);

            // 即使取不到数据，也需要缓存住
            if ($data === false) {
                $data = call_user_func_array(array($this, '__CACHE__' . $method), $args);
                $this->_cache->set($cacheKey, $data, $this->_cacheTTL);
            }

            return $data;
        }

        // 清除指定函数的结果缓存
        elseif (strpos($method, '__DEL_CACHE__') !== false) {

            // 原函数名
            $method = str_replace('__DEL_CACHE__', '', $method);

            $cacheKey = md5($this->_dbName . ':' . $this->_tableName . ':' . $method . ($args ? ':' . serialize($args) : ''));

            return $this->_cache->delete($cacheKey);
        }

        return parent::__call($method, $args);
    }

    // 无条件返回全表行数
    protected function __CACHE__getCount($where = null)
    {
        return $this->where($where)->fetchCount();
    }

    // 无条件返回全表数据
    protected function __CACHE__getAll()
    {
        return $this->fetchAll();
    }

    // 无条件返回全表关联数据
    protected function __CACHE__getAssoc()
    {
        return $this->fetchAssoc();
    }

    // 无条件返回全表主键集合
    protected function __CACHE__getPks()
    {
        return $this->fetchPks();
    }

    // 无条件返回全表名称集合
    protected function __CACHE__getNames()
    {
        return $this->fetchNames();
    }

    // 无条件返回全表键值对
    protected function __CACHE__getPairs($fields = null, $where = null)
    {
        $fields = $fields ?: $this->_pk . ', ' . $this->_nameField;

        return $this->field($fields)->where($where)->fetchPairs();
    }

    protected function __CACHE__getPkByName($name)
    {
        return $this->field($this->_pk)->where(array($this->_nameField => $name))->fetchOne();
    }
}