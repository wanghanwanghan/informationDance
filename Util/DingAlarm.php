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
//
function dingAlarmKeKe($title,$arr,$user){
    //提醒群
    $webhook = 'https://oapi.dingtalk.com/robot/send?access_token=8de334e1bd8432b22e864860440d16e405fa85b9786cdaf9f540c52ff0861348';
    $res = dingAlarmMarkdownForWorkUser($webhook,$title,$arr,$user);
    CommonService::getInstance()->log4PHP($res,'info','dingAlarm');
}

function dingAlarmUser($title,$arr,$user){
    //研发日志群
    $webhook = 'https://oapi.dingtalk.com/robot/send?access_token=188728702d363a6eb79bccd584361da3f6de3a83e19071403345871747cd2482';
    $res = dingAlarmMarkdownForWorkUser($webhook,$title,$arr,$user);
    CommonService::getInstance()->log4PHP($res,'info','dingAlarm');
}
function dingAlarmAtUser($title,$arr,$user){
//    $text = [];
//    foreach ($arr as $key=>$item) {
//        $text[] = [
//            'name' => $key,
//            'msg' => $item,
//        ];
//    }
//    $res = dingAlarmMarkdownForWorkAtUser($title,$text,$user);
//    CommonService::getInstance()->log4PHP($res,'info','dingAlarm');
}
//老板群
function dingAlarmMarkdownForWorkAtUser($title,$text,$user){
    $webhook = 'https://oapi.dingtalk.com/robot/send?access_token=54c82e7b5c12b9c4d866cf9f8ac8857cf3e80516c5f261544f0b1a799be8c846';
    return action($webhook,$title,$text,$user);
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
function dingAlarmMarkdownForWorkUser($webhook,$title,$arr,$user){
    //https://oapi.dingtalk.com/robot/send?access_token=8de334e1bd8432b22e864860440d16e405fa85b9786cdaf9f540c52ff0861348
    $text = [];
    foreach ($arr as $key=>$item) {
        $text[] = [
            'name' => $key,
            'msg' => $item,
        ];
    }
    if(!empty($user)){
        $msg = '';
        foreach ($user as $u){
            $msg.='@'.$u;
        }
        $text[]=['name'=>'查看人','msg'=>$msg];
    }
    return action($webhook,$title,$text,$user);
}
function action($webhook,$title,$text,$user){
    //https://oapi.dingtalk.com/robot/send?access_token=8de334e1bd8432b22e864860440d16e405fa85b9786cdaf9f540c52ff0861348
//        $webhook = 'https://oapi.dingtalk.com/robot/send?access_token=188728702d363a6eb79bccd584361da3f6de3a83e19071403345871747cd2482';
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
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function feishuTishi($title,$text){
    $tishi = "https://open.feishu.cn/open-apis/bot/v2/hook/e9cf2c4b-d94e-4735-8616-6d913cbb6709";
    $res = feishu($tishi,$title,$text);
    CommonService::getInstance()->log4PHP($res,'info','feishuTishi');
}
function feishu($url,$title,$text){
    $webhook = $url;
    $content = [];
    foreach ($text as $k=>$item) {
//        $content[] = [['tag'=>'text','text'=>$k.':'.$item]];
    }

    $msg = ['post'=> ['zh_cn'=> ['title'=>$title, 'content'=>$content]]];;
    $data = array ('msg_type' => 'post','content' => $msg);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $webhook);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array ('Content-Type: application/json;'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}