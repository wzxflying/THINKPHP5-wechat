<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2017/12/25
 * Time: 10:51
 */

namespace app\api\controller;


class Web extends Base
{
    /**
     * 所有单页数据接口
     */
    public function web()
    {
        $web_id = input('web_id');
        $content = db('web')->where('id='.$web_id)->value('concent');
        $content = str_replace('/minipetmrschool/Data/', __DATAURL__, $content);
        $content = html_entity_decode($content, ENT_QUOTES, 'utf-8');
        return urldecode(json(array('status'=>1,'content'=>$content)));
    }

    /**
     * @return \think\response\Json
     * 获取中心认证图片接口
     */
    public function vipChar()
    {
        $pic = db('admin_app')->value('photo');
        $pic = 'http://'.$_SERVER['SERVER_NAME'].__DATAURL__.'/'.$pic;
        return json(array('pic'=>$pic));
    }

    /**
     * @return \think\response\Json
     * 产品商家搜索接口
     */
    public function searches()
    {
        $keyword = trim(input('keyword'));
        if (empty($keyword)){
            return json(array('status'=>0,'err'=>'请输入搜索内容.'));
        }

        $page = input('page', 0);

        $proList = db('product')->where('del=0 AND is_down=0 AND name LIKE "%'.$keyword.'%"')->order('addtime desc')->field('id,name,photo_x,shiyong,renqi,price,price_yh,company')->limit($page,15)->select();
        foreach ($proList as $k => $v){
            $proList[$k]['photo_x'] = __DATAURL__.$v['photo_x'];
        }

        $page2 = input('page2', 0);

        $condition = [
            'status' => 1,
            'name' => array('LIKE', '%'.$keyword.'%')
        ];
        $storeList = db('shangchang')->where($condition)->order('sort desc,type desc')->field('id,name,uname,logo,tel,sheng,city,quyu')->limit($page2,6)->select();
        foreach ($storeList as $k => $v){
            $storeList[$k]['sheng'] = db('china_city')->where('id='.intval($v['sheng']))->value('name');
            $storeList[$k]['city'] = db('china_city')->where('id='.intval($v['city']))->value('name');
            $storeList[$k]['quyu'] = db('china_city')->where('id='.intval($v['quyu']))->value('name');
            $storeList[$k]['logo'] = __DATAURL__.$v['logo'];
            $proList = db('product')->where('del=0 AND is_down=0 AND shop_id='.intval($v['id']))->field('id,photo_x,price_yh')->limit(4)->select();
            foreach ($proList as $key => $val){
                $proList[$key]['photo_x'] = __DATAURL__.$val['photo_x'];
            }
            $storeList[$k]['pro_list'] = $proList;
        }
        return json(array('status'=>1,'pro'=>$proList,'shop'=>$storeList));
    }
}