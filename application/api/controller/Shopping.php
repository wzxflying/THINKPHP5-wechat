<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2017/12/23
 * Time: 9:40
 */

namespace app\api\controller;


class Shopping extends Base
{
    /**
     * 会员获取购物车列表
     */
    public function index()
    {
        $shopping = db('shopping_char');
        $product = db('product');
        $user_id = input('user_id');
        if (empty($user_id)){
            return json(array('status'=>0));
        }

        $cart = $shopping->where('uid='.$user_id)->field('id,uid,pid,price,num')->select();
        foreach ($cart as $k => $v){
            $proInfo = $product->where('id='.$v['pid'])->field('name,photo_x')->find();
            $cart[$k]['pro_name'] = $proInfo['name'];
            $cart[$k]['photo_x'] = __DATAURL__.$proInfo['photo_x'];
        }
        return json(array('status'=>1,'cart'=>$cart));
    }
    /**
     * 购物车商品删除
     */
    public function delete()
    {
        $shopping = db('shopping_char');
        $cart_id = input('cart_id');
        $check_id = $shopping->where('id='.$cart_id)->value('id');
        if (empty($check_id)){
            return json(array('status'=>1));
        }

        $result = $shopping->where('id='.$cart_id)->delete();
        if ($result){
            return json(array('status'=>1));
        }else{
            return json(array('status'=>0));
        }
    }
    /**
     * 会员修改购物车数量接口
     */
    public function upCart()
    {
        $shopping = db('shopping_char');
        $uid = input('user_id');
        $cart_id = input('cart_id');
        $num = input('num');
        if (empty($uid) || empty($cart_id) || empty($num)){
            return json(array('status'=>0,'err'=>'网络异常.'.__LINE__));
        }

        $check = $shopping->where('id='.$cart_id)->find();
        if (empty($check)){
            return json(array('status'=>0,'err'=>'购物车信息错误！'));
        }
        $proNum = db('product')->where('id='.$check['pid'])->value('num');
        if ($num > $proNum){
            return json(array('status'=>0,'err'=>'库存不足！'));
        }
        $data = [
            'num' => $num
        ];
        $result = $shopping->where('id='.$cart_id. ' AND uid='.$uid)->update($data);
        if ($result){
            return json(array('status'=>1,'succ'=>'操作成功!'));
        }else{
            return json(array('status'=>0,'err'=>'操作失败.'));
        }
    }
    /**
     * 多个商品删除
     */
    public function qdelete()
    {
        $uid = input('uid');
        if (empty($uid)){
            return json(array('status'=>0,'err'=>'网络异常，请稍后再试.'));
        }
        $shopping = db('shopping_char');
        $cart_id = trim(input('cart_id'), ',');
        if (empty($cart_id)){
            return json(array('status'=>0,'err'=>'网络错误，请稍后再试.'));
        }
        $result = $shopping->where('id in ('.$cart_id.') AND uid='.$uid)->delete();
        if ($result){
            return json(array('status'=>1));
        }else{
            return json(array('status'=>0,'err'=>'操作失败.'));
        }

    }
    /**
     * 添加购物车
     */
    public function add()
    {
        $uid = input('uid');
        if (empty($uid)){
            return json(array('status'=>0,'err'=>'登录状态异常.'));
        }
        $pid = input('pid');
        $num = input('num');
        if (empty($pid) || empty($num)){
            return json(array('status'=>0,'err'=>'参数错误.'));
        }

        $check = $this->checkCart($pid);
        if ($check['status'] == 0){
            return json(array('status'=>0,'err'=>$check['err']));
        }
        $checkInfo = db('product')->where('id='.$pid.' AND del=0 AND is_down=0')->find();
        if ($num >= $checkInfo['num']){
            return json(array('status'=>0,'err'=>'库存不足！'));
        }
        $shopping = db('shopping_char');

        $data = array();
        $cartInfo =$shopping->where('pid='.$pid.' AND uid='.$uid)->field('id,num,shop_id')->find();
        if (!empty($cartInfo)){
            $data['num'] = $num;
            if ($num >= $checkInfo['num']){
                return json(array('status'=>0,'err'=>'库存不足！'));
            }
            $result = $shopping->where('id='.$cartInfo['id'])->update($data);
        }else{
            $ptype = 1;
            if ($checkInfo['pro_type']){
                $ptype = $checkInfo['pro_type'];
            }
            $data = [
                'pid' => $pid,
                'num' => $num,
                'addtime' => time(),
                'uid' =>$uid,
                'type' => $ptype,
                'price' => $checkInfo['price_yh']
            ];
            $result = $shopping->insert($data);
        }
        if ($result){
            return json(array('status'=>1,'cart_id'=>$result));
        }else{
            return json(array('status'=>0,'err'=>'加入失败.'));
        }
    }
    /**
     * 会员立即购买下单
     */
    public function checkShop()
    {
        $cart_id = trim(input('cart_id'), ',');
        $id = explode(',', $cart_id);
        if (empty($cart_id)){
            return json(array('status'=>0));
        }

        foreach ($id as $k => $v){
            $shop[$k] = db('shopping_char')->where('id='.$v)->field('shop_id,pid')->find();
        }
        foreach ($shop as $k => $v){
            $result[$k] = db('product')->where('id='.$v['pid'])->field('id,price,price_yh')->select();
            $price[] = i_array_column($result[$k], 'price_yh');
        }

        $str = NULL;
        foreach ($price as $k => $v){
            $str .= implode(',', $v).',';
        }
        $str = trim($str, ',');
        $parr = explode(',', $str);
        if (array_sum($parr) && in_array('0', $parr)){
            return json(array('status'=>0));
        }
        $names = i_array_column($shop, 'shop_id');
        $arr = array_unique($names);
        $val = sizeof($arr);
        if ($val == 1){
            return json(array('status'=>1));
        }else{
            return json(array('status'=>2));
        }
    }
    /**
     * 购物车添加
     */
    public function checkCart($pid)
    {
        $checkInfo = db('product')->where('id='.$pid.' AND del=0 AND is_down=0')->find();
        if (empty($checkInfo)){
            return array('status'=>0,'err'=>'商品不存在或已下架.');
        }
        return array('status'=>1);
    }

























}