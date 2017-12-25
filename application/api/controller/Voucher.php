<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2017/12/25
 * Time: 9:26
 */

namespace app\api\controller;


class Voucher extends Base
{
    /**
     * 所有单页数据接口
     */
    public function index()
    {
        $condition = [
            'del' => 0,
            'start_time' => array('lt', time()),
            'end_time' => array('gt', time())
        ];
        $vou = db('voucher')->where($condition)->order('addtime desc')->select();
        foreach ($vou as $k => $v){
            $vou[$k]['start_time'] = date('Y.m.d', $v['start_time']);
            $vou[$k]['end_time'] = date('Y.m.d', $v['end_time']);
            $vou[$k]['amount'] = $v['amount'];
            $vou[$k]['full_money'] = $v['full_money'];
            if ($v['proid'] == 'all' || empty($v['proid'])){
                $vou[$k]['desc'] = '店内通用';
            }else{
                $vou[$k]['desc'] = '限定商品';
            }
        }
        return json(array('status'=>1,'vou'=>$vou));
    }

    /**
     * 用户领取优惠券
     */
    public function getVoucher()
    {
        $vid = input('vid');
        $uid = input('uid');
        $check_user = db('user')->where('id='.intval($uid).' AND del=0')->find();
        if (!$check_user) {
            return json(array('status'=>0,'err'=>'登录状态异常！err_code:'.__LINE__));
        }
        $check_vou = db('voucher')->where('id='.intval($vid).' AND del=0')->find();
        if (!$check_vou) {
            return json(array('status'=>0,'err'=>'优惠券信息错误！err_code:'.__LINE__));
        }
        //判断是否已领取过
        $check = db('user_voucher')->where('uid='.intval($uid).' AND vid='.intval($vid))->value('id');
        if ($check) {
            return json(array('status'=>0,'err'=>'您已经领取过了！'));
        }
        if (intval($check_vou['point'])!=0 && intval($check_vou['point'])>intval($check_user['jifen'])) {
            return json(array('status'=>0,'err'=>'积分余额不足！'));
        }

        if ($check_vou['start_time']>time()) {
            return json(array('status'=>0,'err'=>'优惠券还未生效！'));
        }

        if ($check_vou['end_time']<time()) {
            return json(array('status'=>0,'err'=>'优惠券已失效！'));
        }

        if (intval($check_vou['count'])<=intval($check_vou['receive_num'])) {
            return json(array('status'=>0,'err'=>'优惠券已被领取完了！'));
        }

        $data = [
            'uid' => $uid,
            'vid' => $vid,
            'shop_id' => $check_vou['shop_id'],
            'full_money' => $check_vou['full_money'],
            'amount' => $check_vou['amount'],
            'start_time' => $check_vou['start_time'],
            'end_time' => $check_vou['end_time'],
            'addtime' => time()
        ];
        $result = db('user_voucher')->insert($data);
        if ($result) {
            //修改会员积分
            if (intval($check_vou['point'])!=0) {
                $arr = array();
                $arr['jifen'] = intval($check_user['jifen'])-intval($check_vou['point']);
                $up = db('user')->where('id='.intval($uid))->update($arr);
            }

            //修改领取数量
            $arrs = array();
            $arrs['receive_num'] = intval($check_vou['receive_num'])+1;
            $ups = db('voucher')->where('id='.intval($vid))->update($arrs);

            return json(array('status'=>1));
        }else{
            return json(array('status'=>0,'err'=>'领取失败！'));
        }
    }
}