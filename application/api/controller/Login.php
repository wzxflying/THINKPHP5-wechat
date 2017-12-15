<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2017/12/14
 * Time: 15:37
 */

namespace app\api\controller;

use think\Cookie;
use think\Session;

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
//        if(empty($openid)){
//            return json(array('status' => 0, 'err' => '授权失败！'.__LINE__));
//        }

        $con = [];
        $con['openid'] = trim($openid);
        $uid = db('user')->where($con)->field('id')->select();
        if(!empty($uid)){
            $userinfo = db('user')->where('id ='. intval($uid))->find();
            if(intval($userinfo['del'] == 1)){
                return json(array('status' =>0,'err'=>'账号状态异常'));
            }

        }
    }
}