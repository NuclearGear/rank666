<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12
 * Time: 15:04
 */
namespace app\common\validate;

use think\Validate;

class BuyCheck extends Validate
{
    protected $rule = [
        'number'       => 'require',
        'size'         => 'require',
        'buy_type_id'  => 'require',
        'buy_cost'     => 'require',
        'buy_time'     => 'require',

//        'send_code'    => 'requireWith:send_type_id',
//        'send_type_id' => 'requireWith:send_code',
//        'send_type_id' => 'requireWith:send_cost',
    ];

    protected $message  =   [
        'number.require'       => '请选择 鞋子',
        'size.require'         => '请选择 尺码',
        'buy_type_id.require'  => '请选择 购买平台',
        'buy_cost.require'     => '请输入 成本',
        'buy_time.require'     => '请选择 购买日期',

//        'send_type_id.requireWith' => '请选择 转运平台',
//        'send_code.requireWith'    => '请填写 转运单号、转运费用 或 转运平台选"空"',
//        'send_type_id.requireWith:send_code' => '请先选择 转运平台',
    ];

    protected $scene = [
        'add'     =>  ['number','size','buy_type_id','buy_cost', 'buy_time'],
    ];

}