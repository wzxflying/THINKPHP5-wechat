<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2018/1/11
 * Time: 11:31
 */

namespace app\admin\controller;


class Category extends Admin
{
    public function index()
    {
        $list = db('category')->where('tid=0 AND del<2')->order('sort desc,id asc')->field('id,tid,name,hot,del')->select();
        foreach ($list as $k => $v){
            $list[$k]['list2'] = db('category')->where('tid='.$v['id'])->field('id,tid,name,hot')->select();
            foreach ($list[$k]['list2'] as $k2 => $v2){
                $list[$k]['list2'][$k2]['list3'] = db('category')->where('tid='.$v2['id'])->field('id,tid,name,hot')->select();
            }
        }
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function add()
    {
        $list = null;
        $cateInfo = null;
        $cid = $this->request->get('cid');
        if ($cid){
            $cateInfo = db('category')->where('id='.$cid)->find();
            if (empty($cateInfo)){
                $this->error('没有找到相关信息');
            }
        }
        $list = db('category')->where('tid=1')->field('id,name')->select();

        foreach ($list as $k => $v){
            $list[$k]['list2'] = db('category')->where('tid='.$v['id'])->field('id,name')->select();
        }
        $this->assign('cate_info', $cateInfo);
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function save()
    {
         $tid = $this->request->post('tid');
         $cid = $this->request->post('cid');
         if (empty($cid)){
             $checkId = db('category')->where('tid='.$tid.' AND name='.input('post.name'))->value('id');
             if ($checkId){
                 $this->error('该栏目已存在');
             }
         }

         if ($tid > 0 && $tid == input('post.cid')){
             $this->error('所属栏目不能成为自己的上级');
         }
        $photo = null;
        $categoryimg = $this->uploadImage('file2','','category');
        if (!empty($photoD)){
            $data['img'] = $categoryimg;
            if ($cid){
                $data['img'] = db('category')->where('id='.$cid)->value('img');
            }
        }
        $data['name'] = $this->request->post('name');
        $data['concent'] = $this->request->post('concent');
        $data['sort'] = $this->request->post('sort');
        if ($cid){
            $result = db('category')->where('id='.$cid)->update($data);
        }else{
            if ($tid == 0){
                $data['del'] = 0;

                $data['addtime'] = time();
            }
            $result = db('category')->insertGetId($data);
        }

        if ($result){
            $this->success('操作成功', 'index');
        }else{
            $this->error('操作失败');
        }
    }

    public function set_tj()
    {
        $tj_id = $this->request->param('tj_id');
        $tj_update = db('category')->where('id='.$tj_id)->find();

        if (empty($tj_update)){
            $this->error('产品不存在或已下架删除');
        }

        $data = [
            'hot' => $tj_update['hot'] == 1 ? 0 : 1
        ];
        $up = db('category')->where('id='.$tj_id)->update($data);
        if ($up){
            $this->redirect('index');
        }else{
            $this->error('操作失败');
        }
    }
}