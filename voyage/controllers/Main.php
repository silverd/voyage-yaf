<?php

/**
 * 主界面
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Main.php 11509 2014-06-23 08:31:10Z jiangjian $
 */

class Controller_Main extends Controller_Abstract
{
    /**
     * 主界面
     */
    public function indexAction()
    {
        // 直接渲染模板
    }

    public function echoAction()
    {
        echo 'Hello Voyage-YAF!';
        exit;
    }

    public function jsonAction()
    {
        $this->json(array(
            'status' => 200,
            'data'   => array(
                'hello' => 'world',
            ),
        ));
    }
}