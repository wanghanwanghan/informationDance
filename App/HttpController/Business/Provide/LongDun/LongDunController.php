<?php

namespace App\HttpController\Business\Provide\LongDun;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongDun\LongDunService;

class LongDunController extends ProvideBase
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

    //实际控制人和控制路径
    function getBeneficiary()
    {
        $entName = $this->request()->getRequestParam('entName');
        $percent = $this->request()->getRequestParam('percent') ?? 0;
        $mode = $this->request()->getRequestParam('mode') ?? 0;

        $postData = [
            'companyName' => $entName,
            'percent' => $percent - 0,
            'mode' => $mode - 0,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())->setCheckRespFlag(true)
                ->get($this->ldListUrl . 'Beneficiary/GetBeneficiary', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
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
            $info = (new LongDunService())->setCheckRespFlag(true)
                ->get($this->ldListUrl . 'ECIV4/GetBasicDetailsByName', ['keyword' => $postData['entName']]);
            if ($info['code'] === 200 && !empty($info['result'])) {
                empty($info['result']['StockNumber']) ? $stock = '' : $stock = $info['result']['StockNumber'];
            } else {
                $stock = '';
            }
            if (empty($stock)) return ['code' => 201, 'paging' => null, 'result' => null, 'msg' => '股票代码是空'];
            $postData = [
                'stockCode' => $stock,
                'pageIndex' => $postData['page'],
                'pageSize' => $postData['pageSize'],
            ];
            return (new LongDunService())->setCheckRespFlag(true)
                ->get($this->ldListUrl . 'IPO/GetIPOGuarantee', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getProjectProductCheck()
    {
        $searchKey = $this->getRequestData('searchKey');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $postData = [
            'searchKey' => $searchKey,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())
                ->setCheckRespFlag(true)
                ->get($this->ldListUrl . 'ProjectProductCheck/GetList', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getCompatProductRecommend()
    {
        $id = $this->getRequestData('id');

        $postData = [
            'id' => $id,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())
                ->setCheckRespFlag(true)
                ->get($this->ldListUrl . 'CompatProductRecommend/GetList', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //行政处罚
    function getAdministrativePenaltyList()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'AdministrativePenalty/GetAdministrativePenaltyList', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //招投标
    function tenderSearch()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('pageNo') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'Tender/Search', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }
}