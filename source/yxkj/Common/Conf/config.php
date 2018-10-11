<?php
/**
 * config.php
 * 项目公共配置文件
 * @author baddl
 * @date   2017-01-07 14:39
 */
defined ( 'WEB_URL' ) or define ( 'WEB_URL' , 'http://cloud.youxspace.com');
$con_arr = array(
    'URL_CASE_INSENSITIVE'  =>  false,               //防止部署模式下控制器及方法转小写问题
    'DB_TYPE'               =>  'mysql',     		// 数据库类型
    'DB_HOST'               =>  '47.93.16.203',
    //'DB_HOST'               =>  'rm-2ze6g66l7pzx2kvej.mysql.rds.aliyuncs.com',
    'DB_NAME'               =>  'yxkj',          	// 数据库名
    'DB_USER'               =>  'yxkj',      		// 用户名
    //'DB_PWD'                =>  'yxKJ2017$0904',
    'DB_PWD'                =>  'yxkj20170904',
    'DB_PORT'               =>  '',        			// 端口
    'DB_PREFIX'             =>  'yx_',    			// 数据库表前缀

    'MODULE_ALLOW_LIST'    => array('Pc','Wx','Traveller'),
    'DEFAULT_MODULE'       => 'Pc',
    'URLL_SUFFIX'          => '.html',
    'URL_MODEL'            => 2,                   //地址重写
    'PAGE_SIZE'            => 20,
    'WPAGE_SIZE'           => 10,

    'TMPL_PARSE_STRING' => array (
        '__CSS__'   => '/Public/Css',
        '__JS__'    => '/Public/Js',
        '__IMG__'   => '/Public/Images',
        '__WDP__'   => '/Public/WdatePicker',
        '__UPLOADIFY__' =>'/Public/uploadify',
        '__CKEDITOR__'  => '/Public/ckeditor',
        '__DTREE__'     => '/Public/dtree',
        '__TREETABLE__' => '/Public/treegtable',
        '__PUBLIC__'    => '/Public',
        '__BOOTSTRAP__'    => '/Public/Bootstrap',
        '__RESOURCES__' => '/Public/resources',
        '__FILES__'      => '/Public/files',
        '__DATA__'      => '/Public/data',
    ),

    'RUIZHIHUDONG'          => '北京锐智互动网络科技有限公司',
    'WANGZHAN'              => 'www.irzhd.com',
    'PROJECT'               => '优享空间',
    'TEL'                   => '152 0130 1399',
);

$sett_arr = include 'setting.config.php';

return array_merge($sett_arr,$con_arr);
