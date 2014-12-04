<?php

class Dao_Ucenter_ThirdPlatformUser extends Dao_Ucenter_Abstract
{
    protected $_tableName = 'third_platform_user';
    protected $_pk        = array('source', 'third_uid');
}