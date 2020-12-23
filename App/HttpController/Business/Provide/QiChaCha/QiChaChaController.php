<?php

namespace App\HttpController\Business\Provide\QiChaCha;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\QiChaCha\QiChaChaService;

class QiChaChaController extends ProvideBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function checkResponse($res)
    {
        if (empty($res)) {
            //超时了
            $res = [];
            $this->responseCode = 500;
            $this->responseData = $res;
            $this->spendMoney = 0;
            $this->responseMsg = '请求超时';
        } else {
            $this->responseCode = $res[$this->cspKey]['code'];
            $this->responseData = $res[$this->cspKey]['result'];
            $this->responseMsg = $res[$this->cspKey]['msg'];
        }

        return true;
    }

    function getIPOGuarantee()
    {
        $entName = $this->getRequestData('entName', '');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $postData = [
            'entName' => $entName
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            //先拿股票代码
            $info = (new QiChaChaService())->setCheckRespFlag(true)
                ->get($this->qccListUrl.'ECIV4/GetBasicDetailsByName',['keyword'=>$postData['entName']]);

            CommonService::getInstance()->log4PHP($info);

            if ($info['code'] === 200 && !empty($info['result'])) {
                empty($res['Result']['StockNumber']) ? $StockNumber='' : $StockNumber=$info['result']['StockNumber'];
            }





            return (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccListUrl.'IPO/GetIPOGuarantee',$postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }





}