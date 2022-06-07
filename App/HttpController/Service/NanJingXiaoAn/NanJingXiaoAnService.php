<?php

namespace App\HttpController\Service\NanJingXiaoAn;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use wanghanwanghan\someUtils\traits\Singleton;

class NanJingXiaoAnService extends ServiceBase
{
    use Singleton;

    private $url = 'https://api.365dayservice.com:8044/credit-api/v1/generalMobileInfo/2F_isp';
    private $xaKey = 'wanghan123';

    function generalMobileInfo(string $name, string $mobile)
    {
        $name = trim($name);
        preg_match_all('/\d+/', $mobile, $phone);
        $phone = current(current($phone));

        if (empty($name) || !is_numeric($phone) || strlen($phone) !== 11) {
            return $this->createReturn(500, null, [], '姓名或电话错误');
        }

        $header = [
            'xa-key' => $this->xaKey,
        ];

        $options = [
            'enableSSL' => true,
        ];

        $resq = (new CoHttpClient())->useCache(false)->send(
            $this->url,
            ['name' => $name, 'mobile' => $mobile],
            $header,
            $options,
            'postjson'
        );

        CommonService::getInstance()->log4PHP($resq);

    }

}
