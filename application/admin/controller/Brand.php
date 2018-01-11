<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2018/1/11
 * Time: 9:07
 */

namespace app\admin\controller;


class Brand extends Admin
{
    /**
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $brandName = $this->request->param('brand_name');
        $condition = null;
        if (!empty($brandName)){
            $condition['name'] = array('LIKE', '%' .$brandName. '%');
        }

        $list = db('brand')->where($condition)->paginate(5);

        $this->assign('name', $brandName);
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        return $this->fetch();
    }

    public function add()
    {
        $id = $this->request->get('id');
        $brandInfo = null;
        if ($id){
            $brandInfo = db('brand')->where('id='.$id)->find();
        }
        $this->assign('brand_info', $brandInfo);
        return $this->fetch();
    }

    public function save()
    {
        $photo = null;

        $id = $this->request->post('id');
        $brandimg = $this->uploadImage('file','','brand');
        if (!empty($photoD)){
            $array['photo'] = $brandimg;
        }
        $array['name'] = $this->request->post('name');
        if ($id){
            $result = db('brand')->where('id='.$id)->update($array);
        }else{
            $array['addtime'] = time();
            $result = db('brand')->insertGetId($array);
        }

        if ($result){
            if (!empty($photo) && $id) {
                $img_url = "Data/".$photo;
                if(file_exists($img_url)) {
                    @unlink($img_url);
                }
            }
            $this->success('操作成功', 'index');
        }else{
            $this->error('操作失败', 'index');
        }
    }

    public function del()
    {
        $id = $this->request->param('id');
        $checkInfo = db('brand')->where('id='. $id)->find();
        if (empty($checkInfo)){
            $this->error('参数错误');
        }

        $up = db('brand')->where('id='. $id)->delete();
        if ($up){
            if ($checkInfo['photo']){
                $img_url = "Data/".$checkInfo['photo'];
                if(file_exists($img_url)) {
                    @unlink($img_url);
                }
            }
            $this->success('操作成功', 'index');
        }else{
            $this->error('操作失败', 'index');
        }
    }
}