<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2017/12/21
 * Time: 8:45
 */

namespace app\api\controller;


use think\Config;

class Order extends Base
{
    /**
     * 用户获取订单信息接口
     */
    public function index()
    {
        $uid = input('post.uid');
        if (empty($uid)){
            return json(array('status'=>0,'err'=>'登录状态异常'));
        }

        $pages = input('post.page', 1);
        $limit = $pages*7-7;

        $orders = db('order');
        $orderp = db('order_product');
        $shangchang = db('shangchang');

        //按条件查询
        $condition = [
            'del' => 0,
            'back' => 0,
            'uid' => $uid,
            'status' => 10
        ];
        $orderType = trim(input('post.order_type'));
        if ($orderType){
            switch ($orderType){
                case 'pay':
                    $condition['status'] = 10;
                    break;
                case 'deliver':
                    $condition['status'] = 20;
                    break;
                case 'receive':
                    $condition['status'] = 30;
                    break;
                case 'evaluate':
                    $condition['status'] = 40;
                    break;
                case 'finish':
                    $condition['status'] = array('IN',array(40,50));
                    break;
                default:
                    $condition['status'] = 10;
                    break;
            }
        }

        //每页显示数量7条数据
        $eachpage = 7;

        $orderStatus = array('0'=>'已取消','10'=>'待付款','20'=>'待发货','30'=>'待收货','40'=>'待评价','50'=>'交易完成','51'=>'交易关闭');
        $order = $orders->where($condition)->order('id desc')->field('id,order_sn,pay_sn,status,price,type,product_num')->select();
        foreach ($order as $k => $v){
            $order[$k]['desc'] = $orderStatus[$v['status']];
            $proList = $orderp->where('order_id='.$v['id'])->find();
            $order[$k]['photo_x'] = __DATAURL__.$proList['photo_x'];
            $order[$k]['pid'] = $proList['pid'];
            $order[$k]['name'] = $proList['name'];
            $order[$k]['price_yh'] = $proList['price'];
            $order[$k]['pro_count'] = $orderp->where('order_id='.$v['id'])->value('COUNT(id)');
        }
        return json(array('status'=>1,'ord'=>$order,'eachpage'=>$eachpage));
    }

