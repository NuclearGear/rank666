<?php
namespace app\index\controller;

use think\Cache;

class Index
{
    public function index(){

        return view('search');
    }

    // 获取销量排名前50
    public function ajaxBySoldNum(){
        $data = db('du_product')->field('articleNumber,soldNum')->limit(50)->order('soldNum desc')->select();

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
            $query->whereOr('duTitle', 'like', input('get.title'));
            $query->whereOr('stockxTitle', 'like', input('get.title'));
        }
        if ($data['where']['articleNumber']){
            $query->where('articleNumber', 'like', input('get.articleNumber'));
        }
        if ($data['where']['sizeStart'] || $data['where']['sizeEnd']){
            $query->whereBetween('size', [input('sizeStart'), input('sizeEnd')]);
        }
        if ($data['where']['diffPrice']){
            $query->order('diffPrice',input('get.diffPrice'));
        }
        if ($data['where']['soldNum']){
            $query->order('duSoldNum',input('get.soldNum'));
        }
        if ($data['where']['ceil']){
            $query->order('ceil',input('get.ceil'));
        }

        $data['diff'] = $query->paginate(20,false,['query' => $data['where']]);
        return view('diff', ['data' => $data]);
    }

    public function menu(){
        return view('sold');
    }

    // 单品统计
    public function one(){

        return view('one');
    }

    // 单品统计
    public function search(){
        return view('search');
    }

    // 单品统计
    public function AjaxProductOne(){
        if (!input('?get.articleNumber') || !input('get.articleNumber')){
            return returnJson('', 201, '请填写 货号 或 名称！');
        }

        $cacheKey = input('get.articleNumber');
        $data = cache::get($cacheKey);
        if ($data){
            return returnJson($data, 200, '成功');
        }
//        $_GET['articleNumber'] = 'CD0461-401';
        $data = [];
        // 获取商品信息
        $product = db('du_product')->whereOr('title', 'like', input('get.articleNumber'))->whereOr('articleNumber', input('get.articleNumber'))->find();
        if (!$product){
            return returnJson('', 202, '商品不存在！');
        }

        /** 获取 商品信息 */
        $data['product'] = [
            'title'         => $product['title'],
            'image'         => $product['logoUrl'],
            'articleNumber' => $product['articleNumber'],
            'soldNum'       => $product['soldNum'],
            'sellDate'      => $product['sellDate'],
        ];


        /** 获取 尺码销量 */
        $sold_arr = db('du_sold')->where('articleNumber', 'like', $product['articleNumber'])->order('size', 'asc')->select();
        $data['soldList'] = [];
        foreach ($sold_arr as $k => $v){
            $data['soldList'][] = [
                'size'    => $v['size'],
                'soldNum' => $v['soldNum']
            ];
        }


        /** 获取 尺码销量折线图 */
        $data_sold = db('du_sold_record')->where(['articleNumber' => $product['articleNumber']])->order('spiderTime', 'asc')->select();

        // 处理尺码销量数组
        $temp_sold = [];
        foreach ($data_sold as $k => $v){
            $temp_sold[strval($v['spiderTime'])][strval($v['size'])] = $v['soldNum'];
        }

        $temp_save = [];
        // 处理 若第一天 与 第二天销量相同导致没有数据的情况
        foreach ($temp_sold as $k => $v) {
            foreach ($data['soldList'] as $k2 => $v2) {
                if (isset($v[$v2['size']])) {
                    $temp_save[strval($v2['size'])] = $v[strval($v2['size'])];
                } else {
                    $temp_sold[$k][strval($v2['size'])] = $temp_save[strval($v2['size'])];
                }
            }
        }
        $data['soldSizeName'] = [];
        $data['soldTime'] = [];
        // 构造时间数组
        foreach ($data_sold as $k => $v){
            $data['soldTime'][] = date('Y-m-d', $v['spiderTime']);
        }
        $data['soldTime'] = array_unique($data['soldTime']);
        sort($data['soldTime']);

        // 构造销量数组
        $data['soldSize'] = [];
        if ($temp_sold){
            foreach ($temp_sold as $k => $v){
                foreach ($v as $k2 => $v2){
                    $data['soldSize'][$k2][] = $v2;
                }
                ksort($data['soldSize']);
            }
            foreach ($data['soldSize'] as $k => $v){
                // 默认选中
                if (floatval($k) >= 40 && floatval($k) <= 43){
                    $data['soldSelected'][$k] = True;
                }else{
                    $data['soldSelected'][$k] = False;
                }
                $data['soldSizeName'][] = strval($k);
                $data['soldSizeList'][] = [
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

        /** 获取 尺码价格折线图 */
        $size = db('du_size')->where(['articleNumber' => $product['articleNumber']])->order('size desc')->select();
        $data['sizeName'] = [];
        $data['time'] = [];
        // 构造时间数组
        foreach ($size as $k => $v){
            array_push($data['time'],date('Y-m-d', $v['spiderTime']));
        }
        $data['time'] = array_values(array_unique($data['time']));
        /** 构造尺码数组 */
        $data['size'] = [];
        if ($size){
            foreach ($size as $k => $v){
                $data['size'][$v['size']][] = $v['price'] / 100;
            }
            foreach ($data['size'] as $k => $v){
                // 默认选中
                if (floatval($k) >= 40 && floatval($k) <= 43){
                    $data['sizeSelected'][$k] = True;
                }else{
                    $data['sizeSelected'][$k] = False;
                }
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

        cache::set($cacheKey, $data,3600 * 2);

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
        $stockx = db('stockx_size')->limit(3000)->order('id desc')->select();

        $findGoods = [];
        foreach ($stockx as $k => $v){
            $ret = db('du_product')->where(['articleNumber' => $v['styleId']])->find();
            if ($ret){
                $findGoods[] = $ret['articleNumber'];
            }
        }
        dump($findGoods);
    }



}
