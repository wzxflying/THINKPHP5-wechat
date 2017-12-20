<?php
/**
 * Created by PhpStorm.
 * User: wzx
 * Date: 2017/12/18
 * Time: 20:38
 */

namespace app\api\controller;


class Search extends Base
{
    /**
     * 获取会员 搜索记录接口
     */
    public function index()
    {
        $uid = input('uid');
        //获取热门搜索内容
        $remen = db('search_record')->group('keyword')->field('SUM(num)')->limit(10)->select();
        //获取历史搜索记录
        $history = array();
        if ($uid){
            $history = db('search_record')->where('uid='.$uid)->order('addtime desc')->field('keyword')->limit(20)->select();
        }
        return json(array('remen'=>$remen,'history'=>$history));
    }

    /**
     * 产品商家搜索接口
     */
    public function searches()
    {
        $uid = input('uid');
        $keyword = trim(input('keyword'));
        if (empty($keyword)){
            return json(array('status'=>0,'err'=>'请输入搜索内容.'));
        }

        if (!empty($uid)){
            $check = db('search_record')->where('uid='.$uid.' AND keyword="'.$keyword.'"')->find();
            if ($check){
                $num = $check['num']+1;
                db('search_record')->where('id='.$check['id'])->update(array('num' => $num));
            }else{
                $add = [
                    'uid' => $uid,
                    'keyword' => $keyword,
                    'addtime'=> time()
                ];
                db('search_record')->insert($add);
            }
        }

        $page = input('page');
        if (empty($page)){
            $page = 0; //没实现
        }

        $proList = db('product')->where('del=0 AND pro_type=1 AND is_down=0 AND name LIKE "%'.$keyword.'%"')->order('addtime desc')->field('id,name,photo_x,shiyong,price,price_yh')->select();
        foreach ($proList as $k => $v){
            $proList[$k]['photo_x'] = __DATAURL__.$v['photo_x'];
        }

        $page2 = input('page2');
        if (empty($page2)){
            $page2 = 0;
        }

        $condition = [
            'status' => 1,
            //根据店面查询
            'name' => array('LIKE', '%'.$keyword.'%')
        ];
        //获取全部商家数据
        $storeList = db('shangchang')->where($condition)->order('sort desc,type desc')->field('id,name,uname,logo,tel,sheng,city,quyu')->limit($page2.',6')->select();
        foreach ($storeList as $k => $v){
            $storeList[$k]['sheng'] = db('china_city')->where('id='.$v['sheng'])->value('name');
            $storeList[$k]['city'] = db('china_city')->where('id='.$v['city'])->value('name');
            $storeList[$k]['quyu'] = db('china_city')->where('id='.$v['quyu'])->value('name');
            $storeList[$k]['logo'] = __DATAURL__.$v['logo'];
            $proList2 = db('product')->where('del=0 AND is_down=0 AND shop_id='.$v['id'])->field('id,photo_x,price_yh')->limit(4)->select();
            foreach ($proList2 as $k => $v){
                $proList2[$k]['photo_x'] = __DATAURL__.$v['photo_x'];
            }
            $storeList[$k]['pro_list'] = $proList2;
        }
        return json(array('status'=>1,'pro'=>$proList,'shop'=>$storeList));
    }
}