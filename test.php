<?php

include './vendor/autoload.php';

$url = 'https://api.meirixindong.com/provide/v1/qcc/getIPOGuarantee';

$appId = '2F5AC1D30549E9DDE725E0342DF344F1';
$appSecret = '0549E9DDE725E034';
$time = time();
$sign = substr(strtoupper(md5($appId.$appSecret.$time)),0,30);

$data = [
    'appId' => $appId,
    'time' => $time,
    'sign' => $sign,
    'entName' => '晶澳太阳能科技股份有限公司',
    'page' => 1,
    'pageSize' => 1,
];

$curl=curl_init();//初始化
curl_setopt($curl,CURLOPT_URL,$url);//设置请求地址
curl_setopt($curl,CURLOPT_POST,true);//设置post方式请求
curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,15);//几秒后没链接上就自动断开
curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,FALSE);
curl_setopt($curl,CURLOPT_POSTFIELDS,$data);//提交的数据
curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);//返回值不直接显示
$res=curl_exec($curl);//发送请求



dd(json_decode($res,1));
