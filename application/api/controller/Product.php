<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2017/12/19
 * Time: 10:55
 */

namespace app\api\controller;


class Product extends Base
{
    /**
     * 获取商品详情信息接口
     */
    public function index()
    {
        $product = db('product');
        $proId = input('pro_id');
        if (empty($proId)){
            return json(array('status'=>0,'err'=>'商品不存在或已下架！'));
        }

        $pro = $product->where('id='.$proId.' AND del=0 AND is_down=0')->find();
        if (empty($pro)){
            return json(array('status'=>0,'err'=>'商品不存在或已下架！'.__LINE__));
        }

        $pro['photo_x'] = __DATAURL__.$pro['photo_x'];
        $pro['photo_d'] = __DATAURL__.$pro['photo_d'];
        $pro['brand'] = db('brand')->where('id='.$pro['brand_id'])->value('name');
        $pro['cat_name'] = db('category')->where('id='.$pro['cid'])->value('name');

        //图片轮播数组
        $img = explode(',', trim($pro['photo_string'], ','));
        $b = array();
        if ($pro['photo_string']){
            foreach ($img as $k => $v){
                $b[] = __DATAURL__.$v;
            }
        }else{
            $b[] = $pro['photo_d'];
        }
        $pro['img_arr'] = $b;

        //处理产品属性
        $catList = array();
        $commodityAttr = array();//产品库还剩下的产品规格
        $attrValueList = array();//产品所有的产品规格
        if (!empty($pro['pro_buff'])){
            $pro_buff = explode(',', $pro['pro_buff']);
            foreach ($pro_buff as $k =>$v){
                $attrName = db('attribute')->where('id='.$v)->value('attr_name');
                $guigeList = db('guige')->where('attr_id='.$v.' AND pid='.$pro['id'])->field('id,name')->select();
                $ggss = array();
                $gg = array();
                foreach ($guigeList as $k => $v) {
                    $gg[$k]['attrKey'] = $attrName;
                    $gg[$k]['attrValue'] = $v['name'];
                    $ggss[] = $v['name'];
                }
                $commodityAttr[$k]['attrValueList'] = $gg;
                $attrValueList[$k]['attrKey'] = $attrName;
                $attrValueList[$k]['attrValueList'] = $ggss;
            }
        }

        $content = str_replace('/minipetmrschool/Data/', __DATAURL__, $pro['content']);
        $pro['content'] = html_entity_decode($content, ENT_QUOTES, 'utf-8');
        //检测产品是否收藏
        $uid = input('uid');
        if (!empty($uid)){
            $col = db('product_sc')->where('uid='. input('uid') .' AND pid='.$proId)->value('id');
            if ($col){
                $pro['collect'] = 1;
            }else{
                $pro['collect'] = 0;
            }
        }
        return json(array('status'=>1,'pro'=>$pro,'commodityAttr'=>$commodityAttr,'attrValueList'=>$attrValueList));
    }

    /**
     * 获取商品详情接口
     */
    public function details(){
        $proId = input('pro_id');
        $pro = db('product')->where('id='.$proId. ' AND del=0 AND is_down=0')->find();
        if (empty($pro)){
            return json(array('status'=>0,'err'=>'商品不存在或已下架！'));
        }
        $content = htmlspecialchars_decode($pro['content']);
        return json(array('status'=>1,'content'=>$content));
    }

    /**
     * 下单信息预处理
     * 处理产品信息，用户地址
     * uid:uid,pid:pro_id,aid:addr_id,sid:shop_id,buff:buff,num:num,price_yh:price_yh,p_price:p_price,price:z_price,type:pay_type,yunfei:yun_id,cart_id:cart_id,remark:ly
     */
    public function makeOrder()
    {
        $proId = input('pro_id');
        $uid = input('uid');
        $pro = db('product')->field('id,shop_id,photo_x,name,price,price_yh')->where('id='.$proId.' AND del=0 AND is_down=0')->find();
        $pro['photo_x'] = __DATAURL__.$pro['photo_x'];
        if (empty($pro)){
            return json(array('status'=>0,'err'=>'商品不存在或已下架！'));
        }
        //获取地址
        $address = NULL;
        $addr = db('address')->where('uid='.$uid)->select();
        if ($addr){
            foreach ($addr as $k => $v){
                if ($v['is_default'] == 1){
                    $address = $address[$k];
                }
            }
            if (empty($address)){
                $address = $addr[0];
            }
        }
        return json(array('status'=>1,'pro'=>$pro,'address'=>$address));
    }

