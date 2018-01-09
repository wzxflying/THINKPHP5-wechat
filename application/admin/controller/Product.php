<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2018/1/9
 * Time: 15:23
 */

namespace app\admin\controller;


class Product extends Admin
{
    public function index()
    {
        $id = $this->request->get('id');

        //搜索变量
        $type = $this->request->get('type');
        $name = $this->request->get('name');
        $tuijian = $this->request->get('tuijian');
        //搜索
        $where = [
            'pro_type' => 1,
            'del' => ['<',1],
        ];

        $productList = db('product')
            ->where($where)
            ->order('addtime desc')
            ->paginate(5)->each(function ($item, $key){
                $item['cname'] = db('category')->where('id='.$item['cid'])->value('name');
                $item['brand'] = db('brand')->where('id='.$item['brand_id'])->value('name');
                return $item;
            });
        $this->assign('id',$id);
        $this->assign('name',$name);
        $this->assign('tuijian',$tuijian);
        $this->assign('type',$type);

        $this->assign('productlist',$productList);
        $this->assign('page',$productList->render());
        return $this->fetch();
    }
}