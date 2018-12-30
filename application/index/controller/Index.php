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

    // 单品统计
    public function one(){
        return view('one');

    }

    // 单品统计
    public function AjaxProductOne(){
        if (!input('?get.articleNumber')){
            return returnJson('', 201, '请传商品Id！');
        }
        if (!input('?get.day')){
            return returnJson('', 201, '请传天数！');
        }
        $data = [];

        $product = db('product')->where(['articleNumber' => input('get.articleNumber')])->find();

        $data['name'] = $product['title'];
        $data['sizeName'] = [];
        for($i=1;$i<=input('get.day');$i++){
            $time = Time::yesterdayNum($i);

            $ret_data = db('product_size')->where('spiderTime', 'between', $time)
                                                ->where('productId', '=', $product['productId'])
                                                ->select();
            if ($ret_data){
                $data['time'][] = date('Y-m-d', $time[0]);
                foreach ($ret_data as $k => $v){
                    $data['sizeName'][] = $v['size'];
                    if ($v['size'] && $v['price']){
                        $data[$v['size']][] = $v['price'] / 100;
                    }
                }
            }
        }
        if ($data['sizeName']){
            $data['sizeName'] = array_unique($data['sizeName']);
        }






        return returnJson($data, 200, '成功');
    }


}
