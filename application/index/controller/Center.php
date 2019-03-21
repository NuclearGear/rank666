<?php

namespace app\index\controller;
use app\index\model\UserModel;
use app\index\model\BuyModel;
use think\Controller;

class Center extends Base
{
    public function index()
    {
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
        return view('index', ['data' => $data]);
    }

    // VIP 页面
    public function vip(){
        return view();
    }
}
