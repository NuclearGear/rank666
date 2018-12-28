<?php
namespace app\index\controller;

class Index
{
    public function index(){
        $data = db('product')->limit(50)->order('soldNum')->select();

        return view('sold', ['data' => $data]);
    }

    // 获取销量排名前50
    public function ajaxBySoldNum(){
        $data = db('product')->field('articleNumber,soldNum')->limit(50)->order('soldNum desc')->select();

        return returnJson($data, 200, '成功');
    }

    // 5天内的所有信息
    public function day5(){
        // 获取昨日时间戳
        $time_arr = Time::yesterdayNum(1);

        $data = db('product')->limit(50)->order('soldNum')->select();
        $ids = db('products_sold')->limit(50)->order('soldNum')->select();


        return view('day5', ['data' => $data]);
    }


}
