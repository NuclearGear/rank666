<?php

namespace app\index\controller;


use app\index\model\UserModel;
use think\Cache;
use think\Controller;
use think\Db;

class Shoes extends Base
{
    // 单品查询
    public function index()
    {
        return view();
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
        $product = Db::connect("db_mongo")->name('du_product')->whereOr('title', 'like', input('get.articleNumber'))->whereOr('articleNumber', input('get.articleNumber'))->find();
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
        $sold_arr = Db::connect("db_mongo")->name('du_sold')->where('articleNumber', 'like', $product['articleNumber'])->order('size', 'asc')->select();
        $data['soldList'] = [];
        foreach ($sold_arr as $k => $v){
            $data['soldList'][] = [
                'size'    => $v['size'],
                'soldNum' => $v['soldNum']
            ];
        }


        /** 获取 尺码销量折线图 */
        $data_sold = Db::connect("db_mongo")->name('du_sold_record')->where(['articleNumber' => $product['articleNumber']])->order('spiderTime', 'asc')->select();
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
        $size = Db::connect("db_mongo")->name('du_size')->where(['articleNumber' => $product['articleNumber']])->order('size desc')->select();
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

        Cache::set($cacheKey, $data,3600 * 2);

        return returnJson($data, 200, '成功');
    }

}
