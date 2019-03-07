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
        $data['where']['sellDate']      = input('get.sellDate');


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
        if ($data['where']['sellDate']){
            $query->order('sellDate',input('get.sellDate'));
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
                    $temp_sold[$k][strval($v2['size'])] = $v2['soldNum'];
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

    public function money(){
        $cacheKey = 'index_money';
        $data = cache::get($cacheKey);
        if ($data){
            return view('money', ['data' => $data]);
        }

        $data['where']['title']         = input('get.title');
        $data['where']['articleNumber'] = input('get.articleNumber');
        $query = db('money');

        if ($data['where']['title']){
            $query->where('Title', 'like', input('get.title'));
        }
        if ($data['where']['articleNumber']){
            $query->where('articleNumber', 'like', input('get.articleNumber'));
        }

        $data['list'] = $query->paginate(100,false,['query' => $data['where']]);
        $data['list_arr'] = $data['list']->toArray()['data'];

        $data['total_cost'] = 0;
        $data['total_profit'] = 0;
        $data['total_num'] = 0;
        $data['total_ceil'] = 0;

        if (!$data['list_arr']){
            return view('money', ['data' => $data]);
        }

        foreach ($data['list_arr'] as $k => $v){
            // 获取商品价格
            $size = db('du_size')->where([
                'articleNumber' => $v['articleNumber'],
                'size'          => $v['size'],
            ])->field('price')->order('spiderTime', 'desc')->find();
            if (!$size){
                $add_data['price']  = '';
                $add_data['charge'] = '';
                $add_data['profit'] = '';
                $add_data['ceil']   = '';
            }

            $price  = $size['price'] / 100;
            $charge = round(($price * 0.095), 2);
            if ($charge > 299){
                $charge = 299;
            }
            $profit = round(($price - $v['cost'] - $charge - 100), 2);
            $ceil   = round(($profit / $v['cost']) * 100, 2);

            $data['list_arr'][$k]['price']  = $price;
            $data['list_arr'][$k]['charge'] = $charge;
            $data['list_arr'][$k]['profit'] = $profit;
            $data['list_arr'][$k]['ceil']   = $ceil;

            $data['total_cost'] += $v['cost'];
            $data['total_num'] ++;
            $data['total_profit'] += $profit;
            $data['total_ceil'] += $ceil;
        }

        $data['total_ceil'] = round(($data['total_ceil'] / $data['total_num']), 2);

        cache::set($cacheKey, $data, 3600);

        return view('money', ['data' => $data]);
    }

    // 添加购买商品
    public function ajaxAdd(){
        if (!input('?post.articleNumber') || !input('post.size') || !input('post.cost') || !input('post.buyDate')){
            return returnJson('', 201, '请填写 货号、尺码、成本、购买日期！');
        }

        // 获取商品信息
        $product = db('du_product')->where('articleNumber', input('post.articleNumber'))->find();
        if (!$product){
            return returnJson('', 202, '商品不存在！');
        }

        $add_data = [
            'title'         => $product['title'],
            'image'         => $product['logoUrl'],
            'size'          => input('post.size'),
            'articleNumber' => $product['articleNumber'],
            'soldNum'       => $product['soldNum'],
            'sellDate'      => $product['sellDate'],
            'cost'          => input('post.cost'),
            'buyDate'       => strtotime(input('post.buyDate')),
        ];

        $ret = db('money')->insert($add_data);
        if (!$ret){
            return returnJson('', 202, '添加失败！');
        }

        return returnJson($add_data, 200, '添加成功！');
    }

    // 删除购买商品
    public function ajaxDel(){
        if (input('?post.id')){
            $ret = db('money')->delete(['_id' => input('post.id')]);
            return returnJson($ret, 200, '删除成功！');
        }
    }

    // 清除缓存
    public function ajaxClear(){
        cache::rm('index_money');
    }



}
