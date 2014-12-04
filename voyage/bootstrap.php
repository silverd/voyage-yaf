<?php

// 框架初始化
Core_Bootstrap::init();

class Bootstrap extends Yaf_Bootstrap_Abstract
{
    public function _initView(Yaf_Dispatcher $dispatcher)
    {
        $dispatcher->setView(Core_View::getInstance());
    }
}