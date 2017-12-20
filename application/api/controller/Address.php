<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2017/12/20
 * Time: 8:52
 */

namespace app\api\controller;


class Address extends Base
{
    public function index()
    {
        $userId = input('user_id');
        if (empty($userId)){
            return json(array('status'=>0,'err'=>'网络异常.'.__LINE__));
        }
        //所有地址
        $address = db('address');
        $addsList = $address->where('uid='.$userId)->order('is_default desc,id desc')->select();
        return json(array('status'=>1,'adds'=>$addsList));
    }

    /**
     * 会员添加地址接口
     */
    public function addAdds()
    {
        $userId = input('user_id');
        if (empty($userId)){
            return json(array('status'=>0,'err'=>'网络异常.'.__LINE__));
        }
        //接收ajax传过来的数据
        //data:{user_id:uid,receiver:rec,tel:tel,sheng:sheng,city:city,quyu:quyu,adds:address,code:code}
        $data = [
            'name'    => trim(input('post.receiver')),
            'tel'     => trim(input('post.tel')),
            'sheng'   => input('post.sheng'),
            'city'    => input('post.city'),
            'quyu'    => input('post.quyu'),
            'address' => input('post.adds'),
            'code'    => input('post.code'),
            'uid'     => $userId
        ];
        if (!$data['name'] || !$data['tel'] || !$data['address']){
            return json(array('status'=>0,'err'=>'请先完善信息后再提交.'));
        }
        if (!$data['sheng'] || !$data['city'] || !$data['quyu']){
            return json(array('status'=>0,'err'=>'请选择省市区.'));
        }
        $checkId = db('address')->where($data)->value('id');
        if ($checkId){
            return json(array('status'=>0,'err'=>'该地址已经添加了.'));
        }
        $province = db('china_city')->where('id='.$data['sheng'])->value('name');
        $cityName = db('china_city')->where('id='.$data['city'])->value('name');
        $quyuName = db('china_city')->where('id='.$data['quyu'])->value('name');
        $data['address_xq'] = $province.' '.$cityName.' '.$quyuName.' '.$data['address'];
        $result = db('address')->insert($data);
        if ($result){
            $arr = [
                'addr_id' => $result,
                'rec'     => $data['name'],
                'tel'     => $data['tel'],
                'addr_xq' => $data['address_xq']
            ];
            return json(array('status'=>1,'add_arr'=>$arr));
        }else{
            return json(array('status'=>0,'err'=>'操作失败.'));
        }
    }
    /**
     * 会员获取单个地址接口
     */
    public function details()
    {
        $addrId = input('addr_id');
        if (empty($addrId)){
            return json(array('status'=>0, 'err'=>'无地址'));
        }
        $address = db('address')->where('id='.$addrId)->find();
        if (empty($address)){
            return json(array('status'=>0, 'err'=>'无地址'));
        }
        $arr = [
            'status'  => 1,
            'addr_id' => $address['id'],
            'name'    => $address['name'],
            'tel'     => $address['address_xq']
        ];
        return json($arr);
    }
    /**
     * 会员删除地址接口
     */
    public function delete()
    {
        $userId = input('user_id');
        if (empty($userId)){
            return json(array('status'=>0,'err'=>'网络异常.'.__LINE__));
        }
        $idArr = trim(input('post.id_arr'), ',');
        if ($idArr){
            $result = db('address')->where('uid='.$userId.' AND id IN('.$idArr.')')->delete();
            if ($result){
                return json(array('status'=>1));
            }else{
                return json(array('status'=>0,'err'=>'操作失败.'));
            }
        }else{
            return json(array('status'=>0,'err'=>'没有找到要删除的数据.'));
        }
    }
    /**
     * 获取省份数据接口
     */
    public function getProvince()
    {
        $chinaCity = db('china_city');
        $list = $chinaCity->where('tid=0')->field('id,name')->select();
        return json(array('status'=>1,'list'=>$list));
    }
    /**
     * 获取城市数据接口
     */
    public function getCity()
    {
        $sheng = input('sheng');
        if (empty($sheng)){
            return json(array('status'=>0,'err'=>'请选择省份.'.__LINE__));
        }
        $chinaCity = db('china_city');
        $list = $chinaCity->where('tid=0')->field('id,name')->select();
        $city = $chinaCity->where('tid='.$list[$sheng-1]['id'])->field('id,name')->select();
        return json(array('status'=>1, 'city_list'=> $city, 'sheng'=>$list[$sheng-1]['id']));
    }
    /**
     * 获取区域数据接口
     */
    public function getArea()
    {
        $city = input('city');
        if (empty($city)){
            return json(array('status'=>0,'err'=>'请选择城市.'.__LINE__));
        }
        $chinaCity = db('china_city');
        $list = $chinaCity->where('tid='.input('sheng'))->field('id,name')->select();
        $area = $chinaCity->where('tid='.$list[$city-1]['id'])->field('id,name')->select();
        return json(array('status'=>1,'area_list'=>$area,'city'=>$list[$city-1]['id']));
    }
    /**
     * 获取邮政编号接口
     */
    public function getCode()
    {
        $quyu = input('quyu');
        $chinaCity = db('china_city');
        $list = $chinaCity->where('tid='.input('city'))->field('id,name')->select();
        $code = $chinaCity->where('id='.$list[$quyu-1]['id'])->value('code');
        return json(array('status'=>1,'code'=>$code,'area'=>$list[$quyu-1]['id']));
    }
    /**
     * 设置默认地址
     */
    public function setDefault()
    {
        $uid = input('uid');
        if (empty($uid)){
            return json(array('status'=>0,'err'=>'登录状态异常.'));
        }
        $addrId = input('addr_id');
        if (empty($addrId)){
            return json(array('status'=>0,'err'=>'地址信息错误.'));
        }
        $check = db('address')->where('uid='.$uid. ' AND is_default=1')->find();
        if ($check){
            $up = db('address')->where('uid='.$uid)->update(array('is_default'=>0));
            if (empty($up)){
                return json(array('status'=>0,'err'=>'设置失败.'.__LINE__));
            }
            return json(array('status'=>1));
        }

        $up2 = db('address')->where('id='.$addrId.' AND uid='.$uid)->update(array('is_default'=>1));
        if ($up2){
            return json(array('status'=>1));
        }else{
            return json(array('status'=>0,'err'=>'设置失败.'.__LINE__));
        }
    }
}