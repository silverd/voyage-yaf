<?php

/**
 * Web 访问入口
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: index.php 10615 2014-04-21 12:09:13Z zhengjiang $
 */

// 定义路径常量
define('APP_PATH', dirname(__DIR__) . '/');
define('SYS_PATH', dirname(APP_PATH) . '/system/');

// 调试模式密钥
define('DEBUG_XKEY', 'voyage@dianshitech');

$app = new Yaf_Application(APP_PATH . 'conf/app.ini');
$app->bootstrap()->run();