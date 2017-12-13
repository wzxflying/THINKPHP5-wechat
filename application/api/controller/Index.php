<?php
/**
 * Created by PhpStorm.
 * User: wzx
 * Date: 2017/12/13
 * Time: 19:24
 */
namespace app\api\controller;


class Index extends Base
{
    public function index()
    {
        //如果缓存首页没有数据，那么就读取数据库
        //没实现
        /***********获取首页顶部轮播图************/
        $ggtop = model('guanggao')->order('sort desc,id asc')->field('id,name,photo')->limit(10)->select();
        dump($ggtop);
    }
}