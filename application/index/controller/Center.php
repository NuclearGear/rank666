<?php

namespace app\index\controller;
use app\index\model\UserModel;
use app\index\model\BuyModel;
use think\Cache;
use think\Controller;

class Center extends Base
{

    public $cache_tag = 'buy_ajax_page';

    public function index()
    {

        $cache_key = 'index_center_index';
        $data = Cache::tag($this->cache_tag . session('user.id'))->get($cache_key);
        if ($data){
            return view('index', ['data' => $data]);
        }

    	// 帐号验证
    	$where = ['user_id' => session('user.id')];
        // 本月盈利
        $data['profit'] = BuyModel::where($where)->whereTime('buy_time', 'month')->sum('profit');
        $data['profit'] = round($data['profit'],3);
        // 上月盈利
        $data['last_profit'] = BuyModel::where($where)->whereTime('buy_time', 'last month')->sum('profit');
        $data['last_profit'] = round($data['last_profit'],3);
        // 本月成本
        $data['buy_cost'] = BuyModel::where($where)->whereTime('buy_time', 'month')->sum('buy_cost');
        // 上月成本
        $data['last_buy_cost'] = BuyModel::where($where)->whereTime('buy_time', 'last month')->sum('buy_cost');
        // 本月销售额
        $data['sold_price_total'] = BuyModel::where($where)->wheretime('buy_time', 'month')->sum('sold_price');
        // 上月销售额
        $data['last_sold_price_total'] = BuyModel::where($where)->wheretime('buy_time', 'last month')->sum('sold_price');
        // 本月鞋子总数
        $data['shoes_total'] = BuyModel::where($where)->count();
        // 待出售
        $data['send'] = BuyModel::where($where)->where('send_type_id', 'NEQ', '')->where(['sold_price' => ''])->count();
        // 待收货
        $data['buy'] = BuyModel::where($where)->where(['send_type_id' => ''])->where(['sold_price' => ''])->count();
        // 已经出售
        $data['sold'] = BuyModel::where($where)->where('sold_price', 'NEQ', '')->count();
        // 本月利率
        if ($data['buy_cost'] == '0') {
        	$data['cost'] = '1';
        }else{
        	$data['cost'] = $data['buy_cost'];
        }
        $data['interest_rate'] = round($data['profit'] / $data['cost'],3);
        // 上月利率
        if ($data['last_buy_cost'] == '0') {
        	$data['last_cost'] = '1';
        }else{
        	$data['last_cost'] = $data['last_buy_cost'];
        }
        $data['last_interest_rate'] = round( $data['last_profit'] / $data['last_cost'],3);
        // 相关商品 价格折线图
        $data['size_start'] = '40';
        $data['size_end'] = '40';
        $data['goods_max'] = [];
        $data['send_list'] = BuyModel::where($where)->where(['sold_price' => 0])->order('buy_time', 'desc')->select();
        if ($data['send_list']){
            foreach ($data['send_list'] as $k => $v) {
                $number[] = $data['send_list'][$k]['number'];
            }
            $num = array_count_values($number);
            arsort($num);
            $data['goods_max'] = array_slice(array_keys($num), 0, 4);
        }

        Cache::tag('buy_ajax_page' . session('user.id'))->set($cache_key, $data, 3600 * 4);

        //转运信息
        return view('index', ['data' => $data]);
    }
}
