<?php

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;

function request_by_curl($remote_server, $post_string) {

    $data = (new CoHttpClient())
        ->send($remote_server, $post_string, ['Content-Type' => 'application/json;charset=UTF-8'], [], 'POST');
    CommonService::getInstance()->log4PHP($data,'info','ding_alarm_request_by_curl');

    return $data;
}

/**
 * @param $title
 * @param $text
 * @return bool|string
 */
function dingAlarmMarkdown($title,$text){
    $webhook = 'https://oapi.dingtalk.com/robot/send?access_token=188728702d363a6eb79bccd584361da3f6de3a83e19071403345871747cd2482';
    $content = "# **{$title}log**\n";
    foreach ($text as $item) {
        $content .= "> **{$item['name']}：** {$item['msg']}\n\n";
    }

    $msg = ['title'=>$title,'text'=>$content];
    $data = array ('msgtype' => 'markdown','markdown' => $msg);
    return request_by_curl($webhook, json_encode($data));
}

function dingAlarmSimple($arr){
    $title = '测试日志';
    $text = [];
    foreach ($arr as $key=>$item) {
        $text[] = [
           'name' => $key,
            'msg' => $item,
        ];
    }
    dingAlarmMarkdownForWork($title,$text);
}
function dingAlarm($title,$arr){
    $text = [];
    foreach ($arr as $key=>$item) {
        $text[] = [
            'name' => $key,
            'msg' => $item,
        ];
    }
    $res = dingAlarmMarkdownForWork($title,$text);
    CommonService::getInstance()->log4PHP($res,'info','dingAlarm');
}
function dingAlarmUser($title,$arr,$user){
    $text = [];
    foreach ($arr as $key=>$item) {
        $text[] = [
            'name' => $key,
            'msg' => $item,
        ];
    }
    if(!empty($user)){
        $text[]=['name'=>'查看人','msg'=>'@'.$user['0']];
    }
    $res = dingAlarmMarkdownForWorkUser($title,$text,$user);
    CommonService::getInstance()->log4PHP($res,'info','dingAlarm');
}
function dingAlarmAtUser($title,$arr,$user){
    $text = [];
    foreach ($arr as $key=>$item) {
        $text[] = [
            'name' => $key,
            'msg' => $item,
        ];
    }
    $res = dingAlarmMarkdownForWorkAtUser($title,$text,$user);
    CommonService::getInstance()->log4PHP($res,'info','dingAlarm');
}

function dingAlarmMarkdownForWorkAtUser($title,$text,$user){
    $webhook = 'https://oapi.dingtalk.com/robot/send?access_token=54c82e7b5c12b9c4d866cf9f8ac8857cf3e80516c5f261544f0b1a799be8c846';
    $content = "# **{$title}**\n";
    foreach ($text as $item) {
        $content .= "> **{$item['name']}：** {$item['msg']}\n\n";
    }

    $msg = ['title'=>$title,'text'=>$content];
    $data = array ('msgtype' => 'markdown','markdown' => $msg,'at'=>['atMobiles'=>$user]);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $webhook);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array ('Content-Type: application/json;charset=utf-8'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// 线下环境不用开启curl证书验证, 未调通情况可尝试添加该代码
// curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
// curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}
//function ding
function dingAlarmMarkdownForWork($title,$text){
$webhook = 'https://oapi.dingtalk.com/robot/send?access_token=188728702d363a6eb79bccd584361da3f6de3a83e19071403345871747cd2482';
$content = "# **{$title}log**\n";
foreach ($text as $item) {
    $content .= "> **{$item['name']}：** {$item['msg']}\n\n";
}

$msg = ['title'=>$title,'text'=>$content];
$data = array ('msgtype' => 'markdown','markdown' => $msg);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhook);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_HTTPHEADER, array ('Content-Type: application/json;charset=utf-8'));
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// 线下环境不用开启curl证书验证, 未调通情况可尝试添加该代码
// curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
// curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
$data = curl_exec($ch);
curl_close($ch);
return $data;
}
//function ding
function dingAlarmMarkdownForWorkUser($title,$text,$user){
    $webhook = 'https://oapi.dingtalk.com/robot/send?access_token=188728702d363a6eb79bccd584361da3f6de3a83e19071403345871747cd2482';
    $content = "# **{$title}log**\n";
    foreach ($text as $item) {
        $content .= "> **{$item['name']}：** {$item['msg']}\n\n";
    }

    $msg = ['title'=>$title,'text'=>$content];
    $data = array ('msgtype' => 'markdown','markdown' => $msg,'at'=>['atMobiles'=>$user]);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $webhook);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array ('Content-Type: application/json;charset=utf-8'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// 线下环境不用开启curl证书验证, 未调通情况可尝试添加该代码
// curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
// curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}
