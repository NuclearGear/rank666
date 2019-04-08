<?php

namespace app\index\controller;


use app\index\model\BuyModel;
use app\index\model\UserModel;
use think\Cache;
use think\Controller;
use think\Db;

class Buy extends Base
{

    public $cache_tag = 'buy_ajax_page';


    public function index()
    {
        // 判断用户最新的鞋子截止到几月
        $time = BuyModel::where(['user_id' => session('user.id')])->field('buy_time')->order('buy_time desc')->find();
        // 用户最后的鞋子买到3月份 显示  1、2、3月 3月后面不显示
        $data['month'] = [];
        if ($time) {
            $time = date('m', time());
            $time = str_replace('0', '', $time);
            for ($i = 1; $i < $time; $i++) {
                $for_month = [
                    'num'   => $time-$i,
                    'start' => mktime(0, 0 , 0,date("m")-$i,1,date("Y")),
                    'end'   => mktime(23, 59 , 59,date("m")-$i + 1,0,date("Y")),
                ];
                $data['month'][] = $for_month;
//                array_unshift($data['month'], $for_month);
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
            array_unshift($data['month'], $now_month);
//            $data['month'][] = $now_month;
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
        // 缓存设置
        $get_params = input('get.');
        $get_where = implode('_',array_values($get_params['where']));
        $cache_key = implode('_', [session('user.id'), $get_params['page'], $get_params['tab'], $get_where]);
        $data = Cache::tag($this->cache_tag . session('user.id'))->get($cache_key);
        if ($data){
            return view('ajax_page', ['data' => $data]);
        }

        $where['user_id'] = ['=', session('user.id')];

        // 货号
        if (input('?get.where.number') && input('get.where.number')){
            $where['number'] = ['=', input('get.where.number')];
        }
        // 尺码
        if (input('?get.where.size') && input('get.where.size')){
            $where['size'] = ['=', input('get.where.size')];
        }
        // 购买平台
        if (input('?get.where.buy_type_id') && input('get.where.buy_type_id')){
            $where['buy_type_id'] = ['=', input('get.where.buy_type_id')];
        }
        // 转运平台
        if (input('?get.where.send_type_id') && input('get.where.send_type_id')){
            $where['send_type_id'] = ['=', input('get.where.send_type_id')];
        }
        // 出售平台
        if (input('?get.where.sold_type_id') && input('get.where.sold_type_id')){
            $where['sold_type_id'] = ['=', input('get.where.sold_type_id')];
        }
        // 价格 >=
        if (input('get.where.price_start') && !input('get.where.price_end')){
            $where['buy_cost'] = ['>=', input('get.where.price_start')];
        }
        // 价格 <=
        if (!input('get.where.price_start') && input('get.where.price_end')){
            $where['buy_cost'] = ['<=', input('get.where.price_end')];
        }
        // 价格 between
        if (input('get.where.price_start') && input('get.where.price_end')){
            $where['buy_cost'] = ['between', [input('get.where.price_start'), input('get.where.price_end')]];
        }
        // 出售时间 >=
        if (input('get.where.sold_time_start') && !input('get.where.sold_time_end')){
            $where['sold_time'] = ['>=', input('get.where.sold_time_start')];
        }
        // 出售时间 <=
        if (!input('get.where.sold_time_start') && input('get.where.sold_time_end')){
            $where['sold_time'] = ['<=', input('get.where.sold_time_end')];
        }
        // 出售时间 between
        if (input('get.where.sold_time_start') && input('get.where.sold_time_end')){
            $where['sold_time'] = ['between', [input('get.where.sold_time_start'), input('get.where.sold_time_end')]];
        }
        // 购买时间
        if (input('get.start') && input('get.end') && !input('get.where.sold_time_start') && !input('get.where.sold_time_end')){
            $where['buy_time'] = ['between', [input('get.start'), input('get.end')]];
        }


        // 待收货
        $data['buy'] = BuyModel::where($where)->where(['send_type_id' => ''])->where(['sold_price' => ''])->count();
        // 待出售
        $data['send'] = BuyModel::where($where)->where('send_type_id', 'NEQ', '')->where(['sold_price' => ''])->count();
        // 已经出售
        $data['sold'] = BuyModel::where($where)->where('sold_price', 'NEQ', '')->count();

        // 盈利
        $data['profit'] = BuyModel::where($where)->sum('profit');
        $data['profit'] = round($data['profit'], 2);
        // 成本
        $data['cost'] = BuyModel::where($where)->sum('buy_cost');
        $data['cost'] = round($data['cost'], 2);
        // 销售
        $data['sold_total'] = BuyModel::where($where)->sum('sold_price');
        $data['sold_total'] = round($data['sold_total'], 2);

        // 利率比
        if ($data['profit'] && $data['cost']){
            $data['ceil'] = round($data['profit'] / $data['cost'], 2) * 100;
        }else{
            $data['ceil'] = 0;
        }


        // 预计盈利
        $data['profit_future'] = 0;
        $data['profit_future_du'] = 0;
        $data['ceil_future'] = 0;

        $buy_arr = BuyModel::where($where)->field('number, buy_cost,size,sold_price')->select();
        if ($buy_arr){
            // 获取款式并且去重
            $need_shoes = [];
            foreach ($buy_arr as $k => $v){
                $need_shoes[$v['number'] . $v['size']] = [
                    'number' => $v['number'],
                    'size'   => $v['size'],
                ];
            }
            // 获取毒的价格
            $du_arr = [];
            foreach ($need_shoes as $k => $v){
                // 组装条件
                $where_profit = [
                    'articleNumber' => $v['number'],
                    'size'          => $v['size'],
                ];
                $ret_du = Db::connect("db_mongo")->name("du_size")->where($where_profit)->order('spiderTime', 'desc')->field('articleNumber, price,size')->find();
                $du_arr[$ret_du['articleNumber'] . $ret_du['size']] = $ret_du['price'] / 100;
            }
            // 计算预计盈利
            foreach ($buy_arr as $k => $v){
                if ($v['sold_price'] == 0){
                    // 预计盈利
                    if (isset($du_arr[$v['number'] . $v['size']]) && $du_arr[$v['number'] . $v['size']]){
                        $data['profit_future'] += ($du_arr[$v['number'] . $v['size']] - $v['buy_cost']) - ($du_arr[$v['number'] . $v['size']] * 0.095) - 100;
                    }
                }
            }
            $data['profit_future'] = round($data['profit_future'], 2);
            $data['ceil_future'] = round($data['profit_future'] / $data['cost'], 2) * 100;
        }


        if (isset($get_params['start']) && $get_params['start'] && isset($get_params['end']) && $get_params['end']){
            $start = $get_params['start'];
            $end = $get_params['end'];

            $js_path = "javascript:AjaxPage([PAGE], {$tab}, {$start}, {$end});";
        }else{
            $js_path = "javascript:AjaxPage([PAGE], {$tab});";
        }


        // 列表
        $data['list'] = BuyModel::where($where)->order('buy_time', 'desc')->order('id', 'desc')->paginate(30,false,['path'=>$js_path]);
        foreach ($data['list'] as $k => &$v){
            if (!$v['sold_price'] && isset($du_arr[$v['number'] . $v['size']]) && $du_arr[$v['number'] . $v['size']]){
                // 转运价格判断
                if ($v['send_cost']){
                    $send_cost = $v['send_cost'];
                }else{
                    $send_cost = 100;
                }

                // 增加预计利润
                $data['list'][$k]['profit_future'] = round(($du_arr[$v['number'] . $v['size']] - $v['buy_cost']) - ($du_arr[$v['number'] . $v['size']] * 0.095) - $send_cost, 2);
                // 利率比
                $data['list'][$k]['ceil_future']   = round($data['list'][$k]['profit_future'] / $v['buy_cost'] * 100, 2);
                $data['list'][$k]['price_future']  = $du_arr[$v['number'] . $v['size']];
                $data['list'][$k]['charge_future'] = round($du_arr[$v['number'] . $v['size']] * 0.095, 2);
                if ($data['list'][$k]['charge_future'] > '299') {
                    $data['list'][$k]['charge_future'] = '299';
                }
                $data['list'][$k]['send_future']   = $send_cost;
            }else{
                $data['list'][$k]['profit_future'] = '-';
                $data['list'][$k]['ceil_future']   = '-';
                $data['list'][$k]['price_future']  = '暂无';
                $data['list'][$k]['charge_future'] = '-';
                $data['list'][$k]['send_future']   = '-';
            }
        }
        Cache::tag($this->cache_tag . session('user.id'))->set($cache_key, $data, 3600 * 4);

        return view('ajax_page', ['data' => $data]);
    }

    // 获取商品
    public function ajax_get_goods(){

        $cache_key = 'index_buy_ajax_get_goods' . input('get.keyword');
        $data = cache($cache_key);
        if($data){
            return returnJson($data, 200, '获取商品成功');
        }

        $data = Db::connect("db_mongo")->name("du_product")
                                              ->whereOr('articleNumber', 'like', input('get.keyword'))
                                              ->whereOr('title', 'like', input('get.keyword'))
                                              ->field('articleNumber,title,sellDate,logoUrl')
                                              ->order('sellDate', 'desc')
                                              ->limit(15)
                                              ->select();

        // 拼接字符串
        $str = '<option value="" data-content="全部"></option>';
        foreach ($data as $k => $v){
            $content = '<img src=' . $v['logoUrl'] . ' width="50" style="margin-right:5px">' . $v['sellDate'] . ' [' . $v['articleNumber'] . '] ' .$v['title'];
            $str .= "<option value=\"{$v['articleNumber']}\" data-content='". $content ."'></option>";
        }
        $data = $str;

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

        $goods = Db::connect("db_mongo")->name("du_product")->where('articleNumber', input('post.number'))
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
            if(($add_data['sold_price'] * input('post.sold_charge')) > 299){
                $add_data['sold_charge'] = 299;
            }else{
                $add_data['sold_charge'] = round($add_data['sold_price'] * input('post.sold_charge'), 2);
            }
        }
        $add_data['profit'] = 0;
        // 价格-成本
        if ($add_data['buy_cost'] && $add_data['sold_price']){
            $add_data['profit'] = $add_data['sold_price'] - $add_data['buy_cost'];
        }
        // 价格-转运费
        if ($add_data['send_cost']){
            $add_data['profit'] = $add_data['profit'] - $add_data['send_cost'];
        }
        // 价格-手续费
        if ($add_data['sold_type_id'] == 1 && $add_data['sold_price']){
            $add_data['profit'] = $add_data['profit'] - $add_data['sold_charge'];
        }


        $ret_add = BuyModel::create($add_data);

        if (!$ret_add){
            return returnJson('', 201, '添加失败！');
        }

        // 清除查询缓存
        Cache::clear($this->cache_tag . session('user.id'));

        return returnJson('', 200, '添加成功');
    }

    // 删除鞋子
    public function ajax_del(){
        $ret_del = BuyModel::destroy(input('post.id'));
        if (!$ret_del){
            return returnJson($ret_del, 201, '删除失败，请刷新页面后重试！');
        }

        // 清除查询缓存
        Cache::clear($this->cache_tag . session('user.id'));

        return returnJson('', 200, '删除成功！');
    }

    public function edit(){
        $data['info'] = BuyModel::get(input('get.id'));
        $data['info'] = $data['info']->getData();
        // 获取尺码
        $data['size'] = config('size');
        // 获取平台
        $data['buy_type'] = config('buy_type');
        // 获取转运平台
        $data['send_type'] = config('send_type');
        // 获取出售平台
        $data['sold_type'] = config('sold_type');

        return view('edit', ['data' => $data]);
    }

    public function ajax_edit(){
        $goods = Db::connect("db_mongo")->name("du_product")->where('articleNumber', '=', input('post.number'))
                                                                        ->field('articleNumber,title,logoUrl')
                                                                        ->find();

        $params = [
            'name'          => $goods['title'],
            'number'        => input('post.number'),
            'size'          => input('post.size'),
            'image'         => $goods['logoUrl'],

            'buy_type_id'   => input('post.buy_type_id'),
            'buy_cost'      => input('post.buy_cost'),
            'buy_time'      => strtotime(input('post.buy_time')),

            'send_type_id'  => input('post.send_type_id'),
            'send_code'     => input('post.send_code'),
            'send_cost'     => input('post.send_cost'),

            'sold_type_id'  => input('post.sold_type_id'),
            'sold_price'    => input('post.sold_price'),
            'sold_express'  => input('post.sold_express'),
            'sold_time'     => strtotime(input('post.sold_time')),
        ];

        // 购买平台 stockx 13.95刀 运费
        if ($params['buy_type_id'] == 1){
            $params['buy_charge'] = 13.95;
        }
        // 出售平台 毒 9.5%的手续费
        if ($params['sold_type_id'] == 1 && $params['sold_price']){
            if(($params['sold_price'] * 0.095) > 299){
                $params['sold_charge'] = 299;
            }else{
                $params['sold_charge'] = round($params['sold_price'] * 0.095, 2);
            }
        }else{
            $params['sold_charge'] = 0;
            $params['profit'] = 0;
        }
        $params['profit'] = 0;
        // 价格-成本
        if ($params['buy_cost'] && $params['sold_price']){
            $params['profit'] = $params['sold_price'] - $params['buy_cost'];
        }
        // 价格-转运费
        if ($params['send_cost']){
            $params['profit'] = $params['profit'] - $params['send_cost'];
        }
        // 价格-平台手续费
        if ($params['sold_type_id'] == 1 && $params['sold_price']){
            $params['profit'] = $params['profit'] - $params['sold_charge'];
        }
        // 价格-平台邮寄费
        if ($params['sold_express']){
            $params['profit'] = $params['profit'] - $params['sold_express'];
        }
        $params['profit'] = round($params['profit'], 2);


        $ret_update = BuyModel::update($params, ['id' => input('post.id')]);
        if (!$ret_update){
            return returnJson('', 201, '修改失败！');
        }

        // 清除查询缓存
        Cache::clear($this->cache_tag . session('user.id'));

        return returnJson($ret_update, 200, '修改成功！！');
    }

    // 显示折线图
    public function chart(){
        $data['articleNumber'] = input('articleNumber', 'EG6860');
        $data['size_start'] = input('size_start', 42);
        $data['size_end'] = input('size_end', 42);
        if (input('?size') && input('size')){
            $data['size_start'] = input('size');
            $data['size_end'] = input('size');
        }

        return view('chart', ['data' => $data]);
    }

    // 显示商品列表
    public function goods(){
        return view();
    }


}
