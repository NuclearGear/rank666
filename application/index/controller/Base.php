<?php

namespace app\index\controller;


use app\index\model\UserFunctionModel;
use app\index\model\UserModel;
use think\Controller;

class Base extends Controller
{
    public function _initialize()
    {
        if (!session('?user')){
            $this->error('您还没有登录，请先去登录', url('User/login'));
        }

        $auth = UserFunctionModel::get(session('user.id'));
        if (!$auth){
            echo "<script language=\"JavaScript\">alert(\"请先去购买功能！\");</script>";
//            $this->redirect(url('center/vip'));
        }
//        dump($auth);
    }


}
