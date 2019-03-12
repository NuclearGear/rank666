<?php

namespace app\index\controller;


use app\index\model\UserModel;
use think\Controller;

class User extends Controller
{
    public function login()
    {

        return view();
    }

    public function ajax_register()
    {
        // 验证 注册 场景
        $validate = validate('UserCheck');
        if (!$validate->scene('register')->check(input('post.'))){
            return returnJson('', 201, $validate->getError());
        }

        // 添加注册用户
        $ret_add = UserModel::create([
            'username' => input('post.username'),
            'password' => input('post.password'),
            'phone'    => input('post.phone'),
        ]);
        if (!$ret_add){
            return returnJson('', 201, '注册失败！');
        }

        return returnJson('', 200, '注册成功！');
    }


}
