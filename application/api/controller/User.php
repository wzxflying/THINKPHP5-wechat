<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2017/12/15
 * Time: 16:17
 */

namespace app\api\controller;


use think\captcha\Captcha;

class User extends Base
{
    public function verify(){
        $captcha = new Captcha();
        return $captcha->entry();
    }

    //***************************
    //  获取用户订单数量
    //***************************
    public function getOrder()
    {
        $uid = input('post.userid');
        if (empty($uid)){
            return json(array('status'=>0,'err'=>'非法操作.'));
        }
        $order = db('order');
        $order = [
            'pay_num' => $order->where('uid='.$uid.' AND status=10 AND del=0')->count('id'),
            'rec_num' => $order->where('uid='.$uid.' AND status=30 AND del=0 AND back="0"')->count('id'),
            'finish_num' => $order->where('uid='.$uid.' AND status>30 AND del=0 AND back="0"')->count('id'),
            'refund_num' => $order->where('uid='.$uid.' AND back>"0"')->count('id')
        ];
        return json(array('status'=>1,'orderInfo'=>$order));
    }
}