<?

use Carbon\Carbon;
use wanghanwanghan\someUtils\control;

include './vendor/autoload.php';

$url = 'https://api.meirixindong.com/provide/v1/qq/getThreeYearsDataForLIAGRO_REL';
$url = 'https://api.meirixindong.com/provide/v1/ts/getRegisterInfo';
$url = 'https://api.meirixindong.com/provide/v1/xd/getFinanceBaseData';

$appId = 'PHP_is_the_best_language_in_the_world';
$appSecret = 'PHP_GO';
$time = time();
$sign = substr(strtoupper(md5($appId . $appSecret . $time)), 0, 30);

$data = [
    'appId' => $appId,
    'time' => $time,
    'sign' => $sign,
    // 'image' => new \CURLFile(realpath('./WechatIMG261-tuya.png'))
    'entName' => '葫芦岛海盛石油化工有限公司',
    'code' => '',
    'year' => '2019',
    'dataCount' => '3',
    'page' => 1,
    'pageSize' => 10,
];

//$curl = curl_init();//初始化
//curl_setopt($curl, CURLOPT_URL, $url);//设置请求地址
//curl_setopt($curl, CURLOPT_POST, true);//设置post方式请求
//curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);//几秒后没链接上就自动断开
//curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
//curl_setopt($curl, CURLOPT_POSTFIELDS, $data);//提交的数据
//curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//返回值不直接显示
//$res = curl_exec($curl);//发送请求
//dd(json_decode($res, true));

