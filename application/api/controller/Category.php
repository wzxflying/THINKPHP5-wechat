<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2017/12/14
 * Time: 13:45
 */

namespace app\api\controller;

class Category extends Base
{
    public function index()
    {
        $list = db('category')->where('tid=1')->field('id,tid,name')->select();
        $categoryList = db('category')->where('tid='.intval($list[0]['id']))->field('id,name,img')->select();
        foreach ($categoryList as $k =>$v){
            $categoryList[$k]['img'] = __DATAURL__.$v['img'];
        }
        return json(array('status' => 1, 'list' => $list, 'categoryList' => $categoryList));
    }

    //***************************
    // 产品分类
    //***************************
    public function getCategory()
    {
        $cid = input('post.cid');
        if(empty($cid)){
            return json(array('status' => 0, 'err' => '没有找到产品数据。'));
        }

        $categoryList = db('category')-> where('tid='.$cid)->field('id,name,img')->select();
        foreach ($categoryList as $k => $v){
            $categoryList[$k]['img'] = __DATAURL__.$v['img'];
        }
        return json(array('status' => 1, 'categoryList' => $categoryList));
    }
}