    /**
     * 获取商品详情接口
     */
    public function getBuff()
    {
        $pro = db('product')->where('id='.input('pro_id').' AND del=0 AND is_down=0')->find();
        if (empty($pro)){
            return json(array('status'=>0,'err'=>'商品不存在或已下架！'.__LINE__));
        }
        //处理产品属性
        $catList = array();
        if (!empty($pro['pro_buff'])){
            $proBuff = explode(',', $pro['pro_buff']);
            $buff = array();
            foreach ($proBuff as $k =>$v){
                $attrName = db('attribute')->where('id='.$v)->value('attr_name');
                $guigeList = db('guige')->where('attr_id='.$v.' AND pid='.$pro['id'])->field('id,name')->select();
                $ggss = array();
                $gg = array();
                foreach ($guigeList as $k => $v) {
                    $gg['attrKey'] = $attrName;
                    $gg['attr_id'] = $v;
                    $gg['attrValue'] = $v['name'];
                    $gg['selectedValue'] = $v['id'];
                    $ggss[] = $gg;
                }
                $buff['attrValueList'] = $ggss;
                $catList[] = $buff;
            }
            return json(array('status'=>1,'buff'=>$catList));
        }else{
            return json(array('status'=>0));
        }
    }

    /**
     * 获取商品列表接口
     */
    public function lists()
    {
        $id = input('post.cat_id');//获取分类id 这里id是pro表里的cid
        $brandId = input('post.brand_id');
        $type = input('post.type');//排序类型

        $page = input('post.page', 0);
        $keyword = input('post.keyword');
        //排序
        $order = 'addtime desc';//默认按添加时间排序
        switch ($type){
            case 'ids':
                $order = 'id desc';
                break;
            case 'sale':
                $order = 'shiyong desc';
                break;
            case 'price':
                $order = 'price_yh desc';
                break;
            case 'hot':
                $order = 'renqi desc';
                break;
        }
        //条件
        $where = 'pro_type=1 AND del=0 AND is_down=0';
        if ($id){
            $where .=' AND cid='.$id;
        }
        if (empty($brandId)){
            $where .= ' AND brand_id='.$brandId;
        }
        if ($keyword){
            $where .= ' AND name LIKE "%' .$keyword. '%"';
        }
        if (input("ptype") && input('ptype') == 'new'){
            $where .= ' AND is_show=1';
        }
        if (input("ptype") && input('ptype') == 'hot'){
            $where .= ' AND is_hot=1';
        }
        if (input("ptype") && input('ptype') == 'zk'){
            $where .= ' AND is_sale=1';
        }

        $product = db('product')->where($where)->order($order)->limit($page,8)->select();
        $json = array();
        $jsonArr = array();
        foreach ($product as $k => $v){
            $json['id'] = $v['id'];
            $json['name'] = $v['name'];
            $json['photo_x'] = __DATAURL__.$v['photo_x'];
            $json['price'] = $v['price'];
            $json['price_yh'] = $v['price_yh'];
            $json['shiyong'] = $v['shiyong'];
            $json['intro'] = $v['intro'];
            $jsonArr[] = $json;
        }
        $catName = db('category')->where('id='.$id)->value('name');
        return json(array('status'=>1,'pro'=>$jsonArr,'cat_name'=>$catName));
    }

