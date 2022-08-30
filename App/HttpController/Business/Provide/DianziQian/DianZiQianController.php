<?php

namespace App\HttpController\Business\Provide\DianziQian;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\DianZiqian\DianZiQianService;
use App\HttpController\Service\MaYi\MaYiService;
use Carbon\Carbon;
use wanghanwanghan\someUtils\control;

class DianZiQianController extends ProvideBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }
    function checkResponse($res): bool
    {
        if (empty($res[$this->cspKey])) {
            $this->responseCode = 500;
            $this->responsePaging = null;
            $this->responseData = $res[$this->cspKey];
            $this->spendMoney = 0;
            $this->responseMsg = '请求超时';
        } else {
            $this->responseCode = $res[$this->cspKey]['code'];
            $this->responsePaging = $res[$this->cspKey]['paging'];
            $this->responseData = $res[$this->cspKey]['result'];
            $this->responseMsg = $res[$this->cspKey]['msg'];

            $res[$this->cspKey]['code'] === 200 ?: $this->spendMoney = 0;
        }

        return true;
    }

    function getAuthFile(): bool
    {
        $entName = $this->getRequestData('entName');
        $socialCredit = $this->getRequestData('socialCredit');
        $legalPerson = $this->getRequestData('legalPerson');
        $idCard = $this->getRequestData('idCard');
        $phone = $this->getRequestData('phone');
        $city = $this->getRequestData('city');
        $regAddress = $this->getRequestData('regAddress');
        $file = $this->getRequestData('file');
        $postData = [
            'entName' => $entName,
            'socialCredit' => $socialCredit,
            'legalPerson' => $legalPerson,
            'idCard' => $idCard,
            'phone' => $phone,
            'city' => $city,
            'regAddress' => $regAddress,
            'file' => $file
        ];
        CommonService::getInstance()->log4PHP([$postData],'info','getAuthFile');

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new DianZiQianService())->setCheckRespFlag(true)->getAuthFile($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    public function getCarAuthFile(){
        $entName = $this->getRequestData('entName');
        $socialCredit = $this->getRequestData('socialCredit');
        $legalPerson = $this->getRequestData('legalPerson');
        $idCard = $this->getRequestData('idCard');
        $phone = $this->getRequestData('phone');
        $city = $this->getRequestData('city');
        $regAddress = $this->getRequestData('regAddress');
        $vin = $this->getRequestData('vin');
        $postData = [
            'entName' => $entName,
            'socialCredit' => $socialCredit,
            'legalPerson' => $legalPerson,
            'idCard' => $idCard,
            'phone' => $phone,
            'city' => $city,
            'vin' => $vin
        ];
        CommonService::getInstance()->log4PHP([$postData],'info','getAuthFile');

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new DianZiQianService())->setCheckRespFlag(true)->getCarAuthFile($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    public function getUrl(){
        $this->csp->add($this->cspKey, function () {
            return (new DianZiQianService())->setCheckRespFlag(true)->getUrl();
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }
    public function doTemporaryAction(){
        $res = (new DianZiQianService())->doTemporaryAction();
        return $this->writeJson($res['code'], null, $res, '成功');
    }
    public function accountInfo(){
        $res = (new DianZiQianService())->accountInfo();
        return $this->writeJson($res['code'], null, $res, '成功');
    }
    public function costRecord(){
        $freezeDate = $this->getRequestData('freezeDate');
        if(empty($freezeDate)){
            return $this->writeJson(201, null, [], 'freezeDate不能为空');
        }
        $postData = ['freezeDate'=>$freezeDate];
        $res = (new DianZiQianService())->costRecord($postData);
        return $this->writeJson($res['code'], null, $res, '成功');
    }
    function testInvEntList():bool
    {
        $tmp = $this->getRequestData('data');
        $tmp = json_decode($tmp,true);
        dingAlarm('testInvEntList',['$tmp'=>json_encode($tmp)]);
        dingAlarm('testInvEntList',['companyName'=>$tmp['companyName']]);
        $data['entName'] = $tmp['companyName'] ?? '';
        $data['socialCredit'] = $tmp['nsrsbh'] ?? '';
        $data['legalPerson'] = $tmp['legalName'] ?? '';
        $data['idCard'] = $tmp['idCard'] ?? '';
        $data['phone'] = $tmp['mobile'] ?? '';
        $data['requestId'] = control::getUuid();
        $data['belong'] = '1';
        $data['fileData'] = $tmp['fileData'] ?? '';
        $data['orderNo'] = $tmp['orderNo'] ?? '';
        $data['test'] = true;
        $res = (new MaYiService())->authEnt($data);

        $res['result']['nsrsbh'] = $data['socialCredit'];
        $res['result']['authId'] = $data['requestId'];
        $res['result']['authTime'] = Carbon::now()->format('Y-m-d H:i:s');

        switch ($res['code']) {
            case 600:
            case 605:
                $res['code'] = '0001';
                $res['result']['authResultCode'] = '0';
                $res['result']['authResultMsg'] = '缺少参数';
                break;
            case 606:
            case 615:
                $res['code'] = '9999';
                $res['result']['authResultCode'] = '0';
                $res['result']['authResultMsg'] = '系统异常';
                break;
            default:
                $res['code'] = '0000';
                $res['result']['authResultCode'] = '1';
                $res['result']['authResultMsg'] = '认证授权通过';
        }
        return $this->writeJson($res['code'], null, $res, '成功');
    }
}