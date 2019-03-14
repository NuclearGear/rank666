<?php

namespace app\index\controller;


use app\index\model\UserModel;
use think\Controller;

class Buy extends Controller
{
    public function index()
    {
        return view();
    }

    public function ajax_add(){
        return returnJson('', 200, '添加成功，请刷新页面后查看');
    }


}
