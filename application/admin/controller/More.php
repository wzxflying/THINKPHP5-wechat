<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2018/1/9
 * Time: 13:46
 */

namespace app\admin\controller;


class More extends Admin
{
    /**
     * 单页设置
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pwebGl()
    {
        $list = db('web')->select();
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 单页设置
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function pweb()
    {
        if ($this->request->isPost()){
            if ($this->request->post('id')){
                $data = [
                    'concent' => $this->request->post('concent'),
                    'sort' => $this->request->post('sort'),
                    'addtime' => time()
                ];
                $up = db('web')->where('id='.input('post.id'))->update($data);
                if ($up){
                    $this->success('保存成功！');
                }else{
                    $this->error('操作失败！');
                }
            }else{
                $this->error('系统错误！');
            }
        }else{
            $this->assign('datas', db('web')->find());
            return $this->fetch();
        }
    }

    public function setup()
    {
        if ($this->request->isPost()){
            $data = [
                'title' => input('post.title'),
                'name' => input('post.name'),
                'describe' => input('post.describe'),
                'service_wx' => input('post.service_wx'),
                'tel' => input('post.tel'),
                'email' => input('post.email'),
                'copyright' => input('post.copyright'),
                'uptime' => time()
            ];
            $imgPath = $this->uploadImage('file','jpg,png,gif','logo');
            if (!empty($imgPath)){
                $data['logo'] = $imgPath;
            }
            $result = db('program')->where('id=1')->update($data);
            if ($result){
                $this->success('保存成功');
            }else{
                $this->error('操作失败');
            }
        }else{
            $this->assign('info', db('program')->where('id=1')->find());
            return $this->fetch();
        }
    }
}