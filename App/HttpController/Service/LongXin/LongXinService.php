<?php

namespace App\HttpController\Service\LongXin;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;

class LongXinService extends ServiceBase
{
    private $usercode;
    private $userkey;
    private $baseUrl;
    private $sendHeaders;

    public $rangeArr = [
        ['name' => 'A00', 'range' => [0, 10]],
        ['name' => 'A01', 'range' => [10, 15]],
        ['name' => 'A02', 'range' => [15, 20]],
        ['name' => 'A03', 'range' => [20, 25]],
        ['name' => 'A04', 'range' => [25, 30]],
        ['name' => 'A05', 'range' => [30, 35]],
        ['name' => 'A06', 'range' => [35, 40]],
        ['name' => 'A07', 'range' => [40, 45]],
        ['name' => 'A08', 'range' => [45, 50]],
        ['name' => 'A09', 'range' => [50, 55]],
    ];

    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    function __construct()
    {
        $this->usercode = CreateConf::getInstance()->getConf('longxin.usercode');
        $this->userkey = CreateConf::getInstance()->getConf('longxin.userkey');
        $this->baseUrl = CreateConf::getInstance()->getConf('longxin.baseUrl');

        $this->sendHeaders = [
            'content-type' => 'application/x-www-form-urlencoded',
            'authorization' => '',
        ];

        return parent::__construct();
    }

    //更换区间
    function setRangeArr(array $range): LongXinService
    {
        $this->rangeArr = $range;
        return $this;
    }

    //二分找区间
    function binaryFind(int $find, int $leftIndex = 0, int $rightIndex = 9): ?array
    {
        if (!is_numeric($find)) return null;

        //如果不在所有区间内
        if ($leftIndex > $rightIndex) {
            if ($find < $this->rangeArr[0]['range'][0]) return $this->rangeArr[0];
            if ($find > $this->rangeArr[count($this->rangeArr) - 1]['range'][1])
                return $this->rangeArr[count($this->rangeArr) - 1];
            return null;
        }

        $middle = ($leftIndex + $rightIndex) / 2;

        //如果大于第二个数，肯定在右边
        if ($find > $this->rangeArr[$middle]['range'][1]) {
            return $this->binaryFind($find, $middle + 1, $rightIndex);
        }

        //如果小于第一个数，肯定在左边
        if ($find < $this->rangeArr[$middle]['range'][0])
            return $this->binaryFind($find, $leftIndex, $middle - 1);

        return $this->rangeArr[$middle];
    }

    //创建请求token
    private function createToken($params)
    {
        $str = '';
        ksort($params);

        foreach ($params as $k => $val) {
            $str .= $k . $val;
        }

        return hash_hmac('sha1', $str . $this->usercode, $this->userkey);
    }

    //公司名称换取entid
    private function getEntid($entName): ?string
    {
        $ctype = preg_match('/\d{5}/', $entName) ? 1 : 3;

        $arr = [
            'key' => $entName,
            'ctype' => $ctype,
            'usercode' => $this->usercode
        ];

        $this->sendHeaders['authorization'] = $this->createToken($arr);

        $res = (new CoHttpClient())
            ->useCache(false)
            ->send($this->baseUrl . 'saas/doc/001/', $arr, $this->sendHeaders);

        CommonService::getInstance()->log4PHP($res);

        (!empty($res) && !empty($res['data'])) ? $entid = $res['data'] : $entid = null;

        return $entid;
    }

    public function test()
    {
        return $this->getEntid('北京京东世纪贸易有限公司');
    }

}
