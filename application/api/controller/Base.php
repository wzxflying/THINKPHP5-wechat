<?php
/**
 * Created by PhpStorm.
 * User: wzx
 * Date: 2017/12/13
 * Time: 19:28
 */

namespace app\api\controller;


use think\Controller;

class Base extends Controller
{
    public function _initialize()
    {
        //判断HTTP还是HTTPS
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'ON') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        //所有图片路径
//        define(__DATAURL__, $http_type.$_SERVER['SERVER_NAME'].__DATA__.'/');
//        define(__PUBLICURL__, $http_type.$_SERVER['SERVER_NAME'].__PUBLIC__.'/');
//        define(__HTTP__, $http_type);
    }
}