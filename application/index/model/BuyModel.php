<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12
 * Time: 15:04
 */
namespace app\index\model;

use think\Model;
use traits\model\SoftDelete;

class BuyModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'lt_buy';
    use SoftDelete;
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = 'delete_time';

    // [获取器] 购买平台
    public function getBuyTypeIdAttr($value)
    {
        $status = array_column(config('buy_type'), 'name', 'id');
        return $status[$value];
    }

    // [获取器] 转运平台
    public function getSendTypeIdAttr($value)
    {
        $status = array_column(config('send_type'), 'name', 'id');
        return $status[$value];
    }

    // [获取器] 出售平台
    public function getSoldTypeIdAttr($value)
    {
        $status = array_column(config('sold_type'), 'name', 'id');
        return $status[$value];
    }

    // [获取器] 购买时间
    public function getBuyTimeAttr($value)
    {
        if ($value){
            $value = date('Y-m-d', $value);
        }else{
            $value = '-';
        }
        return $value;
    }

    // [获取器] 出售时间
    public function getSoldTimeAttr($value)
    {
        if ($value){
            $value = date('Y-m-d', $value);
        }else{
            $value = '-';
        }
        return $value;
    }


}