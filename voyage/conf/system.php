<?php

/**
 * 系统常量定义
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: system.php 11708 2014-07-10 10:37:34Z jiangjian $
 */

// 网站版本号
define('WEB_VERSION', '20130124');

// 模板文件扩展名
define('TPL_EXT', '.phtml');

// 支持的语言版本（半角逗号分隔）
define('SUPPORT_LANGS', 'zh_CN,zh_TW');

// 缺省的语言版本（静态库数值读取等）
define('DEFAULT_LANG', 'zh_CN');

// 语言包 po/mo 文件名称
define('CUR_TEXT_DOMAIN', 'voyage');

// 当前时区
define('CUR_TIMEZONE', 'Asia/Shanghai');

// 静态CDN地址
define('CDN_PATH', '/');

// CSS存放目录（全局）
define('CSS_DIR', CDN_PATH . 'css');

// IMG存放目录（全局）
define('IMG_DIR', CDN_PATH . 'img');

// JS存放目录（全局）
define('JS_DIR',  CDN_PATH . 'js');

// 是否调试模式
define('DEBUG_MODE', true);

// 调试模式下，是否 Explain SQL
define('DEBUG_EXPLAIN_SQL', false);

// 新用户存储在哪几个库（最多两个，用半角逗号分隔）
define('DB_SUFFIX_NEW_USER', '1,2');

// 总共有几个用户分库
define('DIST_USER_DB_NUM', 2);

// 今天日期
define('TODAY', date('Y-m-d'));

// 静态文件是否需要按版本部署
define('STATIC_DEPLOY', false);

// 注册是否需要邀请码
define('INVITE_CODE_NEED', false);

// 是否处于内测阶段
define('IS_IN_BETA', false);

// 第三方平台渠道注册人数上限-机锋
define('MAX_USER_LIMIT_GFAN', 600);

// 航海元年（首次开服日期）
define('VOYAGE_1ST_YEAR', '2013/9/25');

// 是否显示网页右侧的导航菜单
define('SHOW_RIGHT_MENU', true);

// 是否开启 XHProf 性能测试
define('XHPROF_DEGUG', true);

// 当前游戏服务器ID
define('CUR_GAME_SERVER_ID', 1);

// Ucenter的访问域名（前面不加http，后面不加斜杠）
define('UCENTER_WEB_HOST', isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '127.0.0.1');