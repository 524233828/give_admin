<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用入口文件
@session_start();
// 指定允许其他域名访问
header('Access-Control-Allow-Origin:*');
// 响应类型
header('Access-Control-Allow-Credentials', true);

header('Access-Control-Allow-Methods:*');
// 响应头设置
header('Access-Control-Allow-Headers:x-requested-with,content-type');

// 检测PHP环境
if(version_compare(PHP_VERSION,'5.6.0','<'))  die('require PHP > 5.6.0 !');

// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG', true);

//数据库开发环境
define('APP_STATUS', 'prod');
// 定义应用目录
define('APP_PATH', './Application/');
// 上传文件保存目录
define('UPLOAD_PATH', './upload/');
// 数据目录
define('DATA_PATH', './Data/');
define('PUBLIC_PATH', './Public/');
define('_PHP_FILE_', 'index.php');
include 'vendor/autoload.php';

// 引入ThinkPHP入口文件
require './ThinkPHP/ThinkPHP.php';

// 亲^_^ 后面不需要任何代码了 就是如此简单
