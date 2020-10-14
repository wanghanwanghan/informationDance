<?php

namespace App\HttpController\Service\Common;

use Amenadiel\JpGraph\Graph\Graph;
use Amenadiel\JpGraph\Graph\PieGraph;
use Amenadiel\JpGraph\Plot\AccBarPlot;
use Amenadiel\JpGraph\Plot\BarPlot;
use Amenadiel\JpGraph\Plot\GroupBarPlot;
use Amenadiel\JpGraph\Plot\LinePlot;
use Amenadiel\JpGraph\Plot\PiePlot;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use App\Task\Service\TaskService;
use EasySwoole\Component\Singleton;
use EasySwoole\Http\Message\UploadFile;
use EasySwoole\Http\Response;
use EasySwoole\RedisPool\Redis;
use EasySwoole\VerifyCode\Conf;
use EasySwoole\VerifyCode\VerifyCode;
use Qiniu\Auth;
use Qiniu\Sms\Sms;
use wanghanwanghan\someUtils\control;

class CommonService extends ServiceBase
{
    use Singleton;

    //写log
    function log4PHP($content)
    {
        !is_array($content) ?: $content = jsonEncode($content);

        return control::writeLog($content, LOG_PATH);
    }

    //存图片
    function storeImage(UploadFile $uploadFile, $type): string
    {
        $type = strtolower($type);

        switch ($type) {
            case 'avatar':
                $newFilename = control::getUuid() . '.jpg';
                $uploadFile->moveTo(AVATAR_PATH . $newFilename);
                $returnPath = str_replace(ROOT_PATH, '', AVATAR_PATH . $newFilename);
                break;
            default:
                $returnPath = '';
        }

        return $returnPath;
    }

    //创建验证码
    function createVerifyCode(Response $response, $codeContent = '', $type = 'image')
    {
        $type = strtolower($type);
        strlen($codeContent) !== 0 ?: $codeContent = control::getUuid(4);

        //配置
        $config = new Conf();
        $config->setUseCurve();
        $config->setUseNoise();
        $config->setLength(strlen($codeContent));

        $code = new VerifyCode($config);

        switch ($type) {
            case 'image':
                $response->withHeader('Content-Type', 'image/png');
                $response->write($code->DrawCode($codeContent)->getImageByte());
                break;
            default:
                $response->write($code->DrawCode($codeContent)->getImageBase64());
        }

        return true;
    }

    //生成一个财务Bar图片
    function createBarPic(array $data = [], $labels = [], $extension = []): string
    {
        $graph = new Graph(!isset($extension['width']) ? 1200 : $extension['width'], !isset($extension['height']) ? 600 : $extension['height']);
        $graph->SetScale('textlin');

        $graph->legend->Pos(0.02, 0.15);
        $graph->legend->SetShadow('darkgray@0.5');
        $graph->legend->SetFillColor('lightblue@0.3');

        $graph->img->SetAutoMargin();

        //设置标题
        !isset($extension['title']) ?: $graph->title->Set($extension['title']);

        //设置横坐标标题
        !isset($extension['xTitle']) ?: $graph->xaxis->title->Set($extension['xTitle']);
        //设置纵坐标标题
        !isset($extension['yTitle']) ?: $graph->xaxis->title->Set($extension['yTitle']);

        //横坐标显示
        $graph->xaxis->SetTickLabels($labels);

        $graph->SetUserFont1(SIMSUN_TTC);
        $graph->title->SetFont(FF_USERFONT1, FS_NORMAL, !isset($extension['titleSize']) ? 14 : $extension['titleSize']);
        $graph->xaxis->title->SetFont(FF_USERFONT1, FS_NORMAL);
        $graph->xaxis->SetFont(FF_USERFONT1, FS_NORMAL);
        $graph->xaxis->SetColor('black');
        $graph->ygrid->SetColor('black@0.5');
        $graph->legend->SetFont(FF_USERFONT1, FS_NORMAL);

        $BarPlotObjArr = [];

        $color = ['red', 'orange', 'yellow', 'green', 'blue'];

        foreach ($data as $key => $oneDataArray) {

            $bar = new BarPlot($oneDataArray);

            $bar->value->Show();

            $bar->SetFillColor($color[$key] . '@0.4');

            $bar->SetLegend($extension['legend'][$key]);

            $BarPlotObjArr[] = $bar;
        }

        $gbarplot = new GroupBarPlot($BarPlotObjArr);

        $gbarplot->SetWidth(0.6);

        $graph->Add($gbarplot);

        $fileName = control::getUuid(12) . '.jpg';

        $graph->Stroke(REPORT_IMAGE_TEMP_PATH . $fileName);

        return $fileName;
    }

    //发送验证码
    function sendCode($phone, $type)
    {
        $ak = CreateConf::getInstance()->getConf('env.qiNiuAk');
        $sk = CreateConf::getInstance()->getConf('env.qiNiuSk');
        $tempId = CreateConf::getInstance()->getConf('env.template01');
        $auth = new Auth($ak, $sk);
        $client = new Sms($auth);

        $code = control::randNum(6);

        $res = TaskService::getInstance()->create(function () use ($client, $tempId, $phone, $code) {
            return $client->sendMessage($tempId, [$phone], ['code' => $code]);
        }, 'sync');

        $redis = Redis::defer('redis');

        $redis->select(14);

        $redis->set($phone . $type, $code, 600);

        return empty(current($res)) ? '验证码发送失败' : '验证码发送成功';
    }

    //发送短信
    function sendSMS($phoneArr, $templateNum, $code = '')
    {
        $ak = CreateConf::getInstance()->getConf('env.qiNiuAk');
        $sk = CreateConf::getInstance()->getConf('env.qiNiuSk');
        $tempId = CreateConf::getInstance()->getConf("env.template{$templateNum}");
        $auth = new Auth($ak, $sk);
        $client = new Sms($auth);

        $res = TaskService::getInstance()->create(function () use ($client, $tempId, $phoneArr, $code) {
            $tmp = [];
            foreach ($phoneArr as $one) {
                $tmp[]=(string)$one;
            }
            return $client->sendMessage($tempId, $tmp, ['code' => $code]);
        }, 'sync');

        return empty(current($res)) ? '验证码发送失败' : '验证码发送成功';
    }

    //百度内容审核 - 纯文本
    function checkContentByAI($content, $type = 'word')
    {
        //https://login.bce.baidu.com/?account=&redirect=http%3A%2F%2Fconsole.bce.baidu.com%2F%3Ffromai%3D1#/aip/overview

        $label = [
            0 => '绝对没有',
            1 => '暴恐违禁',
            2 => '文本色情',
            3 => '政治敏感',
            4 => '恶意推广',
            5 => '低俗辱骂',
            6 => '低质灌水'
        ];

        $grant_type = 'client_credentials';
        $client_id = CreateConf::getInstance()->getConf('baidu.clientId');
        $client_secret = CreateConf::getInstance()->getConf('baidu.clientSecret');
        $url = CreateConf::getInstance()->getConf('baidu.getTokenUrl');

        //auth
        $res = (new CoHttpClient())->needJsonDecode(true)->send($url, [
            'grant_type' => $grant_type,
            'client_id' => $client_id,
            'client_secret' => $client_secret
        ], [], 'get');

        $token = $res['access_token'];

        //准备内容检查
        $url = CreateConf::getInstance()->getConf('baidu.checkWorkUrl') . "?access_token={$token}";

        $content = ['content' => $content];

        $res = (new CoHttpClient())->needJsonDecode(true)->send($url, $content);

        return $res;
    }


}
