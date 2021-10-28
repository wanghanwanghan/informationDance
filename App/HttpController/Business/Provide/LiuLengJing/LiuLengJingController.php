<?php

namespace App\HttpController\Business\Provide\LiuLengJing;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\LiuLengJing\LiuLengJingService;
use App\HttpController\Service\QiXiangYun\QiXiangYunService;
use EasySwoole\Http\Message\UploadFile;
use EasySwoole\RedisPool\Redis;

class LiuLengJingController extends ProvideBase
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

    function patentCnBasics(): bool
    {
        $ans = $this->getRequestData('ans', '');
        $page = $this->getRequestData('page', 1);

        $postData = [
            'pageNum' => $page - 0,
            'pageSize' => 10,
        ];

        if (!empty($ans)) $postData['ans'] = $ans . '';

        $this->csp->add($this->cspKey, function () use ($postData) {
            return LiuLengJingService::getInstance()
                ->setCheckRespFlag(true)->patentCnBasics($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

}



