<?php

class Dao_Ucenter_UserCodeIndex extends Dao_Ucenter_Abstract
{
    protected $_tableName = 'user_code_index';
    protected $_pk        = 'user_code';

    public function getIndexByUid($uid, $gameServerId)
    {
        $where = array(
            'uid' => $uid,
            'game_server_id' => $gameServerId,
        );

        return $this->where($where)->fetchRow();
    }
}