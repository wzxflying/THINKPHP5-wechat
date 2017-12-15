<?php
/**
 * Created by PhpStorm.
 * User: wlb-71
 * Date: 2017/12/14
 * Time: 9:17
 */

namespace app\common\model;


use think\Model;

class Common extends Model
{
    public function getAllDatas($where,$order,$field,$limit)
    {
        $more_datas = $this->where($where)->order($order)->field($field)->limit($limit)->select();             // 查询所有用户的所有字段资料
        if (empty($more_datas)) { // 判断是否出错
            return false;
        }
        return $more_datas;   // 返回修改后的数据
    }
}