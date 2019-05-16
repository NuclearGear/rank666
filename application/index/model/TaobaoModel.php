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

class TaobaoModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'lt_taobao';
    use SoftDelete;
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = 'delete_time';

    // [获取器] 价格
    public function getPriceAttr($value)
    {
        if ($value < 0){
            $value = '<p style="color: darkgreen">' . $value . '</p>';
        }else{
            $value = '<p style="color: darkred">+' . $value . '</p>';
        }

        return $value;
    }

    // [获取器] 尺码列表
    public function getSizeListAttr($value)
    {
        $size_list = explode(',', $value);
        $size_list = json_encode($size_list);
        return $size_list;
    }

}