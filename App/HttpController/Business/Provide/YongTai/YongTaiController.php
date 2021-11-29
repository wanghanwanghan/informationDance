<?php

namespace App\HttpController\Business\Provide\YongTai;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\LiuLengJing\LiuLengJingService;
use App\HttpController\Service\YongTai\YongTaiService;

class YongTaiController extends ProvideBase
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

    //分支机构
    function getBranch(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $page = $this->getRequestData('page', 1);

        $postData = [
            'entName' => $entName,
            'page' => $page . '',
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return YongTaiService::getInstance()
                ->setCheckRespFlag(true)->getBranch($postData['entName'], '', $postData['page']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //股东信息
    function getHolder(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $page = $this->getRequestData('page', 1);

        $postData = [
            'entName' => $entName,
            'page' => $page . '',
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return YongTaiService::getInstance()
                ->setCheckRespFlag(true)->getHolder($postData['entName'], '', $postData['page']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //股权变更
    function getHolderChange(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $page = $this->getRequestData('page', 1);

        $postData = [
            'entName' => $entName,
            'page' => $page . '',
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return YongTaiService::getInstance()
                ->setCheckRespFlag(true)->getHolderChange($postData['entName'], '', $postData['page']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //企业工商变更记录
    function getChangeinfo(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $page = $this->getRequestData('page', 1);

        $postData = [
            'entName' => $entName,
            'page' => $page . '',
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return YongTaiService::getInstance()
                ->setCheckRespFlag(true)->getChangeinfo($postData['entName'], '', $postData['page']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //企业基本信息
    function getBaseinfo(): bool
    {
        $entName = $this->getRequestData('entName', '');

        $postData = [
            'entName' => $entName,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return YongTaiService::getInstance()
                ->setCheckRespFlag(true)->getBaseinfo($postData['entName'], '');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //企业开票税号模糊查询
    function getEnterpriseTicketQuery(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $page = $this->getRequestData('page', 1);

        $postData = [
            'entName' => $entName,
            'page' => $page . '',
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return YongTaiService::getInstance()
                ->setCheckRespFlag(true)->getEnterpriseTicketQuery($postData['entName'], '', $postData['page']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //企业主要人员
    function getStaff(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $page = $this->getRequestData('page', 1);

        $postData = [
            'entName' => $entName,
            'page' => $page . '',
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return YongTaiService::getInstance()
                ->setCheckRespFlag(true)->getStaff($postData['entName'], '', $postData['page']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //搜索
    function getSearch(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $page = $this->getRequestData('page', 1);

        $postData = [
            'entName' => $entName,
            'page' => $page . '',
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return YongTaiService::getInstance()
                ->setCheckRespFlag(true)->getSearch($postData['entName'], $postData['page']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //获取曾用名
    function getHistorynames(): bool
    {
        $entName = $this->getRequestData('entName', '');

        $postData = [
            'entName' => $entName,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return YongTaiService::getInstance()
                ->setCheckRespFlag(true)->getHistorynames($postData['entName']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //获取纳税人识别号
    function getTaxescode(): bool
    {
        $entName = $this->getRequestData('entName', '');

        $postData = [
            'entName' => $entName,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return YongTaiService::getInstance()
                ->setCheckRespFlag(true)->getTaxescode($postData['entName']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //总公司
    function getParentcompany(): bool
    {
        $entName = $this->getRequestData('entName', '');

        $postData = [
            'entName' => $entName,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return YongTaiService::getInstance()
                ->setCheckRespFlag(true)->getParentcompany($postData['entName'], '');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //特殊企业基本信息
    function getEciother(): bool
    {
        $entName = $this->getRequestData('entName', '');

        $postData = [
            'entName' => $entName,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return YongTaiService::getInstance()
                ->setCheckRespFlag(true)->getEciother($postData['entName']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //年报详情
    function getAnnualreport(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $page = $this->getRequestData('page', 1);

        $postData = [
            'entName' => $entName,
            'page' => $page . '',
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return YongTaiService::getInstance()
                ->setCheckRespFlag(true)->getAnnualreport($postData['entName'], '', $postData['page']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //企业基本信息（含联系方式）
    function getBaseinfop(): bool
    {
        $entName = $this->getRequestData('entName', '');

        $postData = [
            'entName' => $entName,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return YongTaiService::getInstance()
                ->setCheckRespFlag(true)->getBaseinfop($postData['entName'], '');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //企业基本信息（含主要人员）
    function getBaseinfos(): bool
    {
        $entName = $this->getRequestData('entName', '');

        $postData = [
            'entName' => $entName,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return YongTaiService::getInstance()
                ->setCheckRespFlag(true)->getBaseinfos($postData['entName'], '');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //特殊企业基本信息
    function getSpecial(): bool
    {
        $entName = $this->getRequestData('entName', '');

        $postData = [
            'entName' => $entName,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return YongTaiService::getInstance()
                ->setCheckRespFlag(true)->getSpecial($postData['entName']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //对外投资
    function getInverst(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $page = $this->getRequestData('page', 1);

        $postData = [
            'entName' => $entName,
            'page' => $page . '',
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return YongTaiService::getInstance()
                ->setCheckRespFlag(true)->getInverst($postData['entName'], '', $postData['page']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //企业三要素验证
    function getComverify(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $fr = $this->getRequestData('fr', '');

        $postData = [
            'entName' => $entName,
            'code' => $code,
            'fr' => $fr,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return YongTaiService::getInstance()
                ->setCheckRespFlag(true)->getComverify($postData['entName'], $postData['code'], $postData['fr']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //企业联系方式
    function getContact(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $page = $this->getRequestData('page', '1');

        $postData = [
            'entName' => $entName,
            'page' => $page . '',
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return YongTaiService::getInstance()
                ->setCheckRespFlag(true)->getContact($postData['entName'], '', $postData['page']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

}



