<?php

namespace App\HttpController\Business\Provide\QiXiangYun;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\QiXiangYun\QiXiangYunService;
use EasySwoole\Http\Message\UploadFile;

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
        $image = $this->request()->getUploadedFile('image');

        if ($image instanceof UploadFile) {
            $content = base64_encode($image->getStream()->__toString());
        } else {
            $content = '';
        }

        $this->csp->add($this->cspKey, function () use ($content) {
            return QiXiangYunService::getInstance()
                ->setCheckRespFlag(true)->ocr($content);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }


}



