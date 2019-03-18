<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
//返回错误信息数组
//1.返回的数据
//2.返回码(默认200成功)
//3.返回的正确(错误)信息
function returnInfo($data = '', $retcode = 200, $msg = ''){
    $tmp_arr             = array();
    $tmp_arr['data']     = $data;
    $tmp_arr['retcode'] = $retcode;
    $tmp_arr['msg']     = $msg;
    return $tmp_arr;
}
// XML    转成    数组
//1.传入xml对象
function xmlToArray($simpleXmlElement){
    $simpleXmlElement=(array)$simpleXmlElement;
    foreach($simpleXmlElement as $k=>$v){
        if($v instanceof SimpleXMLElement ||is_array($v)){
            $simpleXmlElement[$k]=xmlToArray($v);
        }
    }
    return $simpleXmlElement;
}
//返回json格式
//1.返回的数据
//2.返回码(默认200成功)
//3.返回的正确(错误)信息
function returnJson($data = '', $code = 200, $msg = ''){
    header('Content-Type: application/json');
    $tmp_arr         = array();
    $tmp_arr['data'] = $data;
    $tmp_arr['code'] = $code;
    $tmp_arr['msg']  = $msg;
    echo json_encode($tmp_arr);
    die;
}

