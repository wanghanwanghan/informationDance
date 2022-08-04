<?php

namespace App\HttpController\Service\ZhongRuiYinTong;

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use wanghanwanghan\someUtils\control;

class ZhongRuiYinTongService extends ServiceBase
{
    private $urlBase = 'http://222.128.37.11:2212/';
    private $username;
    private $password;

    function __construct()
    {
        $this->username = CreateConf::getInstance()->getConf('zhongruiyintong.username');
        $this->password = CreateConf::getInstance()->getConf('zhongruiyintong.password');

        return parent::__construct();
    }

    private function checkResp($res): array
    {
        if (isset($res['coHttpErr'])) return $this->createReturn(500, null, [], 'co请求错误');

        $paging = null;

        !isset($res['status']) || ($status = !!$res['status']);

        $code = $res['code'] - 0;
        $message = trim($res['message']);
        $data = empty($res['data']) ? [] : $res['data'];

        return $this->createReturn($code, $paging, $data, $message);
    }

    //通过用户名密码获取访问接口所需要的 token 信息
    private function getToken(): ?string
    {
        $url = $this->urlBase . 'api/system/login';

        $postData = [
            'username' => $this->username,
            'password' => $this->password,
        ];

        $login_info = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $postData);

        $token = null;

        if (!empty($login_info)) {
            $login_info['code'] - 0 !== 0 ?: $token = $login_info['data']['token'];
        }

