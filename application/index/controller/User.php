<?php

namespace app\index\controller;


use app\index\model\userModel;
use think\Controller;

class User extends Controller
{
    public function login()
    {

        return view();
    }

    public function ajax_register()
    {

        $user = new userModel();
        if ($user->validate(true)->save(input('post.'))) {
            return '用户[ ' . $user->username . ':' . $user->id . ' ]新增成功';
        } else {
            return $user->getError();
        }
    }


}
