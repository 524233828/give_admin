<?php
return array(
    //数据库配置
    'DB_TYPE' => 'mysql',
    'DB_PORT' => '3306',
    'DB_NAME' => 'dobee',
    'DB_HOST' => '127.0.0.1',
    'DB_USER' => 'root',
    'DB_PWD' => 'root',
    'DB_PREFIX' => 'db_',
    //url路由配置
    'URL_ROUTER_ON'			=> true,

    'URL_MODEL'       => 3,
    'URL_HTML_SUFFIX' => 'html',

    'DEFAULT_MODULE'        =>'Admin',  // 默认模块
    'DEFAULT_CONTROLLER'    =>'Front', // 默认控制器名称
    'DEFAULT_ACTION'        =>'login', // 默认操作名
    'MD5_KEY'               =>'qwtrytvxbuiytdn345', // md5加密秘钥

);
