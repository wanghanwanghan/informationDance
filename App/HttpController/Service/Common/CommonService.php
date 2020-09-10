<?php

namespace App\HttpController\Service\Common;

use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;
use EasySwoole\Http\Message\UploadFile;
use EasySwoole\Http\Response;
use EasySwoole\VerifyCode\Conf;
use EasySwoole\VerifyCode\VerifyCode;
use wanghanwanghan\someUtils\control;

class CommonService extends ServiceBase
{
    use Singleton;

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
    function createVerifyCode(Response $response, $codeContent = '', $type = 'img')
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
            case 'img':
                $response->withHeader('Content-Type', 'image/png');
                $response->write($code->DrawCode($codeContent)->getImageByte());
                break;
            default:
                $response->write($code->DrawCode($codeContent)->getImageBase64());
        }

        return true;
    }


}
