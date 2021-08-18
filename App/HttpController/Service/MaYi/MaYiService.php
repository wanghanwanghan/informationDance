<?php

namespace App\HttpController\Service\MaYi;

use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Models\EntDb\EntDbAreaInfo;
use App\HttpController\Service\BaiDu\BaiDuService;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\ServiceBase;
use App\HttpController\Service\TaoShu\TaoShuService;
use Carbon\Carbon;
use wanghanwanghan\someUtils\control;

class MaYiService extends ServiceBase
{
    const STATUS_0 = 0;//收到蚂蚁请求
    const STATUS_1 = 1;//pdf生成完毕
    const STATUS_2 = 2;//已经提交给大象
    const STATUS_3 = 3;
    const STATUS_4 = 4;
    const STATUS_5 = 5;

    function __construct()
    {
        return parent::__construct();
    }

    private function check($code, $paging, $result, $msg): array
    {
        return [
            'code' => $code,
            'paging' => $paging,
            'result' => $result,
            'msg' => $msg,
        ];
    }

    function authEnt($data): array
    {
        if (empty($data['entName'])) {
            return $this->check(600, null, null, '企业名称不能是空');
        }

        if (strlen($data['socialCredit']) !== 18) {
            return $this->check(605, null, null, '统一社会信用代码必须18位');
        }

        $areaCode = substr($data['socialCredit'], 2, 6) - 0;

        $region = EntDbAreaInfo::create()->get($areaCode);

        if (empty($region)) {
            return $this->check(610, null, null, '未找到行政区划');
        }

        $data['region'] = $region->getAttr('name');

        $postData = ['entName' => $data['entName']];

        $res = (new TaoShuService())->setCheckRespFlag(true)->post($postData, 'getRegisterInfo');

        $res = current($res['result']);

        $data['address'] = $res['DOM'];

        $check = AntAuthList::create()->where([
            'entName' => $data['entName'],
            'socialCredit' => $data['socialCredit'],
        ])->get();

        if (!empty($check)) {
            return $this->check(615, null, null, '已经授权过了');
        }

        $baiduApi = BaiDuService::getInstance()->addressToStructured(trim($res['DOM']));
        $baiduApiRes = [];
        if ($baiduApi['status'] === 0) {
            $baiduApiRes['regAddress'] = $res['DOM'];
            $baiduApiRes['province'] = $baiduApi['result']['province'];
            $baiduApiRes['provinceCode'] = $baiduApi['result']['province_code'];
            $baiduApiRes['city'] = $baiduApi['result']['city'];
            $baiduApiRes['cityCode'] = $baiduApi['result']['city_code'];
            $baiduApiRes['county'] = $baiduApi['result']['county'];
            $baiduApiRes['countyCode'] = $baiduApi['result']['county_code'];
            $baiduApiRes['town'] = $baiduApi['result']['town'];
            $baiduApiRes['townCode'] = $baiduApi['result']['town_code'];
        }

        AntAuthList::create()->data([
            'requestId' => $data['requestId'],
            'entName' => $data['entName'],
            'socialCredit' => $data['socialCredit'],
            'legalPerson' => $data['legalPerson'],
            'idCard' => $data['idCard'],
            'phone' => $data['phone'],
            'region' => $data['region'],
            'requestDate' => time(),
            'status' => 0,
            'regAddress' => $baiduApiRes['regAddress'] ?? '',
            'province' => $baiduApiRes['province'] ?? '',
            'provinceCode' => $baiduApiRes['provinceCode'] ?? '',
            'city' => $baiduApiRes['city'] ?? '',
            'cityCode' => $baiduApiRes['cityCode'] ?? '',
            'county' => $baiduApiRes['county'] ?? '',
            'countyCode' => $baiduApiRes['countyCode'] ?? '',
            'town' => $baiduApiRes['town'] ?? '',
            'townCode' => $baiduApiRes['townCode'] ?? '',
        ])->save();

        return $this->check(200, null, $data, null);
    }


}
