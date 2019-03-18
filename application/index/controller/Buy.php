<?php

namespace app\index\controller;
// use think\Request;
use app\index\model\UserModel;
use think\Controller;

class Buy extends Controller
{
    public function index()
    {
        return view();
    }

    public function ajax_add(){
    	var_dump(input('post.'));exit;
     	if (!input('post.name')){
            return returnJson('', 201, '请填写 货号、尺码、成本、购买日期！');
        }
    	// var_dump(input('post.'));exit;
        return returnJson('', 200, '添加成功，请刷新页面后查看');
    }


}
