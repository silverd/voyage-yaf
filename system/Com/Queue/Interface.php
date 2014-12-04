<?php

/**
 * 队列接口、方法定义
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Interface.php 2 2012-11-14 07:10:07Z jiangjian $
 */

interface Com_Queue_Interface
{
    public function push($value);

    public function pop();

    public function count();

    public function clear();

    public function view();
}