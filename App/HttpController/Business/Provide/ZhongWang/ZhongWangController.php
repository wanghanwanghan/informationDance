<?php

namespace App\HttpController\Business\Provide\ZhongWang;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\ZhongWang\ZhongWangService;
use EasySwoole\Http\Message\UploadFile;

class ZhongWangController extends ProvideBase
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

    function getInvoiceOcr()
    {
        $imageStr = $this->getRequestData('image','');
        $imageJpg = $this->request()->getUploadedFile('image');

        $image = $imageStr;

        if (empty($imageStr))
        {
            if ($imageJpg instanceof UploadFile)
            {
                $image = base64_encode($imageJpg->getStream()->__toString());
            }
        }

        $this->csp->add($this->cspKey, function () use ($image) {
            return (new ZhongWangService())->setCheckRespFlag(true)->getInvoiceOcr($image);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }


}



