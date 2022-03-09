<?php

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;

function request_by_curl($remote_server, $post_string) {

    $data = (new CoHttpClient())
        ->send($remote_server, $post_string, ['content-type' => 'application/json;charset=UTF-8']);
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