    /**
     * 获取商品列表接口
     */
    public function getMore()
    {
        $id = input('post.cat_id');//获取分类id 这里id是pro表里的cid
        $brand_id = input('post.brand_id');
        $type = input('post.type');//排序类型

        $page = input('post.page', 0);
        $keyword = input('post.keyword');
        //排序
        $order = 'addtime desc';//默认按添加时间排序
        switch ($type){
            case 'ids':
                $order = 'id desc';
                break;
            case 'sale':
                $order = 'shiyong desc';
                break;
            case 'price':
                $order = 'price_yh desc';
                break;
            case 'hot':
                $order = 'renqi desc';
                break;
        }
        //条件
        $where = 'pro_type=1 AND del=0 AND is_down=0';
        if ($id){
            $where .=' AND cid='.$id;
        }
        if ($brand_id){
            $where .= ' AND brand_id='.$brand_id;
        }
        if ($keyword){
            $where .= ' AND name LIKE "%' .$keyword. '%"';
        }
        if (input("ptype") && input('ptype') == 'new'){
            $where .= ' AND is_show=1';
        }
        if (input("ptype") && input('ptype') == 'hot'){
            $where .= ' AND is_hot=1';
        }
        if (input("ptype") && input('ptype') == 'zk'){
            $where .= ' AND is_sale=1';
        }

        $product = db('product')->where($where)->order($order)->limit($page,8)->select();
        $json = array();
        $jsonArr = array();
        foreach ($product as $k => $v){
            $json['id'] = $v['id'];
            $json['name'] = $v['name'];
            $json['photo_x'] = __DATAURL__.$v['photo_x'];
            $json['price'] = $v['price'];
            $json['price_yh'] = $v['price_yh'];
            $json['shiyong'] = $v['shiyong'];
            $json['intro'] = $v['intro'];
            $jsonArr[] = $json;
        }
        $catName = db('category')->where('id='.$id)->value('name');
        return json(array('status'=>1,'pro'=>$jsonArr,'cat_name'=>$catName));
    }

    /**
     * 获取商品属性价格接口
     */
    public function jiage()
    {
        $buff = trim(input('post.buff'), ',');
        $buffArr = trim(input('post.buff_arr'), ',');
        $pid = input('post.pid');
        $proInfo = db('product')->where('id='.$pid)->find();
        if ($buffArr && $proInfo){
            $arr = explode(',', $buffArr);
            $str = 0;
            foreach ($arr as $k => $v){
                $price[] = db('guige')->where('id='.$v)->value('price');
                $stock[] = db('guige')->where('id='.$v)->value('stock');
            }

            rsort($price);
            sort($stock);
            return json(array('status'=>1,'price'=>$price[0],'stock'=>$stock[0]));
        }
        return json(array('status'=>0));
    }

    /**
     * 会员商品收藏接口
     */
    public function col()
    {
        $uid = input('uid');
        $pid = input('pid');
        if (!$uid || !$pid){
            return json(array('status'=>0,'err'=>'系统错误，请稍后再试.'));
        }

        $check = db('product_sc')->where('uid='.$uid.' AND pid='.$pid)->value('id');
        if ($check){
            $result = db('product_sc')->where('id='.$check)->delete();
        }else{
            $data = [
                'uid' => $uid,
                'pid' => $pid
            ];
            $result = db('product_sc')->insert($data);
        }

        if ($result){
            return json(array('status'=>1));
        }else{
            return json(array('status'=>0,'err'=>'网络错误..'));
        }

    }

    /**
     * 获取抢购商品接口
     */
    public function panic()
    {
        $id = input('post.cat_id');
        $type = input('post.type');
        $keyword = input('post.keyword');
        $order = 'addtime desc';
        $where = 'pro_type=2 AND del=0 AND is_down=0';
        if ($id){
            $where .= ' AND cid='.$id;
        }
        if ($keyword){
            $where .= ' AND name LIKE "%' .$keyword. '%"';
        }
        $product = db('product')->where($where)->order($order)->limit(10)->select();
        $json = array();
        $jsonArr = array();
        foreach ($product as $k => $v){
            $json['id'] = $v['id'];
            $json['name'] = $v['name'];
            $json['photo_x'] = __DATAURL__.$v['photo_x'];
            $json['price'] = $v['price'];
            $json['price_yh'] = $v['price_yh'];
            $json['shiyong'] = $v['shiyong'];
            if ($v['start_time'] > time()){
                $json['state'] = 1;
                if ($v['start_time'] <= strtotime(date('Y-m-d 23:59:59'))){
                    $json['desc'] = date('H:i', $v['start_time']).'开启';
                }else{
                    $json['desc'] = date('n月j日', $v['start_time']).'开启';
                }
            }elseif ($v['end_time'] < time()){
                $json['state'] = 2;
                $json['desc'] = '已结束';
            }elseif ($v['num'] < 1){
                $json['state'] = 3;
                $json['desc'] = '已抢完';
            }else{
                $json['state'] = 4;
                $json['desc'] = '立即抢购';
            }
            $jsonArr[] = $json;
        }
        return json(array('status'=>1,'pro'=>$jsonArr));
    }
















}