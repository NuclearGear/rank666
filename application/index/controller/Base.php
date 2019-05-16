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
        $user_function = UserFunctionModel::get(['user_id' => session('user.id')]);

        if ($user_function['expire_time'] < time()){
            $this->error('对不起。该功能已在 ' . date('Y-m-d H:i:s', $user_function['expire_time']) . ' 过期，请去再次购买!', url('user/vip'));
        }
    }


}
