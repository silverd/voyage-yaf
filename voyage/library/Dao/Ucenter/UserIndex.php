<?php

class Dao_Ucenter_UserIndex extends Dao_Ucenter_Abstract
{
    protected $_tableName = 'user_index';
    protected $_nameField = 'email';

    public function getUidByEmail($email)
    {
        return $this->_getPkByField('email', $email);
    }

    public function getUidByToken($userToken)
    {
        return $this->_getPkByField('user_token', $userToken);
    }

    public function getUserByEmail($email)
    {
        if (! $uid = $this->getUidByEmail($email)) {
            return array();
        }

        return $this->get($uid);
    }

    public function getUserByToken($userToken)
    {
        if (! $uid = $this->getUidByToken($userToken)) {
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
            foreach (array('email', 'user_token') as $fieldName) {
                isset($setArr[$fieldName]) && $this->_deletePkByFieldCache($fieldName, $orgRow[$fieldName]);
            }
        }

        return $result;
    }
}