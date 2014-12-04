<?php

class Dao_Ucenter_ThirdPlatformAccessToken extends Dao_Ucenter_Abstract
{
    protected $_tableName = 'third_platform_access_token';
    protected $_pk        = array('source', 'access_token');
}