        return $token;
    }

    //车辆年检信息验证 没开权限
    function vehicleInspectionCheck(string $vehicleNo, int $plateColor, string $vehicleVIN)
    {
        //对道路运输车辆检测信息进行的核验，有效避免货物运输风险

        $url = $this->urlBase . 'api/system/vehicleInspection/check';

        $postData = [
            'vehicleNo' => trim($vehicleNo),//车牌号码
            'plateColor' => $plateColor,//车牌颜色
            'vehicleVIN' => trim($vehicleVIN),//车辆识别代号
        ];

        $header = ['token' => $this->getToken()];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $postData, $header);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

    //车企挂靠关系查询（vin）
    function judgeAffiliatedVinCheck(string $plateColor, string $vehicleVIN, string $organizationCode)
    {
        //根据车辆和企业统一社会信用代码，判断车辆和企业是否为挂靠关系

        $url = $this->urlBase . 'api/system/judgeAffiliated/vinCheck';

        $postData = [
            'plateColor' => trim($plateColor),//车牌颜色
            'vehicleVin' => trim($vehicleVIN),//车牌号码
            'organizationCode' => trim($organizationCode),//统一社会信用代码
        ];

        $header = ['token' => $this->getToken()];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $postData, $header);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

    //车企挂靠关系查询（车牌）
    function judgeAffiliatedNoCheck(string $vehicleNo, string $plateColor, string $organizationCode)
    {
        //根据车辆和企业统一社会信用代码，判断车辆和企业是否为挂靠关系

        $url = $this->urlBase . 'api/system/judgeAffiliated/noCheck';

        $postData = [
            'vehicleNo' => trim($vehicleNo),//车牌号码
            'plateColor' => trim($plateColor),//车牌颜色
            'organizationCode' => trim($organizationCode),//统一社会信用代码
        ];

        $header = ['token' => $this->getToken()];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $postData, $header);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

    //经营业户信息查询（vin）
    function searchOwnerVinCheck(string $plateColor, string $vehicleVIN)
    {
        //根据车辆 VIN 查询车辆所属企业信息

        $url = $this->urlBase . 'api/system/searchOwner/vinCheck';

        $postData = [
            'plateColor' => trim($plateColor),//车牌颜色代码
            'vehicleVin' => trim($vehicleVIN),//车辆识别代号
        ];

        $header = ['token' => $this->getToken()];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $postData, $header);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

    //经营业户信息查询（车牌）
    function searchOwnerNoCheck(string $vehicleNo, string $plateColor)
    {
        //根据车辆号牌查询车辆所属企业信息

        $url = $this->urlBase . 'api/system/searchOwner/noCheck';

        $postData = [
            'vehicleNo' => trim($vehicleNo),//车牌号码
            'plateColor' => trim($plateColor),//车牌颜色代码
        ];

        $header = ['token' => $this->getToken()];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $postData, $header);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

    //车辆信息查询(vin)
    function searchVehVinCheck(string $plateColor, string $vehicleVIN)
    {
        //根据车辆 vin 查询车辆相关信息

        $url = $this->urlBase . 'api/system/searchVeh/vinCheck';

        $postData = [
            'plateColor' => trim($plateColor),//车牌颜色代码
            'vehicleVin' => trim($vehicleVIN),//车辆识别代号
        ];

        $header = ['token' => $this->getToken()];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $postData, $header);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

    //车辆信息查询(车牌)
    function searchVehNoCheck(string $vehicleNo, string $plateColor)
    {
        //根据车辆号牌、车辆号牌颜色代码查询车辆相关信息

        $url = $this->urlBase . 'api/system/searchVeh/noCheck';

        $postData = [
            'vehicleNo' => trim($vehicleNo),//车牌号码
            'plateColor' => trim($plateColor),//车牌颜色代码 见 JT/T 697.7 —2014 中 5.6
        ];

        $header = ['token' => $this->getToken()];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $postData, $header);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

    //道路运输许可证验证是否过期
    function checkRoadTransportValidityDate(string $vehicleNo, int $plateColor, string $transCertificateCode, string $vehicleVIN, string $validityDate)
    {
        //对运输车辆身份核验，有效避免货物运输风险

        $url = $this->urlBase . 'api/system/roadTransport/checkValidityDate';

        $postData = [
            'vehicleNo' => trim($vehicleNo),//车牌号码
            'plateColor' => $plateColor,//车牌颜色
        ];

        if (!empty($transCertificateCode) || !empty($vehicleVIN)) {
            //二者选一就行
            empty($transCertificateCode) ?: $postData['transCertificateCode'] = trim($transCertificateCode);//道路运输证号
            empty($vehicleVIN) ?: $postData['vehicleVIN'] = trim($vehicleVIN);//车辆识别代号
        }

        empty($validityDate) ?: $postData['validityDate'] = trim($validityDate);//证件有效期 YYYYMMDD

        $header = ['token' => $this->getToken()];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $postData, $header);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

    //道路运输许可证验证一致性
    function checkRoadTransport(string $vehicleNo, int $plateColor, string $transCertificateCode, string $vehicleVIN)
    {
        //对运输车辆身份核验，有效避免货物运输风险

        $url = $this->urlBase . 'api/system/roadTransport/checkRoadTransport';

        $postData = [
            'vehicleNo' => trim($vehicleNo),//车牌号码
            'plateColor' => $plateColor,//车牌颜色
        ];

        if (!empty($transCertificateCode) || !empty($vehicleVIN)) {
            //二者选一就行
            empty($transCertificateCode) ?: $postData['transCertificateCode'] = trim($transCertificateCode);//道路运输证号
            empty($vehicleVIN) ?: $postData['vehicleVIN'] = trim($vehicleVIN);//车辆识别代号
        }

        $header = ['token' => $this->getToken()];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $postData, $header);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

    //经营许可证验证一致性
    function checkBusinessPermit(string $licenseCode, string $ownerName)
    {
        //对承运企业身份核验，有效避免货物运输风险

        $url = $this->urlBase . 'api/system/businessPermit/checkBusinessPermit';

        $postData = [
            'licenseCode' => trim($licenseCode),//经营许可证编号
            'ownerName' => trim($ownerName),//企业名称
        ];

        $header = ['token' => $this->getToken()];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $postData, $header);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

    //经营许可证验证是否过期
    function checkBusinessValidityDate(string $licenseCode, string $ownerName, string $validityDate)
    {
        //对承运企业身份核验，有效避免货物运输风险

        $url = $this->urlBase . 'api/system/businessPermit/checkValidityDate';

        $postData = [
            'licenseCode' => trim($licenseCode),//经营许可证编号
            'ownerName' => trim($ownerName),//企业名称
        ];

        empty($validityDate) ?: $postData['validityDate'] = trim($validityDate);//证件有效期 YYYYMMDD

        $header = ['token' => $this->getToken()];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $postData, $header);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

    //从业许可证验证一致性
    function checkOccupational(string $staffName, string $idCard, string $provinceCode)
    {
        //对道路运输驾驶员身份的核验，有效避免货物运输风险

        $url = $this->urlBase . 'api/system/occupational/checkOccupational';

        $postData = [
            'staffName' => trim($staffName),//姓名
            'idCard' => trim($idCard),//身份证号
            'provinceCode' => trim($provinceCode),//省行政区划代码
        ];

        $header = ['token' => $this->getToken()];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $postData, $header);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

    //从业许可证验证是否过期
    function checkOccupationalValidityDate(string $staffName, string $idCard, string $provinceCode, string $validityDate)
    {
        //对道路运输驾驶员身份的核验，有效避免货物运输风险

        $url = $this->urlBase . 'api/system/occupational/checkvalidityDate';

        $postData = [
            'staffName' => trim($staffName),//姓名
            'idCard' => trim($idCard),//身份证号
            'provinceCode' => trim($provinceCode),//省行政区划代码
        ];

        empty($validityDate) ?: $postData['validityDate'] = trim($validityDate);//证件有效期 YYYYMMDD

        $header = ['token' => $this->getToken()];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $postData, $header);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }
}
