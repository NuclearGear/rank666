<?php
namespace app\index\controller;

class Index
{
    public function index(){
        $data = db('product')->limit(50)->order('soldNum')->select();


        return view('index', ['data' => $data]);
    }

    public function ajaxBySoldNum(){
        $data = db('product')->field('title,soldNum')->limit(50)->order('soldNum desc')->select();

        return returnJson($data, 200, '成功');
    }
}
