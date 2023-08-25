<?php

namespace App\HttpController\Business\Provide\Notify;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use Carbon\Carbon;
use wanghanwanghan\someUtils\control;

class NotifyController extends ProvideBase
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
            $this->responseMsg = empty($this->responseMsg) ? '请求超时' : $this->responseMsg;
        } else {
            $this->responseCode = $res[$this->cspKey]['code'];
            $this->responsePaging = $res[$this->cspKey]['paging'];
            $this->responseData = $res[$this->cspKey]['result'];
            $this->responseMsg = $res[$this->cspKey]['msg'];

            $res[$this->cspKey]['code'] === 200 ?: $this->spendMoney = 0;
        }

        return true;
    }

    function creditAuthUrl(): bool
    {
        $data[] = $this->getRequestData('pdfname');
        $data[] = $this->getRequestData('pdfurl');

        $this->csp->add($this->cspKey, function () use ($data) {
            return [
                'code' => 666,
                'paging' => null,
                'result' => $this->requestData,
                'msg' => control::getUuid(),
            ];
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function selectPhone_hy(): bool
    {
        $url = $this->getRequestData('url');

        $this->csp->add($this->cspKey, function () use ($url) {
            $stream = file_get_contents($url);
            $filename = Carbon::now()->format('Ymd') . '_' . control::getUuid(8);
            file_put_contents(OTHER_FILE_PATH . date('Y') . '/' . $filename, $stream);
            return [
                'code' => 200,
                'paging' => null,
                'result' => $url,
                'msg' => control::getUuid(),
            ];
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

}