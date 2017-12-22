<?php
/**
 * Created by PhpStorm.
 * User: wzx
 * Date: 2017/12/22
 * Time: 19:37
 */

namespace app\api\controller;


use think\Config;

class Pay extends Base
{
    /**
     * 微信支付接口
     *   {"appid":"wx0411fa6a39d61297","noncestr":"3reA4pSqGBPPryEL","package":"Sign=WXPay","partnerid":"1230636401","prepayid":"wx20170406175858b261288c050041764688","timestamp":1491472738,"sign":"4A50B67D47E062A1F3B739D76C683D37"}
     *  appid
     *  noncestr
     *  package
     *  partnerid
     *  prepayid
     *  timestamp
     *  sign
     */
    public function dowxpay()
    {
        vendor('wxpay.wxpay');
        $notifyUrl = Config::get('weixin.notify_url');

        $uid = input('uid');
        if (empty($uid)){
            return json(array('status'=>0,'err'=>'登录状态异常.'));
        }

        $orderId = input('order_id');
        $payType = trim(input('pay_type'));
        $orderSn = trim(input('order_sn'));

        $order = db('order')->where('id='.$orderId. ' AND order_sn='.$orderSn.' AND del=0')->find();
        if (empty($order)){
            return json(array('status'=>0,'err'=>'订单信息错误.'));
        }
        $product = db('order_product')->where('order_id='.$order['id'])->field('name')->select();
        $body = null;
        foreach ($product as $k => $v){
            if ($k == 0){
                $body .= $v['name'];
            }else{
                $body .= ','.$v['name'];
            }
        }

        $total = $order['price']*100; //转成分
        $subject = '小程序：'.$body; //商品名称
        $outTradeNo = $orderSn; //订单号

        $input = new \WxPayUnifiedOrder();
        $data = new \WxPayDataBase();
        $input->SetBody($body);
        $input->SetAttach('小程序');
        $input->SetOut_trade_no($outTradeNo);
        $input->SetTotal_fee($total);//订单总额
        $input->SetTime_start(date('YmdHis'));//订单时间
        $input->SetTime_expire(date('YmdHis', time()+600));//订单失效时间
        $input->SetGoods_tag('');//设置商品标记，代金券或立减优惠功能的参数，说明详见代金券或立减优惠
        $input->SetNotify_url($notifyUrl);//异步通知地址
        $input->SetTrade_type('APP');
        $orderData = \WxPayApi::unifiedOrder($input);
        $array = [
            'appid' => $orderData['appid'],
            'noncestr' => $orderData['nonce_str'],
            'package' => 'Sign=WXPay',
            'partnerid' => $orderData['mch_id'],
            'prepayid' => $orderData['prepay_id'],
            'timestamp' => time()
        ];
        $str = 'appid='.$array['appid'].'&noncestr='.$array['noncestr'].'&package=Sign=WXPay&partnerid='.$array['partnerid'].'&prepayid='.$array['prepayid'].'&timestamp='.$array['timestamp'];
        //重新生成签名
        $array['sign'] = strtoupper(md5($str.'$key='.\WxPayConfig::KEY));

        return json(array('status'=>1,'success'=>$array));
    }
}