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

    // 获取差价数据
    public function diff(){
        $data['where']['title'] = input('get.title');
        $data['where']['sizeStart'] = input('get.sizeStart');
        $data['where']['sizeEnd'] = input('get.sizeEnd');
        $data['where']['diffPrice'] = input('get.diffPrice');
        $data['where']['soldNum'] = input('get.soldNum');

        $query = db('diff');
        if ($data['where']['title']){
            $query->where('title', 'like', '%' . input('get.title') . '%');
        }
        if ($data['where']['sizeStart']){
            $query->where('size', '>=', input('sizeStart'));
        }
        if ($data['where']['sizeEnd']){
            $query->where('size', '<=', input('sizeEnd'));
        }
        if ($data['where']['diffPrice']){
            $query->order('diffPrice ' . input('get.diffPrice'));
        }
        if ($data['where']['soldNum']){
            $query->order('soldNum ' . input('get.soldNum'));
        }

        $data['diff'] = $query->paginate(20);
        var_dump()

        return view('diff', ['data' => $data]);
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
        $data['time'] = [];
        // 构造时间数组
        for($i=1;$i<=input('get.day');$i++){
            $time = Time::yesterdayNum($i);
            array_unshift($data['time'],date('Y-m-d', $time[0]));
        }
        // 获取商品尺码
        $ret_data = db('product_size')->where('productId', '=', $product['productId'])->select();
        if ($ret_data){
            // 构造尺码数组
            foreach ($ret_data as $k => $v){
                $data['sizeName'][] = $v['size'];
                if ($v['size'] && $v['price']){
                    $price_arr = json_decode($v['price'], true);
                    $start = count($price_arr) - input('get.day');
                    $price_arr = array_slice($price_arr, $start, input('get.day'));
                    $new_price_arr = [];
                    foreach ($price_arr as $k2 => $v2){
                        $new_price_arr[] = $v2 / 100;
                    }
                    $data['sizeList'][] = [
                        'name' => $v['size'],
                        'type' => 'line',
                        'data' => $new_price_arr,
                    ];

                }
            }
        }
        if ($data['sizeName']){
            $data['sizeName'] = array_unique($data['sizeName']);
        }

        return returnJson($data, 200, '成功');
    }

    // 尺码转换
    public function sizeChange(){
        return view('sizeChange');
    }

    public function autoSize(){
        for($i = 1;$i<=11;$i++){
            $str[] = rand(50000, 300000);
        }
        echo '[' . implode(',', $str) . ']';
        die;
    }

    // 获取差价商品
    public function moneyGoods(){
        $stockx = db('stockx_product_size')->limit(3000)->order('id desc')->select();

        $findGoods = [];
        foreach ($stockx as $k => $v){
            $ret = db('product')->where(['articleNumber' => $v['styleId']])->find();
            if ($ret){
                $findGoods[] = $ret['articleNumber'];
            }
        }
        dump($findGoods);
    }



}
