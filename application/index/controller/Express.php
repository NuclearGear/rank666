<?php

namespace app\index\controller;


class Express
{
    static public function http_post_json($url, $jsonStr)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($jsonStr)
            )
        );
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array($httpCode, $response);
    }


    /**
     *  普通物流查询
     */
    static public function query($com,$num)
    {
        $com = trim($com);
        $num = trim($num);
        $express = [
            "转运四方" => 'zhuanyunsifang',
            "海带宝"   => 'haidaibao',
        ];

        if(key_exists($com,$express)){
            $com = $express[$com];
        }else{
            return false;
        }

        $post_data = array();
        $post_data["customer"] = 'A1687F7AB4FEB6C8EBCCF0432AE970EF';
        $key= 'ytfwDEGY6183' ;
        $post_data["param"] = '{"com":"'.$com.'","num":"'.$num.'"}';

        $url='http://poll.kuaidi100.com/poll/query.do';
        $post_data["sign"] = md5($post_data["param"].$key.$post_data["customer"]);
        $post_data["sign"] = strtoupper($post_data["sign"]);
        $o="";
        foreach ($post_data as $k=>$v)
        {
            $o.= "$k=".urlencode($v)."&";		//默认UTF-8编码格式
        }
        $post_data=substr($o,0,-1);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $result = curl_exec($ch);
        $data = str_replace("\"",'"',$result);
        $data = json_decode($data,true);
        return $data;
    }

    public function get(){
        $allow = ['转运四方'];
        if (!input('?get.express') || !input('?get.code') || !input('get.express') || !input('get.code')){
            return returnJson('', 201, '无结果');
        }
        if (!in_array(input('get.express'), $allow)){
            return returnJson('', 201, '无结果');
        }
        $ret = self::query(input('get.express'), input('get.code'));

        if ($ret['status'] != 200){
            return returnJson('', 201, '无结果');
        }
        $msg = [];
        foreach ($ret['data'] as $k => $v){
            if ($k == 0){
                $msg['new'] = $v['time'] . ' ' . $v['context'];
            }
            $msg['list'][] = $v['time'] . ' ' . $v['context'];
        }
        $msg['list'] = implode('<br>', array_reverse($msg['list']));

        return returnJson($msg, 200, '成功');
    }

}