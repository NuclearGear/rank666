<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12
 * Time: 15:04
 */
namespace app\common\validate;

use think\Validate;

class TaobaoCheck extends Validate
{
    protected $rule = [
        'article_number' => 'require',
        'taobao_id'      => 'require|unique:TaobaoModel',
        'price'          => 'require',
    ];

    protected $message  =   [
        'article_number.require' => '请选择 对应鞋款',
        'taobao_id.require'      => '请填写 淘宝ID',
        'taobao_id.unique'       => '淘宝ID已存在',
        'price.require'          => '请填写 溢价价格',
    ];

    protected $scene = [
        'add'     =>  ['article_number','taobao_id','price'],
    ];

}