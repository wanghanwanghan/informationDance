<?php

namespace App\HttpController\Service\CreateSeal;
/**
 * 例子
    $num = '91330206MA2826F51H';
    $num_arr = str_split($num);
    $num = implode('', array_reverse($num_arr));
    $cc = new SealService('厦门市扶树信息科技有限公司厦门市扶树信息科技有限公司', $num, 200);
    $cc->saveImg('png.png', "财务专用章");
    $cc::personalSeal("1.png","陈益芳");
 */


class SealService
{
    private $sealString;  //印章字符
    private $strMaxLeng;  //最大字符长度
    private $sealRadius;  //印章半径
    private $rimWidth;   //边框厚度
    private $innerRadius;  //内圆半径
    private $startRadius;  //五角星半径
    private $startAngle;  //五角星倾斜角度
    private $backGround;  //印章颜色
    private $centerDot;   //圆心坐标
    private $img;      //图形资源句柄
    private $font;     //指定的字体
    private $fontSize;   //指定字体大小
    private $width;     //图片宽度
    private $height;    //图片高度
    private $points;    //五角星各点坐标
    private $charRadius;  //字符串半径
    private $numRadius;  //数字半径
    private $charAngle;   //字符串倾斜角度
    private $spacing;    //字符间隔角度
    private $sealNum;    //数字
    private $sealName;    //章名字
    private $yheight; //字符的y坐标;

    //创建图片资源
    private function createImg()
    {
        $this->img = imagecreate($this->width, $this->height);
        imagecolorresolvealpha($this->img, 255, 255, 255, 127);
        $this->backGround = imagecolorallocate($this->img, 255, 0, 0);
    }

    //画印章边框imagerectangle
    private function drawRim()
    {
        for ($i = 0; $i < $this->rimWidth; $i++) {
            imagearc($this->img, $this->centerDot['x'], $this->centerDot['y'], $this->width - $i, $this->height - $i, 0, 360, $this->backGround);
        }
    }

    //画印章边框
    private function drawSqu()
    {
        for ($i = 0; $i < $this->rimWidth; $i++) {
            imagerectangle($this->img, 0 + $i, 0 + $i, 120 - $i, 120 - $i, $this->backGround);
        }
    }

    //画内圆
    private function drawInnerCircle()
    {
        imagearc($this->img, $this->centerDot['x'], $this->centerDot['y'], 2 * $this->innerRadius, 2 * $this->innerRadius, 0, 360, $this->backGround);
    }

    //画下面的数字
    private function drawNum()
    {
        //编码处理
        $charset = mb_detect_encoding($this->sealNum);
        if ($charset != 'UTF-8') {
            $this->sealNum = mb_convert_encoding($this->sealNum, 'UTF-8', 'GBK');
        }
        //相关计量                 100                 6
        $this->numRadius = $this->sealRadius - $this->rimWidth - 5; //数字半径
        $leng = mb_strlen($this->sealNum, 'utf8'); //字符串长度
//        if($leng > $this->strMaxLeng) $leng = $this->strMaxLeng;
        $avgAngle = 80 / 15;  //平均字符倾斜度

        //拆分并写入字符串
        $nums = array(); //字符数组
        for ($i = 0; $i < $leng; $i++) {
            $nums[] = mb_substr($this->sealNum, $i, 1, 'utf8');
            $r = 95 + $this->charAngle + $avgAngle * ($i - $leng / 2);   //坐标角度
            $R = 720 - $this->charAngle + $avgAngle * ($leng - 2 * $i - 1) / 2;  //字符角度
            $x = $this->centerDot['x'] + $this->numRadius * cos(deg2rad($r)); //字符的x坐标
            $y = $this->centerDot['y'] + $this->numRadius * sin(deg2rad($r)); //字符的y坐标
            imagettftext($this->img, (8 * $this->sealRadius / 100), $R, $x, $y, $this->backGround, $this->font, $nums[$i]);
        }
    }