    /**
     * 用户退款退货接口
     */
    public function orderRefund()
    {
        $uid = input('uid');
        if (empty($uid)){
            return json(array('status'=>0,'err'=>'登录状态异常'));
        }

        $pages = input('page', 0);

        $orders=db("order");
        $orderp=db("order_product");
        $shangchang = db('shangchang');

        $condition = [
            'back' => array('gt','0')
        ];
        $count = $orders->where($condition)->count();
        $thePage = ceil($count/6);

        $refundStatus = array('1' => '退款申请中', '2' => '已退款', '3' => '处理中', '4' => '已拒绝');
        $order = $orders->where($condition)->order('back_addtime desc')->field('id,price,order_sn,product_num,back,back_addtime')->limit($pages,6)->select();
        foreach ($order as $k => $v){
            $order[$k]['desc'] = $refundStatus[$v['back']];
            $proList = $orderp->where('order_id='.$v['id'])->find();
            $order[$k]['photo_x'] = __DATAURL__.$proList['photo_x'];
            $order[$k]['pid'] = $proList['pid'];
            $order[$k]['name'] = $proList['name'];
            $order[$k]['price_yh'] = $proList['price'];
            $order[$k]['back_addtime'] = date("Y-m-d H:i",$v['back_addtime']);
            $order[$k]['pro_count'] = $orderp->where('order_id='.$v['id'])->value('COUNT(id)');
        }
        return json(array('status'=>1,'ord'=>$order));
    }
    /**
     * 用户订单编辑接口
     */
    public function ordersEdit()
    {
        $orders = db('order');
        $orderId = input('id');
        $type = input('type');

        $checkId = $orders->where('id='.$orderId.' AND del=0')->value('id');
        if (empty($checkId) || empty($type)){
            return json(array('status'=>0,'err'=>'订单信息错误.'.__LINE__));
        }

        $data = array();
        if ($type === 'cancel'){
            $data['status'] = 0;
        }elseif ($type === 'receive'){
            $data['status'] = 40;
        }elseif ($type === 'refund'){
            $data['back'] = 1;
            $data['back_remark'] = input('back_remark');
        }

        if ($data){
            $result = $orders->where('id='.$orderId)->update($data);
            if ($result !== false){
                return json(array('status'=>1));
            }else{
                return json(array('status'=>0,'err'=>'操作失败.'.__LINE__));
            }
        }else{
            return json(array('status'=>0,'err'=>'订单信息错误.'.__LINE__));
        }
    }
    /**
     * 用户订单详情接口
     */
    public function orderDetails()
    {
        $orderId = input('order_id');
        //订单详情
        $orders = db('order');
        $productDp = db('product_dp');
        $orderp = db('order_product');
        $id = input('id');
        $qz = Config::get('DB_PREFIX');

        $orderInfo = $orders->where('id='.$orderId. ' AND del=0')->field('id,order_sn,shop_id,status,addtime,price,type,post,tel,receiver,address_xq,remark')->find();
        if (empty($orderInfo)){
            return json(array('status'=>0,'err'=>'订单信息错误.'));
        }
        $orderStatus = array('0'=>'已取消','10'=>'待付款','20'=>'待发货','30'=>'待收货','40'=>'已收货','50'=>'交易完成');
        //支付类型
        $payType = array('cash'=>'现金支付','alipay'=>'支付宝','weixin'=>'微信支付');

        $orderInfo['shop_name'] = db('shangchang')->where('id='.$orderInfo['shop_id'])->value('name');
        $orderInfo['order_status'] = $orderStatus[$orderInfo['status']];
        $orderInfo['pay_type'] = $payType[$orderInfo['type']];
        $orderInfo['addtime'] = date('Y-m-d H:i:s', $orderInfo['addtime']);
        $orderInfo['yunfei'] = 0;
        if ($orderInfo['post']){
            $orderInfo['yunfei'] = db('post')->where('id='.$orderInfo['post'])->value('price');
        }

        $pro = $orderp->where('order_id='.$orderInfo['id'])->select();
        foreach ($pro as $k => $v){
            $pro[$k]['photo_x'] = __DATAURL__.$v['photo_x'];
        }
        return json(array('status'=>1,'pro'=>$pro,'ord'=>$orderInfo));
    }
    /**
     * 用户订单评论接口
     */
    public function orderComment()
    {
        $id = explode(',', input('get.id'));
        $orderid = input('get.orderid');
        $orderProduct = db('order_product');
        $order = db('order');
        $orderr = $order->where('id='.$orderid)->select();
        foreach ($id as $key => $value) {
            $result[$key]  = $orderProduct->where('`order`='.$orderid.' and pid='.$value)->select();
        }
        $this->assign('result',$result);
        $this->assign('orderr',$orderr);
        $this->display();
    }

    /**
     * 用户订单评论接口
     */
    public function addMessage(){
        $product_dp      = M('product_dp');
        $order_product   = M("order_product");
        $order       = M("order");
        //获取商品的ID
        $id              = $_POST['pid'];
        $id              = explode(",", $id);
        $data['orderid'] = $_POST['orderid'];
        $data['type']    = 1;
        $status['mstatus']=1;
        $data['uid']     = $_SESSION['ID'];
        $data['addtime'] = time();
        foreach ($id as $key => $value) {
            $data['pid']     = $value;
            $data['concent'] = $_POST['content'.$data['pid']];
            $data['num']     = $_POST['pingfen'.$data['pid']];
            $result   = $product_dp->add($data);
        }
        /*$meresult = $product_dp->where('uid='.$_SESSION['ID'].' and orderid='.$_POST['orderid'].' and pid='.$_POST['id'])->select();
        if($meresult){
        	echo 2;
        	exit();
        }*/

        if($result){
            $order->where('id='.$_POST['orderid'])->save($status);
            $this->success('评价成功',U('User/orders',array('key'=>$_POST['key'])));
        }else{
            $this->error('评价失败');
        }

    }






























}