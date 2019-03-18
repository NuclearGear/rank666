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
            'password' => md5(input('post.password')),
            'phone'    => input('post.phone'),
        ]);
        if (!$ret_add){
            return returnJson('', 201, '注册失败！');
        }

        // 登录
        session('user', $ret_add);

        return returnJson($ret_add, 200, '注册成功！');
    }

    public function ajax_login(){
        // 验证 登录 场景
        $validate = validate('UserCheck');
        if (!$validate->scene('login')->check(input('post.'))){
            return returnJson('', 201, $validate->getError());
        }

        // 查看用户是否存在
        $ret_add = UserModel::get([
            'username' => input('post.username'),
            'password' => md5(input('post.password')),
        ]);

        if (!$ret_add){
            return returnJson('', 201, '用户名或密码错误！');
        }

        // 登录
        session('user', $ret_add);

        return returnJson($ret_add, 200, '登录成功！');
    }

    public function login_out(){
        session('user', null);
        $this->redirect(url('User/login'));
    }


}
