<?php

namespace app\index\controller;


use app\index\model\BuyModel;
use app\index\model\TaobaoModel;
use app\index\model\UserModel;
use think\Cache;
use think\Controller;
use think\Db;

class Taobao extends Base
{

    public $cache_tag = 'taobao_ajax_page';


    public function index()
    {

        // 获取尺码
        $data['size'] = config('size');
        // 获取所有商品
        return view('index', ['data' => $data]);
    }

    public function ajax_page(){
        // 缓存设置
        $get_params = input('get.');
        $get_where = implode('_',array_values($get_params['where']));
        $cache_key = implode('_', [session('user.id'), $get_params['page'], $get_where]);
        $data = Cache::tag($this->cache_tag . session('user.id'))->get($cache_key);
        if (false){
            return view('ajax_page', ['data' => $data]);
        }

        $where['user_id'] = ['=', session('user.id')];

        // 货号
        if (input('?get.where.article_number') && input('get.where.article_number')){
                $where['article_number'] = ['=', input('get.where.article_number')];
        }
        // 货号
        if (input('?get.where.taobao_id') && input('get.where.taobao_id')){
            $where['taobao_id'] = ['=', input('get.where.taobao_id')];
        }


        $js_path = "javascript:AjaxPage([PAGE]);";


        // 列表
        $data['list'] = TaobaoModel::where($where)->order('create_time', 'desc')->order('update_time', 'desc')->paginate(30,false,['path'=>$js_path]);

        Cache::tag($this->cache_tag . session('user.id'))->set($cache_key, $data, 3600 * 4);

        return view('ajax_page', ['data' => $data]);
    }

    // 添加鞋子
    public function ajax_add(){
        // 验证 登录 场景
        $validate = validate('TaobaoCheck');
        if (!$validate->scene('add')->check(input('post.'))){
            return returnJson('', 201, $validate->getError());
        }

        // 鞋款信息查询
        $where = ['articleNumber' => input('post.article_number')];
        $product = Db::connect("db_mongo")->name('du_product')->where($where)->find();

        if (input('post.size_list/a')){
            $size_list = implode(',', input('post.size_list/a'));
        }else{
            $size_list = '';
        }

        // 查看用户是否存在
        $add_data = [
            'user_id'        => session('user.id'),
            'image'          => $product['logoUrl'],
            'article_number' => input('post.article_number'),
            'taobao_id'      => trim(input('post.taobao_id')),
            'product_id'     => $product['productId'],
            'size_list'      => $size_list,
            'price'          => input('post.price'),
            'title'          => $product['title'],
            'link'           => input('post.link'),
            'note'           => input('post.note'),
        ];

        $ret_add = TaobaoModel::create($add_data);

        if (!$ret_add){
            return returnJson('', 201, '添加失败！');
        }

        // 清除查询缓存
        Cache::clear($this->cache_tag . session('user.id'));

        return returnJson('', 200, '添加成功');
    }

    // 删除鞋子
    public function ajax_del(){
        $ret_del = TaobaoModel::destroy(input('post.id'), true);
        if (!$ret_del){
            return returnJson($ret_del, 201, '删除失败，请刷新页面后重试！');
        }

        // 清除查询缓存
        Cache::clear($this->cache_tag . session('user.id'));

        return returnJson('', 200, '删除成功！');
    }

    public function edit(){
        $data['info'] = TaobaoModel::get(input('get.id'));
        // 获取尺码
        $data['size'] = config('size');

        return view('edit', ['data' => $data]);
    }

    public function ajax_edit(){
        // 验证 登录 场景
        $validate = validate('TaobaoCheck');
        if (!$validate->scene('add')->check(input('post.'))){
            return returnJson('', 201, $validate->getError());
        }

        // 鞋款信息查询
        $where = ['articleNumber' => input('post.article_number')];
        $product = Db::connect("db_mongo")->name('du_product')->where($where)->find();

        if (input('post.size_list/a')){
            $size_list = implode(',', input('post.size_list/a'));
        }else{
            $size_list = '';
        }

        // 查看用户是否存在
        $params = [
            'user_id'        => session('user.id'),
            'image'          => $product['logoUrl'],
            'article_number' => input('post.article_number'),
            'taobao_id'      => trim(input('post.taobao_id')),
            'product_id'     => $product['productId'],
            'size_list'      => $size_list,
            'price'          => input('post.price'),
            'title'          => $product['title'],
            'link'           => input('post.link'),
            'note'           => input('post.note'),
        ];


        $ret_update = TaobaoModel::update($params, ['id' => input('post.id')]);
        if (!$ret_update){
            return returnJson('', 201, '修改失败！');
        }

        // 清除查询缓存
        Cache::clear($this->cache_tag . session('user.id'));

        return returnJson($ret_update, 200, '修改成功！！');
    }

}
