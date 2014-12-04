<?php

class Dao_Share_UserIndex extends Dao_Share_Abstract
{
    protected $_tableName = 'user_index';
    protected $_pk        = 'uid';
    protected $_nameField = 'user_name';

    public function getUidByCode($userCode)
    {
        return $this->_getPkByField('user_code', $userCode);
    }

    public function getUidByName($userName)
    {
        return $this->_getPkByField('user_name', $userName);
    }

    public function getUserByCode($userCode)
    {
        if (! $uid = $this->getUidByCode($userCode)) {
            return array();
        }

        return $this->get($uid);
    }

    public function getUserByName($userName)
    {
        if (! $uid = $this->getUidByName($userName)) {
            return array();
        }

        return $this->get($uid);
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
        $orgRow = $this->get($pk);

        if (! $result = parent::updateByPk($setArr, $pk, $extraWhere)) {
            return $result;
        }

        // 清理缓存
        if ($this->_isCached) {
            foreach (array('user_code', 'user_name') as $fieldName) {
                isset($setArr[$fieldName]) && $this->_deletePkByFieldCache($fieldName, $orgRow[$fieldName]);
            }
        }

        return $result;
    }
}