<?php

abstract class Dao_Ucenter_Abstract extends Dao_Abstract
{
    protected $_dbName = 'voyage_ucenter';

    protected function _init()
    {
        $this->_cache = F('Memcache')->ucenter;
    }
}