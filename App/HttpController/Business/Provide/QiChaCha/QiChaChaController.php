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
        if (empty($res[$this->cspKey])) {
            $this->responseCode = 500;
            $this->responsePaging = null;
            $this->responseData = $res[$this->cspKey];
            $this->spendMoney = 0;
            $this->responseMsg = '请求超时或出错';
        } else {
            $this->responseCode = $res[$this->cspKey]['code'];
            $this->responsePaging = $res[$this->cspKey]['paging'];
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
            'entName' => $entName,
            'page' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            //先拿股票代码
            $info = (new QiChaChaService())->setCheckRespFlag(true)
                ->get($this->qccListUrl.'ECIV4/GetBasicDetailsByName',['keyword'=>$postData['entName']]);
            if ($info['code'] === 200 && !empty($info['result'])) {
                empty($info['result']['StockNumber']) ? $stock='' : $stock=$info['result']['StockNumber'];
            }else{
                $stock = '';
            }
            if (empty($stock)) return [];
            $postData = [
                'stockCode' => $stock,
                'pageIndex' => $postData['page'],
                'pageSize' => $postData['pageSize'],
            ];
            return (new QiChaChaService())->setCheckRespFlag(true)
                ->get($this->qccListUrl.'IPO/GetIPOGuarantee',$postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }





}