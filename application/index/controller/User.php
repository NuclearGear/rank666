<?php

namespace app\index\controller;


use app\index\model\FunctionModel;
use app\index\model\UserFunctionModel;
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

        return returnJson($ret_add, 200, '注册成功, 正在登录..');
    }

    public function ajax_login(){
        // 验证 登录 场景
        $validate = validate('UserCheck');
        if (!$validate->scene('login')->check(input('post.'))){
            return returnJson('', 201, $validate->getError());
        }

        // 查看用户是否存在
        $ret_add = UserModel::get(['username' => input('post.username'),]);

        if (!$ret_add){
            return returnJson('', 201, '用户名不存在！');
        }

        if ($ret_add['password'] != md5(input('post.password'))){
            return returnJson('', 201, '密码错误，请重新输入！');
        }

        // 登录
        session('user', $ret_add);

        return returnJson($ret_add, 200, '登录成功！');
    }

    public function login_out(){
        session('user', null);
        $this->redirect(url('User/login'));
    }

    // VIP 页面
    public function vip(){
        $user = UserModel::get(session('user.id'));
        $data['is_test'] = $user['is_test'];
        return view('vip', ['data' => $data]);
    }

    // 立即试用
    public function ajax_test(){
        $user = UserModel::get(session('user.id'));
        if ($user['is_test'] == 1){
            return returnJson('', 203, '你已试用， 每个账号限试用一次！');
        }

        $function = FunctionModel::all();
        $expire_time = time() + 3600 * 24 * 30;
        foreach ($function as $k => $v){
            $ret_user = UserFunctionModel::create([
               'user_id'     => session('user.id'),
               'function_id' => $v['id'],
               'expire_time' => $expire_time,
            ]);
            if (!$ret_user){
                return returnJson($ret_user, 201, '试用失败，请重试！');
            }
        }

        $ret = UserModel::update([
            'is_test' => 1
        ], ['id' => session('user.id')]);
        if (!$ret){
            return returnJson('', 202, '试用失败， 请重试');
        }

        return returnJson('', 200, '试用成功，时间将在 ' . date('Y-m-d H:i:s', $expire_time) . ' 到期！');
    }


}
