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
        $data['where']['title']         = input('get.title');
        $data['where']['articleNumber'] = input('get.articleNumber');
        $data['where']['sizeStart']     = input('get.sizeStart');
        $data['where']['sizeEnd']       = input('get.sizeEnd');
        $data['where']['diffPrice']     = input('get.diffPrice');
        $data['where']['soldNum']       = input('get.soldNum');
        $data['where']['ceil']          = input('get.ceil');

        $query = db('diff');
        if ($data['where']['title']){
            $query->where('title', 'like', '%' . input('get.title') . '%');
        }
        if ($data['where']['articleNumber']){
            $query->where('articleNumber', 'like', '%' . input('get.articleNumber') . '%');
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
        if ($data['where']['ceil']){
            $query->order('ceil ' . input('get.ceil'));
        }


        $data['diff'] = $query->paginate(20,false,['query' => $data['where']]);

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
        $data = [];
        // 获取商品信息
        $product = db('product')->whereOr(['articleNumber' => input('get.articleNumber')])->whereOr('title', 'like', '%' . input('get.articleNumber') . '%')->find();
        if (!$product){
            return returnJson('', 202, '商品不存在！');
        }
        $data['name'] = $product['title'];
        $data['image'] = $product['logoUrl'];
        $data['articleNumber'] = $product['articleNumber'];


        $size = db('product_size')->where(['articleNumber' => $product['articleNumber']])->order('spiderTime desc')->select();

        $data['sizeName'] = [];
        $data['time'] = [];
        // 构造时间数组
        foreach ($size as $k => $v){
            array_unshift($data['time'],date('Y-m-d', $v['spiderTime']));
        }
        $data['time'] = array_values(array_unique($data['time']));
        /** 构造尺码数组 */
        $data['size'] = [];
        if ($size){
            foreach ($size as $k => $v){
                $data['size'][$v['size']][] = $v['price'] / 100;
            }
            foreach ($data['size'] as $k => $v){
                $data['sizeName'][] = strval($k);
                $data['sizeList'][] = [
                    'name' => $k,
                    'type' => 'line',
                    'data' => $v,
                    'markPoint' => [
                        'data' => [
                            ['type' => 'max', 'name' => '最大值'],
                            ['type' => 'min', 'name' => '最小值'],
                        ],
                    ],
                    'markLine' => [
                        'data' => [
                            ['type' => 'average', 'name' => '平均值'],
                        ],
                    ]
                ];
            }
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
