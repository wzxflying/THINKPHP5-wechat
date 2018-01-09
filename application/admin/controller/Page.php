<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2018/1/9
 * Time: 11:53
 */

namespace app\admin\controller;


class Page extends Admin
{
    public function adminindex()
    {
        return $this->fetch();
    }
    public function shopindex()
    {
        return $this->fetch();
    }
}