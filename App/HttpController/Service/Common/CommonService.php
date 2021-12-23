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
use App\HttpController\Service\Common\EmailTemplate\Template04;
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
    function log4PHP($content, $type = 'info', $filename = ''): bool
    {
        (!is_array($content) && !is_object($content)) ?:
            $content = jsonEncode($content, false);

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

        is_dir($path) || mkdir($path, 0755);

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
    function createVerifyCode(Response $response, $codeContent = '', $type = 'image'): bool
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

    //生成一个财务Line图片
    function createLinePic(array $data = [], $labels = [], $extension = []): string
    {
        $graph = new Graph(1200, 700);

        $graph->SetUserFont1(SIMSUN_TTC);
        $graph->title->SetFont(FF_USERFONT1, FS_NORMAL, 14);
        $graph->subtitle->SetFont(FF_USERFONT1);

        $graph->img->SetAutoMargin();
        $graph->img->SetAntiAliasing();
        $graph->SetScale('textlin');
        $graph->SetShadow();
        $graph->title->Set($extension['title']);
        $graph->subtitle->Set($extension['subTitle']);

        $graph->yscale->SetGrace(20);
        $graph->xaxis->SetTickLabels($labels);

        $p1 = new LinePlot($data);
        $p1->mark->SetType(MARK_FILLEDCIRCLE);
        $p1->mark->SetFillColor('red');
        $p1->mark->SetWidth(4);
        $p1->SetColor('blue');
        $p1->SetCenter();
        $graph->Add($p1);

        $fileName = control::getUuid(12) . '.jpg';

        $graph->Stroke(REPORT_IMAGE_TEMP_PATH . $fileName);

        return $fileName;
    }

    //生成一个仪表盘图片
    function createDashboardPic($angle, $word): string
    {
        //1、创建画布
        $im = imagecreate(400, 300);//创建一个基于调色板的图像
        $im = imagecreatetruecolor(400, 230);//创建一个真彩色图像

        $bg = imagecolorallocate($im, 255, 255, 255);//创建颜色
        $red = imagecolorallocate($im, 255, 0, 0);
        $orange = imagecolorallocate($im, 255, 100, 0);
        $yellow = imagecolorallocate($im, 255, 255, 0);
        $green = imagecolorallocate($im, 0, 255, 0);
        $dgreen = imagecolorallocate($im, 0, 150, 0);
        $blue = imagecolorallocate($im, 0, 0, 255);
        $black = imagecolorallocate($im, 0, 0, 0);

        //2、开始绘画
        imagefill($im, 0, 0, $bg);//填充背景颜色
        for ($i = 0; $i < 500; $i++) {
            $x = rand(1, 400);
            $y = rand(1, 230);
            imagesetpixel($im, $x, $y, $bg);//画点

            $xx = rand(1, 400);
            $yy = rand(1, 230);
            imagesetpixel($im, $xx, $yy, $bg);
        }

        imagefilledarc($im, 200, 200, 380, 385, 180, 360, $red, IMG_ARC_EDGED);
        imagefilledarc($im, 200, 200, 380, 385, 216, 360, $orange, IMG_ARC_EDGED);
        imagefilledarc($im, 200, 200, 380, 385, 252, 360, $yellow, IMG_ARC_EDGED);
        imagefilledarc($im, 200, 200, 380, 385, 288, 360, $green, IMG_ARC_EDGED);
        imagefilledarc($im, 200, 200, 380, 385, 324, 360, $dgreen, IMG_ARC_EDGED);
        imagefilledarc($im, 200, 200, 260, 265, 180, 360, $bg, IMG_ARC_EDGED);
        imagefilledarc($im, 200, 200, 380, 385, 214, 217, $bg, IMG_ARC_EDGED);
        imagefilledarc($im, 200, 200, 380, 385, 250, 253, $bg, IMG_ARC_EDGED);
        imagefilledarc($im, 200, 200, 380, 385, 287, 290, $bg, IMG_ARC_EDGED);
        imagefilledarc($im, 200, 200, 380, 385, 324, 326, $bg, IMG_ARC_EDGED);

        $p = [
            [185, 190],
            [200, 115],
            [215, 190],
            [200, 210],
        ];

        $tmp = [];

//        $word = [
//            '1.4' => 'dgreen',
//            '0.7' => 'green',
//            '0' => 'yellow',
//            '5.6' => 'orange',
//            '4.9' => 'red',
//        ];
//
//        $word = [
//            '1.4' => '低',
//            '0.7' => '较低',
//            '0' => '中',
//            '5.6' => '较高',
//            '4.9' => '高',
//        ];

        if ($word !== '无') {
            foreach ($p as $one) {
                $new_p = $this->angle([200, 190], $one, $angle);
                $tmp[] = $new_p[0];
                $tmp[] = $new_p[1];
            }
            imagepolygon($im, $tmp, 4, $red);
            imagefilledpolygon($im, $tmp, 4, $blue);
            imageellipse($im, 200, 190, 6, 6, $bg);//画圆
        }

        if (mb_strlen($word) === 2) {
            $width = 174;
        } else {
            $width = 187;
        }

        imagettftext($im, 20, 0, $width, 105, $black, SIMSUN_TTC, $word);//水平绘制字符串

        $filename = control::getUuid(12) . '.jpg';

        //3、输出图像
        imagejpeg($im, REPORT_IMAGE_TEMP_PATH . $filename);

        //4、释放资源
        imagedestroy($im);

        return $filename;
    }

    private function angle($p0, $p1, $c): array
    {
        //假设对图片上任意点(x,y)，绕一个坐标点(rx0,ry0)逆时针旋转a角度后的新的坐标设为(x0, y0)，有公式：
        //x0 = (x - rx0) * cos(a) - (y - ry0) * sin(a) + rx0 ;
        //y0 = (x - rx0) * sin(a) + (y - ry0) * cos(a) + ry0 ;

        $x = ($p1[0] - $p0[0]) * cos($c) - ($p1[1] - $p0[1]) * sin($c) + $p0[0];
        $y = ($p1[0] - $p0[0]) * sin($c) + ($p1[1] - $p0[1]) * cos($c) + $p0[1];

        return [$x, $y];
    }

    //发送验证码
    function sendCode($phone, $type): string
    {
        $type = strtolower($type);
        $code = control::randNum(6);

        $res = TaskService::getInstance()->create(function () use ($type, $phone, $code) {
            return SmsService::getInstance()->$type($phone, $code);
        }, 'sync');

        $redis = Redis::defer('redis');

        $redis->select(14);

        $redis->set($phone . $type, $code, 600);

        return $res ? '验证码发送成功' : '验证码发送失败';
    }

    //发送短信
    function sendSMS($phoneArr, $templateNum, $code = ''): string
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

        return empty(current($res)) ? '发送失败' : '发送成功';
    }

    //发送邮件
    function sendEmail($sendTo, $addAttachment = [], $templateNum = '01', $options = []): bool
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
        $templateNum = str_pad($templateNum, 2, '0', STR_PAD_LEFT);
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
            case '04':
                //两表
                $template = Template04::getInstance();
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

    //
    function spaceTo($str, $to = ','): string
    {
        $str = trim($str);
        $arr = explode(' ', $str);
        $arr = array_filter($arr);
        return implode($to, $arr);
    }

}
