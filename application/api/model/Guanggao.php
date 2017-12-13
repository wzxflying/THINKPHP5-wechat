<?php
/**
 * Created by PhpStorm.
 * User: wzx
 * Date: 2017/12/13
 * Time: 21:44
 */

namespace app\api\model;


use think\Model;

class Guanggao extends Model
{
    public function getAllUserDatas($order,$field,$limit)
    {
        $more_datas = $this->order($order)->field($field)->limit($limit)->select();          // 查询所有用户的所有字段资料
        if (empty($more_datas)) {                 // 判断是否出错
            return false;
        }
        return tp5ModelTransfer($more_datas);   // 返回修改后的数据
    }
}