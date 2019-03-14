<?php
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
}