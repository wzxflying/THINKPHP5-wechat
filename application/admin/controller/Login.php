<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2018/1/8
 * Time: 15:18
 */

namespace app\admin\controller;


use think\Request;
use think\Session;

class Login extends Base
{
    /**
     * 登录
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        if (Request::instance()->isPost()){
            $username = Request::instance()->post('username');
            $adminInfo = db('adminuser')->where('name='.$username.' AND del<1')->find();
            $appcheck = db('program')->find();
            if (empty($adminInfo)){
                $this->error('账号不存在或已注销！');
            }else{
                if (md5(md5(Request::instance()->post('pwd'))) == $adminInfo['pwd']){
                    $admin = [
                        'id' => $adminInfo['id'],
                        'name' => $adminInfo['name'],
                        'qx' => $adminInfo['qx'],
                        'shop_id' => $adminInfo['shop_id']
                    ];
                    $system = [
                        'name' => $appcheck['name'],
                        'sysname' => $appcheck['title'],
                        'photo' => $appcheck['logo']
                    ];
                    Session::delete('admininfo');
                    Session::delete('system');
                    Session::set('admininfo', $admin);
                    Session::set('system', $system);
                    echo "<script>alert('登录成功');location.href='".url('Index/index')."'</script>";
                }else{
                    $this->error('账号密码错误！');
                }
            }
        }else{
            $sysname = db('program')->find();
            $this->assign('sysname', $sysname['title']);
            return $this->fetch();
        }
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        Session::delete('admininfo');
        Session::delete('system');
        echo "<script>alert('注销成功');location.href='".url('Login/index')."'</script>";
        exit;
    }
}