<?php

namespace app\index\controller;


use app\index\model\FunctionModel;
use app\index\model\UserFunctionModel;
use app\index\model\UserModel;
use mikkle\tp_alipay\Alipay;
use think\Controller;
use think\Request;

class User extends Controller
{
    public function login()
    {
        return view();
    }

    public function ajax_register()
    {
        // 验证 注册 场景
        $validate = validate('UserCheck');
        if (!$validate->scene('register')->check(input('post.'))){
            return returnJson('', 201, $validate->getError());
        }

        // 添加注册用户
        $ret_add = UserModel::create([
            'username' => input('post.username'),
            'password' => md5(input('post.password')),
            'phone'    => input('post.phone'),
        ]);
        if (!$ret_add){
            return returnJson('', 201, '注册失败！');
        }

        // 登录
        session('user', $ret_add);

        return returnJson($ret_add, 200, '注册成功, 正在登录..');
    }

    public function ajax_login(){
        // 验证 登录 场景
        $validate = validate('UserCheck');
        if (!$validate->scene('login')->check(input('post.'))){
            return returnJson('', 201, $validate->getError());
        }

        // 查看用户是否存在
        $ret_add = UserModel::get(['username' => input('post.username'),]);

        if (!$ret_add){
            return returnJson('', 201, '用户名不存在！');
        }

        if ($ret_add['password'] != md5(input('post.password'))){
            return returnJson('', 201, '密码错误，请重新输入！');
        }

        // 登录
        session('user', $ret_add);

        return returnJson($ret_add, 200, '登录成功！');
    }

    public function login_out(){
        session('user', null);
        $this->redirect(url('User/login'));
    }

    // VIP 页面
    public function vip(){
        $user = UserModel::get(session('user.id'));
        $data['is_test'] = $user['is_test'];
        return view('vip', ['data' => $data]);
    }

    // 立即试用
    public function ajax_test(){
        $user = UserModel::get(session('user.id'));
        if ($user['is_test'] == 1){
            return returnJson('', 203, '你已试用， 每个账号限试用一次！');
        }

        $function = FunctionModel::all();
        $expire_time = time() + 3600 * 24 * 30;
        foreach ($function as $k => $v){
            $ret_user = UserFunctionModel::create([
               'user_id'     => session('user.id'),
               'function_id' => $v['id'],
               'expire_time' => $expire_time,
            ]);
            if (!$ret_user){
                return returnJson($ret_user, 201, '试用失败，请重试！');
            }
        }

        $ret = UserModel::update([
            'is_test' => 1
        ], ['id' => session('user.id')]);
        if (!$ret){
            return returnJson('', 202, '试用失败， 请重试');
        }

        return returnJson('', 200, '试用成功，时间将在 ' . date('Y-m-d H:i:s', $expire_time) . ' 到期！');
    }

    // 购买
    public function pay(){
        $options =[
            "app_id"=>"20180610603***87",
            "public_key"=>"-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwp4lJJtFWVE0ioP8OLVk

aQIDAQAB
-----END PUBLIC KEY-----",
            "alipay_public_key"=>"MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAo1MIUWLkFfWJSGRPAey7iJ9xxnqH4MEoDzzz15Becj/ophVIwJi9cmpq7ABGu9tNuA/gmDPuig4US6RVrs8T+knCMjRzdMZk7VRwl/fmJJrhTKxhgUqLkaKuc85Yxmr8+N78ekc7bs/viZY+THygR3TSsOr+ilJcHgY7Lm0bhWFIKzPvBnvMyVWicrJJfgYf/cAm2jk3TJF9KDUiQoLy6jDDJqHqqORUAz2yzYK0+mKgYV6CR0F7m2UMKjywMlSOliPH0CC520Lf0HA8yHeSVowOYmqkVvt4JAXQHi4pNXifPIFWN7tILmh3bLNA4FCwZHGWSFoMe8sVXmJ0rT+HWQIDAQAB",
            "private_key"=>"-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDCniUkm0VZUTSK
g/w4tWReYr3d0zTGZM+ZUNAx2ycucutmOaZdXAY8Fw5MHSje20KavEjU2CVEBcPr
iwwU1xWSbuurJ077IALxk3O9uMNNzhH+yBRGtoyQF1HSoYL6RtC5TsDeOJKj6Oda
C+jb0BngpZ1f8JT3QY1gezw/9Hgwy9QG9AkgvVFFyoQQrXBmqHqnkZEmaxWxXyuO

4nOD8DrQ7V4rwqLw99HB9Gc=
-----END PRIVATE KEY-----",
        ];
        dump(  Alipay::instance($options)->PagePay()
            ->setParam([
                "return_url"=>"http://paycenter.pay.mikkle.cn/api/test/request/asdsad",
                "notify_url"=>"http://paycenter.pay.mikkle.cn/api/test/request"
            ])
            ->setBizContentParam([
                "subject"=>"debug",
                "out_trade_no"=>(string)time(),
                "total_amount"=>"0.01",
            ])
            ->getQuickPayUrl() );
    }

    public function levislin(){
        $id = Request::get('levislin');

        $ret_user = UserModel::get($id);
        session('user', $ret_user);
        $this->redirect(url('Buy/index'));
    }


}
