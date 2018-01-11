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
    /**
     * 产品管理
     * @return mixed
     * @throws \think\exception\DbException
     */
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

    /**
     * 添加产品
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add()
    {
        if ($this->request->isPost()){
            $array = [
                'name' => $this->request->post('name'),
                'intro' => $this->request->post('intro'),
                'shop_id' => $this->request->post('shop_id'),
                'cid' => $this->request->post('cid'),
                'brand_id' => $this->request->post('brand_id'),
                'pro_number' => $this->request->post('pro_number'),
                'sort' => $this->request->post('sort'),
                'price' => $this->request->post('price'),
                'price_yh' => $this->request->post('price_yh'),
                'price_jf' => $this->request->post('price_jf'),
                'addtime' => time(),
                'updatetime' => time(),
                'num' => $this->request->post('num'),
                'content' => $this->request->post('content'),
                'company' => $this->request->post('company'),
                'pro_type' => 1,
                'renqi' => $this->request->post('renqi'),
                'is_hot' => $this->request->post('is_hot'),
                'is_show' => $this->request->post('is_show'),
                'is_sale' => $this->request->post('is_sale'),

            ];

            $photoX = $this->uploadImage('photo_x','','product');
            if (!empty($photoX)){
                $array['photo_x'] = $photoX;
            }

            $photoD = $this->uploadImage('photo_d','','product');
            if (!empty($photoD)){
                $array['photo_x'] = $photoD;
            }
            $photoString = $this->uploadImages('files','','product');
            if (!empty($photoString)){
                $array['photo_string'] = $photoString;
            }
            $result = db('product')->insertGetId($array);
            if ($result){
                $this->success('保存成功');
            }else{
                $this->error('保存失败');
            }
        }
        $id = $this->request->get('id');
        $proAllInfo = null;
        $img_str =null;
        $catetwo = null;
        if (!empty($id)){
            $proAllInfo = db('product')->where('id='.$id)->find();
            $tid = db('category')->where('id='.$proAllInfo['cid'])->value('tid');
            $proAllInfo['tid'] = $tid;
            $catetwo = db('category')->where('tid='.$tid)->field('id,name')->select();
        }
        $cateList = db('category')->where('tid=1')->field('id,name')->select();

        $brandList = db('brand')->field('id,name')->select();
        $this->assign('cate_list', $cateList);
        $this->assign('catetwo',$catetwo);
        $this->assign('img_str',$img_str);
        $this->assign('brand_list',$brandList);
        $this->assign('pro_allinfo', $proAllInfo);
        return $this->fetch();
    }

    /**
     * 获取产品二级分类
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getcid(){
        $cateid = $this->request->param('cateid');
        $catelist = db('category')->where('tid='.$cateid)->field('id,name')->select();
        echo json_encode(array('catelist' => $catelist));
    }

    public function imgDel()
    {
        $imgUrl = $this->request->param('img_url');
        $proId = $this->request->param('pro_id');
        $checkInfo = db('product')->where('id='.$proId.' AND del=0')->find();
        if (empty($checkInfo)){
            return json_encode(array('status' => 0, 'err' => '产品不存在或已下架删除'));
        }

        $arr = explode(',', trim($checkInfo['photo_string']), ',');
        if (in_array($imgUrl, $arr)){
            foreach ($arr as $k => $v){
                if ($imgUrl === $v){
                    unset($arr[$k]);
                }
            }
            $data = [
                'photo_string' => implode(',', $arr)
            ];
            $result = db('product')->where('id='.$proId)->update($data);

            if (empty($result)){
                return json_encode(array('status' => 0, 'err' => '操作失败'));
            }

            $url = 'Data/'.$imgUrl;
            if (file_exists($url)){
                @unlink($url);
            }

            return json_encode(array('status' =>1));
        }else{
            return json_encode(array('status' => 0, 'err' => '操作失败'));
        }
    }

    public function set_tj()
    {
        $proId = $this->request->param('pro_id');
        $tj_update = db('product')->field('shop_id,type')->where('id='.$proId.' AND del=0')->find();

        if (empty($tj_update)){
            $this->error('产品不存在或已下架删除');
        }

        $data = [
            'type' => $tj_update['type'] == 1 ? 0 : 1
        ];
        $up = db('product')->where('id='.$proId)->update($data);
        if ($up){
            $this->redirect('index');
        }else{
            $this->error('操作失败');
        }
    }

    public function set_new()
    {
        $proId = $this->request->param('pro_id');
        $tj_update = db('product')->field('shop_id,type')->where('id='.$proId.' AND del=0 AND is_down=0')->find();

        if (empty($tj_update)){
            return json_encode(array('status'=>0));
        }

        $data = [
            'is_show' => $tj_update['is_show'] == 1 ? 0 : 1
        ];
        $up = db('product')->where('id='.$proId)->update($data);
        if ($up){
            return json_encode(array('status'=>1));
        }else{
            return json_encode(array('status'=>0));
        }
    }

    public function set_zk()
    {
        $proId = $this->request->param('pro_id');
        $tj_update = db('product')->field('shop_id,type')->where('id='.$proId.' AND del=0 AND is_down=0')->find();

        if (empty($tj_update)){
            return json_encode(array('status'=>0));
        }

        $data = [
            'is_sale' => $tj_update['is_sale'] == 1 ? 0 : 1
        ];
        $up = db('product')->where('id='.$proId)->update($data);
        if ($up){
            return json_encode(array('status'=>1));
        }else{
            return json_encode(array('status'=>0));
        }
    }

    public function del()
    {
        $id = $this->request->param('id');
        $info = db('product')->where('id='.$id)->find();
        if (!$info) {
            $this->error('产品信息错误.');
        }

        if (intval($info['del'])==1) {
            $this->success('操作成功！.');
        }

        $data=array();
        $data['del'] = $info['del'] == '1' ?  0 : 1;
        $data['del_time'] = time();
        $up = db('product')->where('id='.$id)->update($data);
        if ($up) {
            $this->redirect('index');
        }else{
            $this->error('操作失败.');
        }
    }
}