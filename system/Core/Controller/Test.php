<?php

/**
 * 测试模块抽象父类
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Error.php 9716 2015-05-31 12:12:41Z jiangjian $
 */

abstract class Core_Controller_Test extends Core_Controller_Web
{
    protected $_url;

    public function init()
    {
        parent::init();

        $this->setViewPath(APP_PATH . 'modules/Test/views');
    }

    // 函数列表
    public function indexAction()
    {
        $funcs = get_class_methods($this);
        foreach ($funcs as $func) {
            if (substr($func, 0, 1) != '_' && substr($func, -6, 6) == 'Action' && ! in_array($func, array('indexAction'))) {
                $func = str_replace('Action', '', $func);
                echo '<a href="' . $this->_url . $func .'" target="_blank">' . $func . '</a><br />';
            }
        }

        exit();
    }

    protected function _dump($var)
    {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
    }
}