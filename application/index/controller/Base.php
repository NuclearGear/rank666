<?php

namespace app\index\controller;


use app\index\model\FunctionModel;
use app\index\model\UserFunctionModel;
use app\index\model\UserModel;
use think\Controller;

class Base extends Controller
{
    public function _initialize()
    {

        // 判断权限
        $controller = request()->controller();
        $function = request()->action();
        $check_arr = [
            'Buy' => ['index',],
            'Center' => ['index',],
            'Shoes' => ['index', 'diff'],
        ];


        if (isset($check_arr[$controller]) && in_array($function, $check_arr[$controller])) {
            if (!session('?user')) {
                $this->error('您还没有登录，请先去登录', url('User/login'));
            }

            $user_function = UserFunctionModel::get(['user_id' => session('user.id')]);
            if (!$user_function) {
                $this->error('你还未购买该功能，请先去购买', url('user/vip'));
            }

            if ($user_function['expire_time'] < time()) {
                $this->error('对不起。该功能已在 ' . date('Y-m-d H:i:s', $user_function['expire_time']) . ' 过期，请去再次购买!', url('user/vip'));
            }
        }


    }
}