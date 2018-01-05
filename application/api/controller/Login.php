<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2017/12/14
 * Time: 15:37
 */

namespace app\api\controller;

use think\Config;
use think\Cookie;
use think\Session;
use wlt\wxmini\WXLoginHelper;

class Login extends Base
{
    //***************************
    //  前台登录接口
    //***************************
    public function dologin()
    {
        Session::clear();
        $name = trim(input('post.username'));
        $pwd = md5(md5(input('post.pwd')));
        if(empty($name) || empty($pwd)){
            return json(array('status' => 0,'err' => '请输入账号或密码！'));
        }

        $map['name'] = $name;
        $map['pwd'] = $pwd;
        $userNum = db('user')->where($map)->find();
        if(!empty($userNum)){
            Cookie::set('sessionid', session_id());
            //Session::set('sessionid', session_id());
            Session::set('logincheck', md5($name), 'user');
            Session::set('loginname', $name, 'user');
            Session::set('id', $userNum['id'], 'user');
            Session::set('photo', $userNum['photo'], 'user');

            return json(array('status' => 1, 'session' => $_SESSION));
        }else{
            return json(array('status' => 0, 'err' => '账号密码错误！'));
        }
    }

    //***************************
    //  授权登录接口
    //***************************
    public function authLogin()
    {
        $openid = input('post.openid');
        if(empty($openid)){
            return json(array('status' => 0, 'err' => '授权失败！'.__LINE__));
        }

        $con = [];
        $con['openid'] = $openid;
        $uid = db('user')->where($con)->find();
        if(!empty($uid)){
            if($uid['del'] == 1){
                return json(array('status' =>0,'err'=>'账号状态异常'));
            }

            $arr = [
                'id' => $uid['id'],
                'nickname' => input('post.NickName'),
                'headurl' => input('post.HeadUrl')
            ];
            return json(array('status' => 1,'arr' => $arr));
        }else{
            $data = [
              'name' => input('post.NickName'),
              'uname' => input('post.NickName'),
              'photo' => input('post.HeadUrl'),
              'sex' => input('post.gender'),
              'openid' => $openid,
              'source' => 'wx',
              'addtime' => time()
            ];
            if(empty($data['openid'])){
                return json(array('status' => 0,'err' => '授权失败！'.__LINE__));
            }
            $res = db('user')->insert($data);
            if($res){
                $arr = [
                    'id' => intval($res),
                    'nickname' => $data['name'],
                    'headurl' => $data['photo']
                ];
                return json(array('status' => 1, 'arr' => $arr));
            }else{
                return json(array('status' => 0,'err' => '授权失败！'.__LINE__));
            }
        }
    }

    //***************************
    //  前台注册接口
    //***************************
    public function register()
    {
        $name = trim(input('post.user'));
        $pwd = md5(md5(input('post.pwd')));
        $pwds = md5(md5(input('post.pwds')));
        if($pwd != $pwds){
            return json(array('status'=>0,'err'=>'两次输入密码不同！'));
        }

        $user = db('user');
        $count = $user->where('name='.$name)->count();
        if ($count){
            return json(array('status'=>0,'err'=>'用户名已被注册了！'));
        }

        $check_mobile = $user->where('tel='.trim(input('post.tel')))->count();
        if($check_mobile){
            return json(array('status'=>0,'err'=>'手机号已存在！'));
        }

        $data = [
            'name' => $name,
            'qx' => 6,
            'pwd' => $pwd,
            'tel' => trim(input('post.tel')),
            'addtime' => time()
        ];
        $res = $user->insert($data);
        if($res){
            Session::set('loginname', $name);
            Session::set('id', $res);
            $arr = [
                'status' => 1,
                'uid' => $res,
                'loginname' => $name
            ];
            return json($arr);
        }else{
            return json(array('status'=>0,'err'=>'注册失败！'));
        }
    }

    //***************************
    //  获取sessionkey 接口
    //***************************
    public function getSessionKey()
    {
        $code = input("code", '', 'htmlspecialchars_decode');
        $rawData = input("rawData", '', 'htmlspecialchars_decode');
        $signature = input("signature", '', 'htmlspecialchars_decode');
        $encryptedData = input("encryptedData", '', 'htmlspecialchars_decode');
        $iv = input("iv", '', 'htmlspecialchars_decode');

        $wxHelper = new WXLoginHelper();
        $data = $wxHelper->checkLogin($code, $rawData, $signature, $encryptedData, $iv);
        
        return json($data);
    }



    //***************************
    //  前台退出登录接口
    //***************************
    public function logout(){
        Session::delete('uid');
        Session::delete('loginname');
        Session::clear();
        return json(array('status'=>1));
    }
}