<?php

namespace App\HttpController\Business\Provide\TaoShu;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\TaoShu\TaoShuService;
use App\HttpController\Service\TaoShu\TaoShuTwoService;

class TaoShuController extends ProvideBase
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

    function lawPersonInvestmentInfo()
    {
        $entName = $this->getRequestData('entName', '');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $postData = [
            'entName' => $entName,
            'pageNo' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'lawPersonInvestmentInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getRegisterInfo()
    {
        $entName = $this->getRequestData('entName', '');

        $postData = [
            'entName' => $entName,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getRegisterInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getGoodsInfo()
    {
        $entName = $this->getRequestData('entName', '');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $postData = [
            'entName' => $entName,
            'pageNo' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getgoodsInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getEntScore()
    {
        $entName = $this->getRequestData('entName');

        $postData = [
            'entName' => $entName,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuTwoService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getEntScore');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getGraphGCoreData()
    {
        $entName = $this->getRequestData('entName');
        $level = $this->getRequestData('level');
        $nodeType = $this->getRequestData('nodeType');
        $attIds = $this->getRequestData('attIds', 'R101;R102;R103;R104;R105;R106;R107;R108');
        $attIds = str_replace(',', ';', $attIds);

        $postData = [
            'keyword' => $entName,
            'level' => $level - 0 > 3 ? '3' : $level . '',
            'nodeType' => $nodeType,
            'attIds' => $attIds,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getGraphGCoreData');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getEntGraphG()
    {
        $entName = $this->getRequestData('entName');
        $level = $this->getRequestData('level');
        $nodeType = $this->getRequestData('nodeType');
        $attIds = $this->getRequestData('attIds', 'R101;R102;R103;R104;R105;R106;R107;R108');
        $attIds = str_replace(',', ';', $attIds);

        $postData = [
            'keyword' => $entName,
            'level' => $level - 0 > 3 ? '3' : $level . '',
            'nodeType' => $nodeType,
            'attIds' => $attIds,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getEntGraphG');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }


}