    //画中间章的名字
    private function drawName()
    {
        //编码处理
        $charset = mb_detect_encoding($this->sealName);
        if ($charset != 'UTF-8') {
            $this->sealName = mb_convert_encoding($this->sealName, 'UTF-8', 'GBK');
        }
        //相关计量
        $leng = mb_strlen($this->sealName, 'utf8'); //字符串长度

        //拆分并写入字符串
        $nums = array(); //字符数组
        for ($i = 0; $i < $leng; $i++) {
            $nums[] = mb_substr($this->sealName, $i, 1, 'utf8');
            $x = (48 + $i * 22) * $this->sealRadius / 100; //字符的x坐标
            $y = $this->yheight; //字符的y坐标
            imagettftext($this->img, 18 * $this->sealRadius / 100, 0, $x, $y, $this->backGround, $this->font, $nums[$i]);
        }
    }

    //画五角星
    private function drawStart()
    {
        $ang_out = 18 + $this->startAngle;
        $ang_in = 56 + $this->startAngle;
        $rad_out = $this->startRadius;
        $rad_in = $rad_out * 0.4;
        for ($i = 0; $i < 5; $i++) {
            //五个顶点坐标
            $this->points[] = $rad_out * cos(2 * M_PI / 5 * $i - deg2rad($ang_out)) + $this->centerDot['x'];
            $this->points[] = $rad_out * sin(2 * M_PI / 5 * $i - deg2rad($ang_out)) + $this->centerDot['y'];

            //内凹的点坐标
            $this->points[] = $rad_in * cos(2 * M_PI / 5 * ($i + 1) - deg2rad($ang_in)) + $this->centerDot['x'];
            $this->points[] = $rad_in * sin(2 * M_PI / 5 * ($i + 1) - deg2rad($ang_in)) + $this->centerDot['y'];
        }
        imagefilledpolygon($this->img, $this->points, 10, $this->backGround);
    }

    //输出
    private function outPut()
    {
        header('Content-type:image/png');
        imagepng($this->img);
        imagedestroy($this->img);
    }

    //对外生成
    public function saveImg($path, $name = '')
    {
        if (mb_strlen($name) > 8) {
            throw new Exception("印章名长度不能大于8");
        }

        //思路
        //公司编码宽度是否固定占用(是)
        //公司名字数限制: 1-40
        //处理公司名方法:
        //1:在标准宽度未超出情况下,居中正常显示(1-20个字)
        //2:占用超过20个字时,根据校正变量,缩小文字大小

        $this->sealName = $name;
        //预创建图形
        $this->createImg();
        //印章边框
        $this->drawRim();
        //内圆
        $this->drawInnerCircle();
        //渲染印章名
        $this->drawName();
        //渲染公司编码
        $this->drawNum();
        //渲染公司名
        $this->drawString();
        //五角星
        $this->drawStart();
        imagepng($this->img, $path);
        imagedestroy($this->img);
    }

    //对外生成
    public function doImg($name = '')
    {
        $this->sealName = $name;
        $this->createImg();
        $this->drawRim();
        $this->drawInnerCircle();
        $this->drawName();
        $this->drawNum();
        $this->drawString();
        $this->drawStart();
        $this->outPut();
    }

    //构造方法
    public function __construct($str = '', $num = '', $rad = 200, $rmwidth = 6, $strad = 28, $stang = 0, $crang = 0, $fsize = 13, $inrad = 0)
    {
        if ($rad <= 100) {
            $rad = 200;
        }
        if (mb_strlen($str) > 40) {
            throw new Exception("公司名长度不能超过40");
        }

        if (mb_strlen($num) > 20) {
            throw new Exception("公司编码长度不能超过20");
        }

        $this->sealString = empty($str) ? '印章测试字符串' : $str;
        $this->sealNum = empty($num) ? '010101010' : $num;
        $this->strMaxLeng = 16;
        $this->sealRadius = $rad;
        $this->rimWidth = $rmwidth * $rad / 100;
        $this->startRadius = $strad * $rad / 100;//根据大圆半径修改
        $this->startAngle = $stang;
        $this->charAngle = $crang;
        $this->centerDot = array('x' => $rad, 'y' => $rad);
        $this->font = dirname(__FILE__) . '/simsun.ttc';
        $this->font = dirname(__FILE__) . '/simhei.ttf';
        $this->fontSize = $fsize;
        $this->innerRadius = $inrad;  //默认0,没有
        $this->spacing = 1;
        $this->width = 2 * $this->sealRadius;
        $this->height = 2 * $this->sealRadius;
        $this->yheight = 150 * $this->sealRadius / 100; //字符的y坐标
    }

