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
    public function unCollection()
    {
        $scId = input('id');
        if (empty($scId)){
            return json(array('status'=>0,'err'=>'非法操作.'));
        }
        $product = db('product_sc');
        $result = $product->where('id='.$scId)->select();
        if ($result){
            return json(array('status'=>1));
        }else{
            return json(array('status'=>0,'err'=>'网络异常！'.__LINE__));
        }
    }

    /**
     * 用户单个店铺取消收藏
     */
    public function unFollow()
    {
        $scId = input('id');
        if (empty($scId)){
            return json(array('status'=>0,'err'=>'非法操作.'));
        }

        //取消关注店铺
        $result = db('shangchang_sc')->where('id='.$scId)->delete();
        if ($result){
            return json(array('status'=>1));
        }else{
            return json(array('status'=>0,'err'=>'网络异常！'.__LINE__));
        }
    }

    /**
     * 获取用户店铺收藏数据
     */
    public function shangchang()
    {
        //关注店铺
        $userId = input('user_id');
        if (empty($userId)){
            return json(array('status'=>0,'err'=>'系统错误，请稍后再试.'));
        }

        $result = db('shangchang_sc')->where('uid='.$userId)->paginate(4);
        $page = $result->render();

        $scList = db('shangchang_sc')->where('uid='.$userId)->order('id desc')->select();
        foreach ($scList as $k=>$v){
            $scInfo = db('shangchang')->where('id='.$v['shop_id'])->find();
            if ($scInfo){
                $scList[$k]['shop_name'] = $scInfo['name'];
                $scList[$k]['uname'] = $scInfo['uname'];
                $scList[$k]['logo'] = __DATAURL__.$scInfo['logo'];
                $scList[$k]['tel'] = $scInfo['tel'];
                $scList[$k]['sheng'] = db('china_city')->where('id='.$scInfo['sheng'])->value('name');
                $scList[$k]['city'] = db('china_city')->where('id='.$scInfo['city'])->value('name');
                $scList[$k]['quyu'] = db('china_city')->where('id='.$scInfo['quyu'])->value('name');
            }else{
                db('shangchang_sc')->where('id='.$v['id'])->select();
            }
        }

        return json(array('status'=>1,'sc_list'=>$scList));
    }

    /**
     * H5头像上传
     */
    public function uploadify()
    {
        $imgtype = [
            'gif' => 'gif',
            'png' => 'png',
            'jpg' => 'jpg',
            'jpeg' => 'jpeg'
        ];//图片类型在传输过程中对应的头信息
        $message = input('message');//接受以base64编码的图片数据
        $filename = input('filename');//自定义文件名称
        $filetype = input('filetype');//接收文件类型
        //首先将头信息去掉，然后解码剩余的base64编码的数据
        $message = base64_decode(substr($message, strlen('data:image/'.$imgtype[strtolower($filetype)].';base64')));
        $filename2 = $filename.'.'.$filetype;
        $fileurl = './Data/UploadFiles/user_img/'. date('Ymd');
        if (!is_dir($fileurl)){
            @mkdir($fileurl, 0777);
        }
        $fileurl = $fileurl.'/';

        //开始写文件
        $picUrl = $fileurl.$filename2;
        $file = fopen($picUrl, 'w');
        if (fwrite($file,$message) === false){
            return json(array('status'=>0,'err'=>'failed'));
        }

        //图片url地址
        $image = \think\Image::open($picUrl);
        //生成一个居中裁剪为100x100的缩略图
        $image->thumb(100, 100,\think\Image::THUMB_CENTER)->save($picUrl);

        $uid = input('uid');
        if (empty($uid)){
            return json(array('status'=>0,'err'=>'登录状态异常！error'));
        }
        //获取原来的头像链接
        $oldPic = db('user')->where('id='.$uid)->value('photo');
        $oldPic2 = './Data/'.$oldPic;

        $data = [
            'photo' => 'UploadFiles/user_img/'.date('Ymd').'/'.$filename2
        ];
        $up = db('user')->where('id='.$uid)->update($data);
        if ($up){
            //如果原头像存在就删除
            if ($oldPic && file_exists($oldPic2)){
                @unlink($oldPic2);
            }
            return json(array('status'=>1,'urls'=>'Data/'.$data['photo']));
        }else{
            return json(array('status'=>0,'err'=>'头像保存失败.'));
        }

    }

    /**
     * 用户修改密码接口
     */
    public function forgetPwd()
    {
        $userName = trim(input('username'));
        $tel = trim(input('tel'));
        if (!$userName || !$tel){
            return json(array('status'=>0,'err'=>'请输入账号或手机号.'));
        }

        $where = [
            'name' => $userName,
            'tel' => $tel
        ];
        $check = db('user')->where($where)->count();
        if ($check){
            return json(array('status'=>1));
        }else{
            return json(array('status'=>0,'err'=>'账号不存在.'));
        }
    }

    /**
     * 用户修改密码接口
     */
    public function upPwd()
    {
        $psw = trim(input('psw'));
        if (!$psw){
            return json(array('status'=>0,'err'=>'请输入新密码.'));
        }
        $userName = trim(input('user'));
        $tel = trim(input('tel'));
        if (!$userName || !$tel){
            return json(array('status'=>0,'err'=>'系统错误，请稍后再试.'));
        }

        $where = [
            'name' => $userName,
            'tel' => $tel
        ];
        $pwd = md5(md5($psw));
        $up = db('user')->where($where)->update(array('pwd' => $pwd));
        if ($up){
            return json(array('status'=>1));
        }else{
            return json(array('status'=>0,'err'=>'账号不存在.'));
        }
    }

    /**
     * 获取用户优惠券
     */
    public function voucher()
    {
        $uid = input('uid');
        if (empty($uid)){
            return json(array('status'=>0,'err'=>'登录状态异常！'.__LINE__));
        }
        //获取未使用或者已失效的优惠券
        $nouse = array();
        $nouses = array();
        $offdate = array();
        $offdates = array();
        $vouList = db('user_voucher')->where('uid='.$uid.' AND status!=2')->select();
        foreach ($vouList as $k => $v){
            $vouInfo = db('voucher')->where('id='.$v['vid'])->find();
            if ($vouInfo['del'] == 1 || $vouInfo['end_time'] < time()){
                $offdate['vid'] = $vouInfo['id'];
                $offdate['full_money'] = $vouInfo['full_money'];
                $offdate['amount'] = $vouInfo['amount'];
                $offdate['start_time'] = date('Y.m.d', $vouInfo['start_time']);
                $offdate['end_time'] = date('Y.m.d', $vouInfo['end_time']);
                $offdates[] = $offdate;
            }elseif ($vouInfo['end_time'] > time()){
                $nouse['vid'] = $vouInfo['id'];
                $nouse['shop_id'] = $vouInfo['shop_id'];
                $nouse['title'] = $vouInfo['title'];
                $nouse['full_money'] = $vouInfo['full_money'];
                $nouse['amount'] = $vouInfo['amount'];
                if ($vouInfo['proid'] == 'all' || empty($vouInfo['proid'])){
                    $nouse['desc'] = '店内通用';
                }else{
                    $nouse['desc'] = '限定商品';
                }
                $nouse['start_time'] = date('Y.m.d', $vouInfo['start_time']);
                $nouse['end_time'] = date('Y.m.d', $vouInfo['end_time']);
                if ($vouInfo['proid']){
                    $proid = explode(',', $vouInfo['proid']);
                    $nouse['proid'] = $proid[0];
                }
                $nouses[] = $nouse;
            }
        }
        //获取已使用的优惠券
        $userd = array();
        $userds = array();
        $userVoucher = db('user_voucher')->where('uid='.$uid.' AND status=2')->select();
        foreach ($userVoucher as $k => $v){
            $vouInfo = db('voucher')->where('id='.$v['vid'])->find();
            $userd['vid'] = $vouInfo['id'];
            $userd['full_money'] = $vouInfo['full_money'];
            $userd['amount'] = $vouInfo['amount'];
            $userd['start_time'] = date('Y.m.d', $vouInfo['start_time']);
            $userd['end_time'] = date('Y.m.d', $vouInfo['end_time']);
            $userds[] = $userd;
        }

        return json(array('status'=>1,'offdates'=>$offdates,'nouses'=>$nouses,'useds'=>$userds));
    }
}