<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
//定义data文件目录
define('__DATA__', '/Data/');
//定义public资源目录
define('__PUBLIC__', '/static/');
//判断HTTP还是HTTPS
$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'ON') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
//所有图片路径
define('__DATAURL__', $http_type.$_SERVER['SERVER_NAME'].__DATA__);
define('__PUBLICURL__', $http_type.$_SERVER['SERVER_NAME'].__PUBLIC__);
define('__HTTP__', $http_type.$_SERVER['SERVER_NAME']);
// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
