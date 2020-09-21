<?php

namespace App\HttpController\Service\Common;

use App\HttpController\Service\HttpClient\CoHttpClient;
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
        $client_id = \Yaconf::get('baidu.clientId');
        $client_secret = \Yaconf::get('baidu.clientSecret');
        $url = \Yaconf::get('baidu.getTokenUrl');

        //auth
        $res = (new CoHttpClient())->needJsonDecode(true)->send($url, [
            'grant_type' => $grant_type,
            'client_id' => $client_id,
            'client_secret' => $client_secret
        ], [], 'get');

        $token = $res['access_token'];

        //准备内容检查
        $url = \Yaconf::get('baidu.checkWorkUrl') . "?access_token={$token}";

        $content = ['content' => $content];

        $res = (new CoHttpClient())->needJsonDecode(true)->send($url, $content);

        return $res;
    }

}
