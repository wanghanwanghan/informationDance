<?php

namespace App\HttpController\Business\Provide\GuoPiao;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\GuoPiao\GuoPiaoService;
use EasySwoole\Http\Message\UploadFile;

class GuoPiaoController extends ProvideBase
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
            $this->responseCode = $res['code'] ?? 500;
            $this->responsePaging = $res['paging'] ?? null;
            $this->responseData = $res['result'] ?? null;
            $this->spendMoney = 0;
            $this->responseMsg = $res['msg'] ?? '请求超时';
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
        $imageStr = $this->getRequestData('image', '');
        $imageJpg = $this->request()->getUploadedFile('image');

        $image = $imageStr;

        if (empty($imageStr) && $imageJpg instanceof UploadFile) {
            $image = base64_encode($imageJpg->getStream()->__toString());
        }

        $size = strlen(base64_decode($image));

        if ($size / 1024 / 1024 > 2) {
            return $this->checkResponse([
                $this->cspKey => '',
                'msg' => '图片大小不能超过2m',
            ]);
        }

        $this->csp->add($this->cspKey, function () use ($image) {
            return (new GuoPiaoService())->setCheckRespFlag(true)->getInvoiceOcr($image);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }


}



