<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2017/12/22
 * Time: 16:53
 */

namespace app\api\controller;


class Shangchang extends Base
{
    /**
     * 获取所有商场数据
     */
    public function index()
    {
        $condition = [
            'status' => 1
        ];
        $cid = input('cid');
        if ($cid){
            $condition['cid'] = $cid;
        }
        $keyword = trim(input('keyword'));
        if ($keyword){
            $condition['name'] = array('LIKE', '%'.$keyword.'%');
        }
        $page = input('page');
        if (empty($page)){
            $page = 1;
        }
        $limit = $page*6-6;

        $stroeList = db('shangchang')->where($condition)->order('sort desc,type desc')->field('id,name,uname,logo,tel,sheng,city,quyu')->limit($limit,6)->select();
        foreach ($stroeList as $k => $v){
            $stroeList[$k]['sheng'] = db('china_city')->where('id='.$v['sheng'])->value('name');
            $stroeList[$k]['city'] = db('china_city')->where('id='.$v['city'])->value('name');
            $stroeList[$k]['quyu'] = db('china_city')->where('id='.$v['quyu'])->value('name');
            $stroeList[$k]['logo'] = __DATAURL__.$v['logo'];
            $proList = db('product')->where('del=0 AND pro_type=1 AND is_down=0 AND shop_id='.$v['id'])->field('id,photo_x,price_yh')->limit(4)->select();
            foreach ($proList as $k => $v){
                $proList[$k]['photo_x'] = __DATAURL__.$v['photo_x'];
            }
            $stroeList[$k]['pro_list'] = $proList;
        }
        return json(array('status'=>1,'store_list'=>$stroeList));
    }

    /**
     * 获取商铺详情接口
     */
    public function shopDetails()
    {
        $shopId = input('shop_id');
        $shopInfo = db('shangchang')->where('id='.$shopId)->field('id,name,uname,tel,logo,address,content')->find();
        if (empty($shopInfo)){
            return json(array('status'=>0,'err'=>'没有找到商铺信息.'));
        }
        $shopInfo['logo'] = __DATAURL__.$shopInfo['logo'];
        $shopInfo['content'] = html_entity_decode($shopInfo['content'], ENT_QUOTES, 'utf-8');

        $proList = db('product')->where('shop_id='.$shopId.' AND del=0 AND is_down=0')->order('addtime desc, sort desc')->field('id,name,intro,price,price_yh,photo_x,shiyong')->limit(8)->select();
        foreach ($proList as $k => $v){
            $proList[$k]['photo_x'] = __DATAURL__.$v['photo_x'];
        }

        return json(array('status'=>1,'shop_info'=>$shopInfo,'pro'=>$proList));
    }
    /**
     * 会员店铺收藏
     */
    public function shopCollect()
    {
        $uid = input('uid');
        $shopId = input('shop_id');
        if (empty($uid) || empty($shopId)){
            return json(array('status'=>0,'err'=>'系统错误，请稍后再试.'));
        }
        $check = db('shangchang_sc')->where('uid='.$uid. ' AND shop_id='.$shopId)->value('id');
        if ($check){
            return json(array('status'=>1,'succ'=>'您已收藏该店铺.'));
        }
        $data = [
            'uid' => $uid,
            'shop_id' => $shopId
        ];
        $result = db('shangchang_sc')->insert($data);
        if ($result){
            return json(array('status'=>1,'succ'=>'收藏成功！'));
        }else{
            return json(array('status'=>0,'err'=>'网络错误..'));
        }
    }
}