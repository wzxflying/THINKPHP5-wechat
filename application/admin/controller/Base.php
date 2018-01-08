<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2018/1/8
 * Time: 14:01
 */

namespace app\admin\controller;


use think\Controller;
use think\Request;
use think\Session;

class Base extends Controller
{
    public $error_message = '服务器繁忙！';

    /**
     * Base constructor.
     * @param Request|null $request
     */
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        if (empty(Session::has('admininfo'))){
            $this->error('请先登录再进行操作', 'Login/index');
        }
    }

    /**
     * 地址枚举
     * @param int $id
     * @param int $tid
     * @param int $f
     * @return bool|null|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cityOption($id = 0, $tid = 0, $f = 0)
    {
        if ($id == 0 && $tid == 0 && $f == 0){
            return false;
        }
        $priv = db('ChinaCity')->where('id='.$id)->field('tid')->find();

        $tid == 0 ? $tid = $priv['tid'] : $tid;

        $city = db('ChinaCity')->where('tid='.$tid)->field('id,name')->select();
        $text = null;
        foreach ($city as $k => $v){
            if ($v){
                $id == $v['id'] ? $select = 'selected="selected"' : $select = '';
                $text .= '<option value="'.$v['id'].'" '.$select.'>--'.$v['name'].'</option>';
            }
        }
        return $text;
    }

    /**
     * 地址ajax
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function chinaCity()
    {
        $id = input('get.id');
        $city = db('ChinaCity')->where('tid='.$id)->field('id,name')->select();
        foreach ($city as $k => $v){
            $city[$k]['name'] = urlencode($v['name']);
        }
        return urldecode(json_encode($city));
    }

    /**
     * 推荐分类的递归查询
     * @param int $id
     * @param int $lv
     * @return null|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function productOption($id = 0, $lv = 0)
    {
        $sql = db('category')->where('tid='.$id.' AND id!='.$id.'')->field('id,name,hot')->select();
        $hot = null;
        foreach ($sql as $k => $v){
            if($hot == null && $lv == 0){
                $hot .= 'id='.$v['id'];
            }else{
                $hot .= ' OR id='.$v['id'];
            }
            if (db('category')->where('tid='.$v['id'])->select() > 0){
                $hot .= $this->productOption($v['id'], $lv+1);
            }
        }
        return $hot;
    }

    /**
     * 图片上传的公共方法
     * @param $file 文件数据流
     * @param $exts 文件类型
     * @param $path 子目录名称
     */
    public function uploadImage($file, $exts = 'jpg,png,gif', $path = null)
    {
        if (empty($path)){
            return '请输入路径';
        }
        $info = $file->validate(['size' => '2097152', 'ext' => $exts])->move(ROOT_PATH . 'public' . DS . 'Data' . DS . 'UploadFiles' . DS . $path);
        if ($info){
            // 成功上传后 获取上传信息
            // 输出 jpg
            return $info->getExtension();
            // 输出 42a79759f284b767dfcb2a0197904287.jpg
            return $info->getFilename();
        }else{
            return $file->getError();
        }
    }

}