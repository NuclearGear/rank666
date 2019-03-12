<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12
 * Time: 15:04
 */
namespace app\common\validate;

use think\Validate;

class userCheck extends Validate
{
    protected $rule = [
        'username'   =>  'require|length:3,20|unique:UserModel',
        'password'   =>  'require|length:6,30',
        'repassword' =>  'require|confirm:password'
    ];

    protected $message  =   [
        'username.unique'    => '用户名已存在',
        'username.require'   => '请输入用户名',
        'username.between'   => '用户名必须在 3~20 个字符之间',

        'password.require'   => '请输入密码',
        'password.length'    => '密码必须在 6~30 个字符之间',


        'repassword.require' => '请输入确认密码',
        'repassword.confirm' => '密码与确认密码不一致',
    ];

    protected $scene = [
        'login'     =>  ['username' => 'require','password' => 'require'],
        'register'  =>  ['username','password', 'repassword'],
    ];

}