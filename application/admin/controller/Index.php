<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2018/1/9
 * Time: 9:53
 */

namespace app\admin\controller;


class Index extends Admin
{
    public function index()
    {
        $index = "<iframe src='".url('Page/adminindex')."' id='iframe' name='iframe'></iframe>";
        $copy = db('web')->where('id=5')->value('concent');
        $this->assign('copy', $copy);
        $this->assign('index', $index);
        return $this->fetch();
    }
}