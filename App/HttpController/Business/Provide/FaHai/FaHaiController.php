<?php

namespace App\HttpController\Business\Provide\FaHai;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\FaYanYuan\FaYanYuanService;

class FaHaiController extends ProvideBase
{
    public $listBaseUrl;
    public $detailBaseUrl;

    function onRequest(?string $action): ?bool
    {
        $this->listBaseUrl = CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl');
        $this->detailBaseUrl = CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl');

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

    function getKtgg()
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $docType = 'ktgg';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getList($this->listBaseUrl . 'sifa', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getFygg()
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $docType = 'fygg';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getList($this->listBaseUrl . 'sifa', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getKtggDetail()
    {
        $id = $this->getRequestData('id');

        $docType = 'ktgg';

        $postData = [
            'id' => $id,
        ];

        $this->csp->add($this->cspKey, function () use ($postData, $docType) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getDetail($this->detailBaseUrl . $docType, $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getFyggDetail()
    {
        $id = $this->getRequestData('id');

        $docType = 'fygg';

        $postData = [
            'id' => $id,
        ];

        $this->csp->add($this->cspKey, function () use ($postData, $docType) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getDetail($this->detailBaseUrl . $docType, $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }


}



