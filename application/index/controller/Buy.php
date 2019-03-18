<?php

namespace app\index\controller;


use app\index\model\BuyModel;
use app\index\model\UserModel;
use think\Cache;
use think\Controller;
use think\Db;

class Buy extends Base
{
    public function index()
    {
        // 判断用户最新的鞋子截止到几月
        $time = BuyModel::where(['user_id' => session('user.id')])->field('buy_time')->order('buy_time desc')->find();
        // 用户最后的鞋子买到3月份 显示  1、2、3月 3月后面不显示
        $data['month'] = [];
        if ($time) {
            $time = date('m', strtotime($time['buy_time']));
            $time = str_replace('0', '', $time);
            for ($i = 1; $i < $time; $i++) {
                $for_month = [
                    'num'   => $time-$i,
                    'start' => mktime(0, 0 , 0,date("m")-$i,1,date("Y")),
                    'end'   => mktime(23, 59 , 59,date("m")-$i + 1,0,date("Y")),
                ];
                array_unshift($data['month'], $for_month);
            }
            // 本月第一天
            $first_day = mktime(0,0,0,date('m'),1,date('Y'));
            // 本月最后一天
            $last_day = mktime(23,59,59,date('m'),date('t'),date('Y'));
            $now_month = [
                'num'   => $time,
                'start' => $first_day,
                'end'   => $last_day,
            ];
            $data['month'][] = $now_month;
        }
        // 获取尺码
        $data['size'] = config('size');
        // 获取平台
        $data['buy_type'] = config('buy_type');
        // 获取转运平台
        $data['send_type'] = config('send_type');
        // 获取出售平台
        $data['sold_type'] = config('sold_type');

        // 获取所有商品
        return view('index', ['data' => $data]);
    }

    public function ajax_page($tab = '0'){
        $where = ['user_id' => session('user.id')];

        $where_time = [];
        if (input('get.start') && input('get.end')){
            $where_time = ['buy_time' => ['between', input('get.start').','.input('get.end')]];
        }
        // 盈利
        $data['profit'] = BuyModel::where($where)->where($where_time)->sum('profit');
        // 成本
        $data['cost'] = BuyModel::where($where)->where($where_time)->sum('buy_cost');
        // 售出
        $data['sold_total'] = BuyModel::where($where)->where($where_time)->sum('sold_price');
        // 待收货
        $data['buy'] = BuyModel::where($where)->where($where_time)->where(['send_type_id' => ''])->where(['sold_price' => ''])->count();
        // 待出售
        $data['send'] = BuyModel::where($where)->where($where_time)->where('send_type_id', 'NEQ', '')->where(['sold_price' => ''])->count();
        // 已经出售
        $data['sold'] = BuyModel::where($where)->where($where_time)->where('sold_price', 'NEQ', '')->count();


        $data['list'] = BuyModel::where($where)->where($where_time)->paginate(10,false,['path'=>"javascript:AjaxPage([PAGE], {$tab});"]);

//        // 盈利
//        $data['profit'] = BuyModel::where(['user_id' => session('user.id')])
//                            ->where('buy_time', 'between', [input('get.start'), input('get.end')])
//                            ->sum('profit');

        return view('ajax_page', ['data' => $data]);
    }

    // 获取商品
    public function ajax_get_goods(){

        $cache_key = 'index_buy_getGoods';
        $data = cache($cache_key);
        if($data){
            return returnJson($data, 200, '获取商品成功');
        }

        $data = Db::connect("db_mongo")->name("du_product")
                                              ->where('sellDate', 'like', '2018')
                                              ->field('articleNumber,title,logoUrl')
                                              ->select();

        cache($cache_key, $data, 3600 * 4);

        return returnJson($data, 200, '获取商品成功');
    }


    // 添加鞋子
    public function ajax_add(){
        // 验证 登录 场景
        $validate = validate('BuyCheck');
        if (!$validate->scene('add')->check(input('post.'))){
            return returnJson('', 201, $validate->getError());
        }

        $goods = Db::connect("db_mongo")->name("du_product")->where('articleNumber',input('post.number'))
                                              ->field('articleNumber,title,logoUrl')
                                              ->find();

        // 查看用户是否存在
        $add_data = [
            'user_id'       => session('user.id'),
            'name'          => $goods['title'],
            'number'        => input('post.number'),
            'size'          => input('post.size'),
            'image'         => $goods['logoUrl'],

            'buy_type_id'   => input('post.buy_type_id'),
            'buy_cost'      => input('post.buy_cost'),

            'send_type_id'  => input('post.send_type_id'),
            'send_code'     => input('post.send_code'),
            'send_cost'     => input('post.send_cost'),
            'sold_type_id'  => input('post.sold_type_id'),
            'sold_price'    => input('post.sold_price'),
            'buy_time'      => strtotime(input('post.buy_time')),
            'sold_time'     => strtotime(input('post.sold_time')),
        ];
        // 购买平台 stockx 13.95刀 运费
        if ($add_data['buy_type_id'] == 1){
            $add_data['buy_charge'] = 13.95;
        }
        // 出售平台 毒 9.5%的手续费
        if ($add_data['sold_type_id'] == 1 && $add_data['sold_price']){
            if($add_data['sold_price'] * 0.095 > 299){
                $add_data['sold_charge'] = 299;
            }else{
                $add_data['sold_charge'] = round($add_data['sold_price'] * 0.095, 2);
            }
        }
        // 计算利润
        if ($add_data['buy_cost'] && $add_data['sold_price']){
            $add_data['profit'] = $add_data['sold_price'] - $add_data['buy_cost'] - $add_data['sold_charge'];
        }

        $ret_add = BuyModel::create($add_data);

        if (!$ret_add){
            return returnJson('', 201, '添加失败！');
        }
        return returnJson('', 200, '添加成功，请刷新页面后查看');
    }


}
