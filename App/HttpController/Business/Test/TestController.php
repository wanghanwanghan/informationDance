<?php

namespace App\HttpController\Business\Test;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HeHe\HeHeService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use EasySwoole\Http\Message\UploadFile;

class TestController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return true;
    }

    function afterAction(?string $actionName): void
    {
    }

    function test()
    {
        $file = $this->request()->getUploadedFile('file');

        if ($file instanceof UploadFile) {

            $url = CreateConf::getInstance()->getConf('baidu.getTokenUrl');

            $res = (new CoHttpClient())->needJsonDecode(true)->useCache(false)->send($url, [
                'grant_type' => 'client_credentials',
                'client_id' => 'Z326OVZVu42CmyONeGmu7bF2',
                'client_secret' => 'TWDDZYf8D42IFA1OGO1ow9dpnsO7G8Ft'
            ], [], 'get');

            $token = $res['access_token'];

            $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];

            $url = "https://aip.baidubce.com/rest/2.0/ocr/v1/accurate_basic?access_token={$token}";

            $postData = [
                'image' => base64_encode($file->getStream()->__toString())
            ];

            $res = (new CoHttpClient())->send($url, $postData, $headers);

            var_dump($res);

        } else {
            var_dump(321);
        }
    }

}