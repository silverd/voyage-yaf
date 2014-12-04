<?php

/**
 * Api 服务端控制器
 * 提供给GM管理后台的
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Mgmts.php 11936 2014-07-28 09:10:13Z zhengjiang $
 */

class Controller_Mgmts extends Core_Controller_Api
{
    // 签名私钥
    protected $_secretKey = 'XXXXX';

    // 给指定玩家发放物品
    public function sendItemAction()
    {
        $uid    = $this->getInt('uid');
        $itemId = $this->getInt('item_id');
        $amount = $this->getInt('amount');

        if ($itemId < 1 || $amount < 1) {
            throws403('Invalid ItemId/Amount');
        }

        // do something
        
        $this->output('OK');
    }
}