<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2017/12/15
 * Time: 13:56
 */

namespace app\api\controller;


class News extends Base
{
    //*****************************
    //  新闻列表
    //*****************************
    public function index()
    {
        $keyword = input('post.keyword');
        if (!empty($keyword)){
            $where = 'name LIKE "%'. $keyword .'%"';
        }

        $list = db('news')->where($where)->field('id,cid,digest,name,photo,addtime,source')->order('sort desc,addtime desc')->limit(8)->select();
        foreach ($list as $k=>$v){
            $list[$k]['photo'] = __DATAURL__.$v['photo'];
            $list[$k]['cname'] = db('news_cat')->where('id='.intval($v['cid']))->find('name');
            $list[$k]['addtime'] = date('Y-m-d', $v['addtime']);
        }
        return json(array('list'=>$list));

    }

    //*****************************
    //  新闻列表  加载更多
    //*****************************
    public function getList()
    {
        $page = input('post.page','2');
        $limit = $page*8-8;

        $list = db('news')->field('id,cid,digest,name,photo,addtime,source')->order('sort desc,addtime desc')->limit($limit.',8')->select();
        foreach ($list as $k=>$v){
            $list[$k]['photo'] = __DATAURL__.$v['photo'];
            $list[$k]['cname'] = db('news_cat')->where('id='.$v['cid'])->find('name');
            $list[$k]['addtime'] = date('Y-m-d', $v['addtime']);
        }
        return json(array('list'=>$list));

    }

    //*****************************
    //  新闻详情
    //*****************************
    public function detail()
    {
        $newid = input('post.news_id');
        $detail = db('news')->where('id='.$newid)->find();
        if (empty($detail)){
            return json(array('status'=>0,'err'=>'没有找到相关信息.'));
        }

        db('news')->where('id='.$newid)->setInc('click');

        $detail['addtime'] = date('Y-m-d', $detail['addtime']);

        return json(array('status'=>1,'info'=>$detail));
    }
}