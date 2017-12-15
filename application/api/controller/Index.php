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
        $ggtop = db('guanggao')->order('sort desc,id asc')->field('id,name,photo')->limit(10)->select();
        foreach($ggtop as $k => $v){
            $ggtop[$k]['photo'] = __DATAURL__.$v['photo'];
        }
        /***********获取首页顶部轮播图 end************/

        //======================
        //首页推荐品牌 20个
        //======================
        $brand = db('brand')->field('id,name,photo')->limit(20)->select();
        foreach ($brand as $k => $v){
            $brand[$k]['photo'] = __DATAURL__.$v['photo'];
        }
        //======================
        //首页培训课程
        //======================
        $course = db('course')->where('del=0')->order('id desc')->field('id,title,intro,photo')->select();
        foreach ($course as $k => $v) {
            $course[$k]['photo'] = __DATAURL__.$v['photo'];
        }
        //======================
        //首页推荐产品
        //======================
        $pro_list = db('product')->where('del=0 AND pro_type=1 AND is_down=0 AND type=1')->order('sort desc,id desc')->field('id,name,intro,photo_x,price_yh,price,shiyong')->limit(8)->select();
        foreach ($pro_list as $k => $v) {
            $pro_list[$k]['photo_x'] = __DATAURL__.$v['photo_x'];
        }
        //======================
        //首页分类 自己组建数组
        //======================
        $indeximg = db('indeximg')->order('sort asc')->field('name,photo')->select();
        foreach ($indeximg as $k => $v){
            $indeximg[$k]['photo'] = __DATAURL__.$v['photo'];
        }

        return json(array('ggtop'=>$ggtop,'indeximg'=>$indeximg,'prolist'=>$pro_list,'brand'=>$brand,'course'=>$course));
    }

    //***************************
    //  首页产品 分页
    //***************************
    public function getList()
    {
        $page = input('post.page');
        $limit = intval($page*8)-8;
        $pro_list = db('product')->where('del=0 AND pro_type=1 AND is_down=0 AND type=1')->order('sort desc,id desc')->field('id,name,photo_x,price_yh,shiyong')->limit($limit.',8')->select();
        foreach ($pro_list as $k => $v) {
            $pro_list[$k]['photo_x'] = __DATAURL__.$v['photo_x'];
        }
        return json(array('prolist' => $pro_list));
    }
}