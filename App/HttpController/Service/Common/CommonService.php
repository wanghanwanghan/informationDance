<?php

namespace App\HttpController\Service\Common;

use Amenadiel\JpGraph\Graph\Graph;
use Amenadiel\JpGraph\Graph\PieGraph;
use Amenadiel\JpGraph\Plot\AccBarPlot;
use Amenadiel\JpGraph\Plot\BarPlot;
use Amenadiel\JpGraph\Plot\GroupBarPlot;
use Amenadiel\JpGraph\Plot\LinePlot;
use Amenadiel\JpGraph\Plot\PiePlot;
use App\HttpController\Service\Common\EmailTemplate\Template01;
use App\HttpController\Service\Common\EmailTemplate\Template02;
use App\HttpController\Service\Common\EmailTemplate\Template03;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use App\HttpController\Service\Sms\SmsService;
use App\Task\Service\TaskService;
use Carbon\Carbon;
use EasySwoole\Component\Singleton;
use EasySwoole\Http\Message\UploadFile;
use EasySwoole\Http\Response;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Smtp\Mailer;
use EasySwoole\Smtp\MailerConfig;
use EasySwoole\Smtp\Message\Attach;
use EasySwoole\Smtp\Message\Html;
use EasySwoole\VerifyCode\Conf;
use EasySwoole\VerifyCode\VerifyCode;
use Qiniu\Auth;
use Qiniu\Sms\Sms;
use wanghanwanghan\someUtils\control;

class CommonService extends ServiceBase
{
    use Singleton;

    //写log
    function log4PHP($content, $type = 'info', $filename = '')
    {
        (!is_array($content) && !is_object($content)) ?: $content = jsonEncode($content);

        return control::writeLog($content, LOG_PATH, $type, $filename);
    }

    //存图片
    function storeImage(array $image, $type): array
    {
        $type = strtolower($type);

        $returnPath = [];

        foreach ($image as $key => $oneImage) {
            try {
                switch ($type) {
                    case 'avatar':
                        $newFilename = control::getUuid() . '.jpg';
                        $oneImage->moveTo(AVATAR_PATH . $newFilename);
                        $returnPath[$key] = str_replace(ROOT_PATH, '', AVATAR_PATH . $newFilename);
                        break;
                    case 'auth':
                        $newFilename = control::getUuid() . '.jpg';
                        $oneImage->moveTo(AUTH_BOOK_PATH . $newFilename);
                        $returnPath[$key] = str_replace(ROOT_PATH, '', AUTH_BOOK_PATH . $newFilename);
                        break;
                    case 'ocr':
                        $newFilename = control::getUuid() . '.jpg';
                        $oneImage->moveTo(OCR_PATH . $newFilename);
                        $returnPath[$key] = str_replace(ROOT_PATH, '', OCR_PATH . $newFilename);
                        break;
                }
            } catch (\Throwable $e) {
                $this->writeErr($e, __FUNCTION__);
                $returnPath[$key] = 'upload error';
            }
        }

        return $returnPath;
    }

    function getOtherFilePath(): string
    {
        $suffix = date('Y') . DIRECTORY_SEPARATOR . date('m') . DIRECTORY_SEPARATOR;

        $path = OTHER_FILE_PATH . $suffix;

        is_dir($path) ?: mkdir($path, 0644);

        return $path;
    }

    //存文件
    function storeFile($files): array
    {
        $path = [];

        foreach ($files as $key => $oneFile) {
            if ($oneFile instanceof UploadFile) {
                try {
                    //提取文件后缀
                    $ext = explode('.', $oneFile->getClientFilename());
                    $ext = '.' . end($ext);

                    $newFilename = control::getUuid() . $ext;

                    $pathTemp = $this->getOtherFilePath();

                    $oneFile->moveTo($pathTemp . $newFilename);

                    $path[$key] = str_replace(ROOT_PATH, '', $pathTemp . $newFilename);

                } catch (\Throwable $e) {
                    $this->writeErr($e, __FUNCTION__);
                    $path[$key] = $e->getMessage();
                }
            }
        }

        return $path;
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

            //显示柱状图上的数
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
        $code = control::randNum(6);

        $res = TaskService::getInstance()->create(function () use ($type, $phone, $code) {
            if (strtolower($type) === 'login') {
                return SmsService::getInstance()->login($phone, $code);
            } elseif (strtolower($type) === 'reg') {
                return SmsService::getInstance()->reg($phone, $code);
            } else {
                return false;
            }
        }, 'sync');

        $redis = Redis::defer('redis');

        $redis->select(14);

        $redis->set($phone . $type, $code, 600);

        return $res ? '验证码发送成功' : '验证码发送失败';
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
                $tmp[] = (string)$one;
            }
            return $client->sendMessage($tempId, $tmp, ['code' => $code]);
        }, 'sync');

        return empty(current($res)) ? '验证码发送失败' : '验证码发送成功';
    }

    //发送邮件
    function sendEmail($sendTo, $addAttachment = [], $templateNum = '01', $options = [])
    {
        $config = new MailerConfig();
        $config->setServer(CreateConf::getInstance()->getConf('env.mailServer'));
        $config->setSsl(true);
        $config->setPort((int)CreateConf::getInstance()->getConf('env.mailPort'));
        $config->setUsername(CreateConf::getInstance()->getConf('env.mailUsername'));
        $config->setPassword(CreateConf::getInstance()->getConf('env.mailPassword'));
        $config->setMailFrom(CreateConf::getInstance()->getConf('env.mailFrom'));
        $config->setTimeout(10);//设置客户端连接超时时间
        $config->setMaxPackage(1024 * 1024 * 5);//设置包发送的大小：5M

        //设置文本或者html格式
        $mimeBean = new Html();
        $templateNum = (string)str_pad($templateNum, 2, '0', STR_PAD_LEFT);
        switch ($templateNum) {
            case '01':
                //极简
                $template = Template01::getInstance();
                break;
            case '02':
                //简版
                $template = Template02::getInstance();
                break;
            case '03':
                //深度
                $template = Template03::getInstance();
                break;
        }
        $mimeBean->setSubject($template->getSubject($options['entName']));
        $mimeBean->setBody($template->getBody());

        try {
            //添加附件
            if (!empty($addAttachment)) {
                foreach ($addAttachment as $onePathAndFilename) {
                    $mimeBean->addAttachment(Attach::create($onePathAndFilename));
                }
            }
            $mailer = new Mailer($config);
            //发送邮件
            $mailer->sendTo($sendTo, $mimeBean);
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        return true;
    }

    //验证邮箱
    function validateEmail($emailStr)
    {
        $pattern = '/^([0-9A-Za-z\-_\.]+)@([0-9a-z]+\.[a-z]{2,3}(\.[a-z]{2})?)$/i';

        $username = preg_replace($pattern, '$1', $emailStr);
        $domain = preg_replace($pattern, '$2', $emailStr);

        return preg_match($pattern, $emailStr);
    }

    //验证身份证
    function validateIdCard($idCardStr)
    {
        $pattern = '/(^\d{17}([0-9]|X)$)/';

        return preg_match($pattern, strtoupper($idCardStr));
    }

}
