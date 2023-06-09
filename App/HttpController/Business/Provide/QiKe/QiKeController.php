<?php

namespace App\HttpController\Business\Provide\QiKe;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\QiKe\ZhaoTouBiaoService;

class QiKeController extends ProvideBase
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

    function getList(): bool
    {
        $keyword = $this->getRequestData('keyword', '');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $postData = [
            'keyword' => $keyword,
            'page' => $page,
            'size' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new ZhaoTouBiaoService())
                ->setCheckRespFlag(true)
                ->getList(...$postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getDetail(): bool
    {
        $mid = $this->getRequestData('mid', '');

        $this->csp->add($this->cspKey, function () use ($mid) {
            return (new ZhaoTouBiaoService())
                ->setCheckRespFlag(true)
                ->getDetail($mid);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

}