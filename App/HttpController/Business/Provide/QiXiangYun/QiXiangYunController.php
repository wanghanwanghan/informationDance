<?php

namespace App\HttpController\Business\Provide\QiXiangYun;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\QiXiangYun\QiXiangYunService;
use EasySwoole\Http\Message\UploadFile;
use EasySwoole\RedisPool\Redis;

class QiXiangYunController extends ProvideBase
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

    function cySync(): bool
    {
        $fpdm = $this->getRequestData('fpdm');
        $fphm = $this->getRequestData('fphm');
        $kprq = $this->getRequestData('kprq');
        $je = round($this->getRequestData('je'), 2);
        $jym = $this->getRequestData('jym');

        $postData = [
            $fpdm,
            $fphm,
            $kprq,
            $je,
            $jym,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return QiXiangYunService::getInstance()
                ->setCheckRespFlag(true)->cySync(...$postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function ocr(): bool
    {
        $image = $this->getRequestData('image');

        $this->csp->add($this->cspKey, function () use ($image) {
            return QiXiangYunService::getInstance()
                ->setCheckRespFlag(true)->ocr($image);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function createEnt(): bool
    {
        $this->csp->add($this->cspKey, function () {
            return QiXiangYunService::getInstance()
                ->setCheckRespFlag(true)->createEnt();
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getInv(): bool
    {
        $nsrsbh = $this->getRequestData('nsrsbh', '91110108MA01KPGK0L');
        $kpyf = $this->getRequestData('kpyf', '202109');
        $jxxbz = $this->getRequestData('jxxbz', 'jx');
        $fplx = $this->getRequestData('fplx', '01');
        $page = $this->getRequestData('page', '1');

        $postData = [
            $nsrsbh,
            $kpyf,
            $jxxbz,
            $fplx,
            $page,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return QiXiangYunService::getInstance()
                ->setCheckRespFlag(true)->getInv(...$postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getFpxzStatus(): bool
    {
        $nsrsbh = $this->getRequestData('nsrsbh', '91110108MA01KPGK0L');
        $this->csp->add($this->cspKey, function () use ($nsrsbh)  {
            return QiXiangYunService::getInstance()
                ->setCheckRespFlag(true)->getFpxzStatus($nsrsbh);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getCjYgxByFplxs(): bool
    {
        $nsrsbh = $this->getRequestData('nsrsbh', '91110108MA01KPGK0L');
        $skssq = $this->getRequestData('skssq', '202010');
        $postData = [
            'nsrsbh' => $nsrsbh,
            'skssq' => $skssq
        ];
        $this->csp->add($this->cspKey, function () use ($postData)  {
            return QiXiangYunService::getInstance()
                ->setCheckRespFlag(true)->getCjYgxByFplxs($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    public function getGxgxztStatus(): bool
    {
        $nsrsbh = $this->getRequestData('nsrsbh', '91110108MA01KPGK0L');
        $skssq = $this->getRequestData('skssq', '202010');
        $addJob = $this->getRequestData('addJob', true);
        $postData = [
            'nsrsbh' => $nsrsbh,
            'skssq' => $skssq,
            'addJob' => $addJob
        ];
        $this->csp->add($this->cspKey, function () use ($postData)  {
            return QiXiangYunService::getInstance()
                ->setCheckRespFlag(true)->getGxgxztStatus($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }
}



