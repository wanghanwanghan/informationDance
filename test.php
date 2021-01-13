<?php

include './vendor/autoload.php';

$url = 'https://api.meirixindong.com/provide/v1/qq/getThreeYearsData';

$appId = 'PHP_is_the_best_language_in_the_world';
$appSecret = 'PHP_GO';
$time = time();
$sign = substr(strtoupper(md5($appId . $appSecret . $time)), 0, 30);

$data = [
    'appId' => $appId,
    'time' => $time,
    'sign' => $sign,
    // 'image' => new \CURLFile(realpath('./WechatIMG261-tuya.png'))
    'entName' => '福建省华渔教育科技有限公司',
    'page' => 1,
    'pageSize' => 10,
];

$curl=curl_init();//初始化
curl_setopt($curl,CURLOPT_URL,$url);//设置请求地址
curl_setopt($curl,CURLOPT_POST,true);//设置post方式请求
curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,15);//几秒后没链接上就自动断开
curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,FALSE);
curl_setopt($curl,CURLOPT_POSTFIELDS,$data);//提交的数据
curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);//返回值不直接显示
$res=curl_exec($curl);//发送请求


//$curl = curl_init();
//curl_setopt($curl, CURLOPT_URL, $url);
//curl_setopt($curl, CURLOPT_POST, true);
//curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);
//curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
//curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
//curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//$result = curl_exec($curl);
//
//dd(json_decode($result, 1));





