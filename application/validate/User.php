<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12
 * Time: 15:04
 */
namespace app\common\validate;

use think\Validate;

class User extends Validate
{
    protected $rule = [
        'username'   =>  'require|between:3,20',
        'password'   =>  'require|confirm|between:6,30',
        'repassword' =>  'require|confirm:password'
    ];

    protected $message  =   [
        'name.require'       => '请输入用户名',
        'name.max'           => '用户名必须在 3~20 个字符之间',
        'pass.require'       => '请输入密码',
        'pass.between'       => '密码必须在 6~30 个字符之间',
        'password.confirm'   => '密码与确认密码不一致',
        'repassword.confirm' => '密码与确认密码不一致',
    ];

    protected $scene = [
        'login'     =>  ['username','password'],
        'register'  =>  ['username','password', 'repassword'],
    ];

}