    //画字符串
    private function drawString()
    {
        //编码处理
        $charset = mb_detect_encoding($this->sealString);
        if ($charset != 'UTF-8') {
            $this->sealString = mb_convert_encoding($this->sealString, 'UTF-8', 'GBK');
        }

        $leng = mb_strlen($this->sealString, 'utf8'); //字符串长度
        if ($leng > $this->strMaxLeng) {
            $this->strMaxLeng = $leng;
        }
        $varCorrecting = 1;
        //字体大小默认为 10*(半径/100)*数量变量校正
        if ($leng <= 10) {
            $varCorrecting = 1;
        } elseif ($leng <= 20) {
            $varCorrecting = 1;
        } elseif ($leng <= 30) {
            $varCorrecting = 0.7;
        } elseif ($leng <= 35) {
            $varCorrecting = 0.6;
        } elseif ($leng <= 40) {
            $varCorrecting = 0.55;
        }
        $this->fontSize = 15 * ($this->sealRadius / 100) * $varCorrecting;
        $this->charRadius = $this->sealRadius - $this->rimWidth - $this->fontSize - 10; //字符串半径
        $avgAngle = 250 / ($this->strMaxLeng);  //平均字符倾斜度
        //拆分并写入字符串
        $words = array(); //字符数组
        for ($i = 0; $i < $leng; $i++) {
            $words[] = mb_substr($this->sealString, $i, 1, 'utf8');
            $r = 630 + $this->charAngle + $avgAngle * ($i - $leng / 2);  //坐标角度
            $R = 720 - $this->charAngle + $avgAngle * ($leng - 2 * $i - 1) / 2;  //字符角度
            $x = $this->centerDot['x'] + $this->charRadius * cos(deg2rad($r)); //字符的x坐标
            $y = $this->centerDot['y'] + $this->charRadius * sin(deg2rad($r)); //字符的y坐标
            imagettftext($this->img, $this->fontSize, $R, $x, $y, $this->backGround, $this->font, $words[$i]);
        }
    }

    public static function personalSeal($filePath,$name, $width = 120, $height = 40)
    {
        if ($width/$height<2){
            throw new Exception("宽度必须大于等于高度的3倍");
        }
        $img = imagecreate($width, $height);

        //画背景
        imagefill($img, 0, 0, imagecolorresolvealpha($img, 255, 255, 255, 127));

        //编码处理
        $charset = mb_detect_encoding($name);
        if ($charset != 'UTF-8') {
            $name = mb_convert_encoding($name, 'UTF-8', 'GBK');
        }
        $font = dirname(__FILE__) . '/simsun.ttc';
        $fontSize = 50*$height/100;
        $fontBox = imagettfbbox($fontSize, 0, $font, $name);//获取文字所需的尺寸大小

        $fontColor = imagecolorallocate($img, 255, 0, 0);
        imagettftext($img, $fontSize, 0, ceil(($width - $fontBox[2]) / 2), ceil(($height - $fontBox[1] - $fontBox[7]) / 2), $fontColor, $font, $name);
        imagepng($img, $filePath);
        imagedestroy($img);
    }

    public function scaleImg($picName, $maxx = 800, $maxy = 450)
    {
        $info = getimageSize($picName);//获取图片的基本信息
        $w = $info[0];//获取宽度
        $h = $info[1];//获取高度

        if($w<=$maxx&&$h<=$maxy){
            return $picName;
        }
        $im = imagecreatefrompng($picName);
        //计算缩放比例
        if (($maxx / $w) > ($maxy / $h)) {
            $b = $maxy / $h;
        } else {
            $b = $maxx / $w;
        }
        //计算出缩放后的尺寸
        $nw = floor($w * $b);
        $nh = floor($h * $b);
        //创建一个新的图像源（目标图像）
        $nim = imagecreatetruecolor($nw, $nh);
        //透明背景变黑处理
        //2.上色
        $color=imagecolorallocate($nim,255,255,255);
        //3.设置透明
        imagecolortransparent($nim,$color);
        imagefill($nim,0,0,$color);

        //执行等比缩放
        imagecopyresampled($nim, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
        $savePath = TEMP_FILE_PATH.'qianzhang2.png';
        imagepng($nim,$savePath);
        //释放图片资源
        imagedestroy($im);
        imagedestroy($nim);
        //返回结果
        return $savePath;
    }

}


