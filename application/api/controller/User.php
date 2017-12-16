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
            'refund_num' => $order->where('uid='.$uid.' AND back>0')->count('id')
        ];
        return json(array('status'=>1,'orderInfo'=>$order));
    }

    /***
     * 修改用户密码
     */
    public function findfwd_edit()
    {
        $name = input('post.name');
        $tel = input('post.tel');
        $newpwd = input('post.newpwd');
        $newpwds = input('post.newpwds');
        if (empty($name)){
            $this->error('请输入用户名', url('User/findfwd',array('key'=>input('key'))));
        }
        if (empty($tel)){
            $this->error('请输入手机', url('User/findfwd',array('key'=>input('key'))));
        }
        if ($newpwd!=$newpwds){
            $this->error('两次密码输入不同', url('User/findfwd',array('key'=>input('key'))));
        }else{
            $yzm = input('post.yzm');
            $data['pwd'] = md5(md5($newpwd)); //新密码
            $sms_o = file_get_contents('Public/Rand'.$tel.'.txt');
            if ($sms_o!=$yzm){
                $this->error('验证码错误', url('User/findfwd', array('key'=>input('key'))));
            }else{
                $result = db('user')->where('name = '.$name)->update($data);
                if ($result !== false){
                    $this->success('修改成功', url('User/logo', array('key'=> input('key'))));
                }else{
                    $this->error('修改失败', url('User/logo', array('key'=> input('key'))));
                }
            }

        }
    }

    /**
     * 获取用户信息
     */
    public function userInfo()
    {
        $uid = input('post.uid');
        if (!$uid){
            return json(array('status'=>0,'err'=>'非法操作!'));
        }

        $user = db('user')->where('id = '.$uid)->field('id,name,uname,photo,tel')->find();
        if ($user['photo']){
            if (empty($user['source'])){
                $user['photo'] = __DATAURL__.$user['photo'];
            }
        }else{
            $user['photo'] = __PUBLICURL__.'home/images/moren.png';
            $user['tel'] = substr_replace($user['tel'], '*******',3,4);
            return json(array('status'=>1,'userinfo'=>$user));
        }
    }

    /**
     * 修改用户信息
     */
    public function userEdit()
    {
        $userId = input('user_id');
        $oldPwd = input('old_pwd');
        $pwd = input('new_pwd');
        $oldTel = input('old_tel');
        $tel = input('new_tel');
        $uname = input('uname');

        $userInfo = db('user')->where('id = '.$userId.' AND del=0')->find();
        if (!$userInfo){
            return json(array('status'=>0,'err'=>'会员信息错误.'));
        }

        $arr = input('post.photo');
        $data = [];
        if (empty($arr)){
            $data['photo'] = $arr;
        }

        //用户密码检测
        if (!empty($pwd)){
            if ($userInfo['pwd'] && md5(md5($oldPwd)) !== $userInfo['pwd']){
                return json(array('status'=>0,'err'=>'旧密码不正确.'));
            }
        }
        //用户手机检测
        if ($tel){
            if ($userInfo['tel'] && $oldTel!==$userInfo['tel']){
                return json(array('status'=>0,'err'=>'原手机号不正确.'));
            }
            $checkTel = db('user')->where('tel = '.$tel.' AND del=0')->count();
            if ($checkTel){
                return json(array('status'=>0,'err'=>'新手机号已存在.'));
            }
            $data['tel'] = trim($tel);
        }

        if ($uname && $uname!==$userInfo['uname']) {
            $data['uname'] = trim($uname);
        }

        if (empty($data)){
            return json(array('status'=>0,'err'=>'您没有输入要修改的信息.'.__LINE__));
        }

        $result = db('user')->where('id='.$userId)->update($data);
        if ($result){
            return json(array('status'=>1));
        }else{
            return json(array('status'=>0,'err'=>'操作失败.'));
        }

    }

    /**
     * 用户反馈接口
     */
    public function feedBack()
    {
        $uid = input('uid');
        if (empty($uid)){
            return json(array('status'=>0,'err'=>'登录状态异常.'));
        }

        $con = input('post.con');
        if (empty($con)){
            return json(array('status'=>0,'err'=>'请输入反馈内容.'));
        }

        $data = [
            'uid' => $uid,
            'message' => $con,
            'addtime' => time()
        ];
        $result = db('fankui')->insert($data);
        if ($result){
            return json(array('status'=>1));
        }else{
            return json(array('status'=>0,'保存失败！'));
        }
    }

    /**
     * 用户商品收藏信息
     */
    public function collection()
    {
        $userId = input('id');
        if (empty($userId)){
            return json(array('status'=>0,'err'=>'系统错误，请稍后再试.'));
        }

        $proSC = db('product_sc');
        $count = $proSC->where('uid='.$userId)->count();
        //分页没实现

        $scList = $proSC->where('uid='.$userId)->order('id desc')->select();
        foreach ($scList as $k => $v){
            $proInfo = db('product')->where('id='. $v['pid'] .' AND del=0 AND is_down=0')->find();
            if ($proInfo){
                $scList[$k]['pro_name'] = $proInfo['name'];
                $scList[$k]['photo'] = __DATAURL__.$proInfo['photo_x'];
                $scList[$k]['price_yh'] = number_format($proInfo['price_yh'], 2);
            }else{
                $proSC->where('id='.$v['id'])->delete();
            }
        }
        return json(array('status'=>1,'sc_list'=>$scList));
    }

    /**
     * 用户单个商品取消收藏
     */


































}