<?php

namespace App\HttpController\Business\Provide\GuoPiao;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Models\Api\AuthBook;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\GuoPiao\GuoPiaoService;
use EasySwoole\Http\Message\UploadFile;
use wanghanwanghan\someUtils\control;

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

    function checkResponse($res): bool
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

    function getInvoiceOcr(): bool
    {
        $imageStr = $this->getRequestData('image', '');
        $imageJpg = $this->request()->getUploadedFile('image');

        $image = $imageStr;

        if (empty($imageStr) && $imageJpg instanceof UploadFile) {
            $image = base64_encode($imageJpg->getStream()->__toString());
        }

        $size = strlen(base64_decode($image));

        if ($size / 1024 / 1024 > 3) {
            return $this->checkResponse([
                $this->cspKey => '',
                'msg' => '图片大小不能超过3m',
            ]);
        }

        $this->csp->add($this->cspKey, function () use ($image) {
            return (new GuoPiaoService())->setCheckRespFlag(true)->getInvoiceOcr($image);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getInvoiceCheck(): bool
    {
        $postData = [
            'invoiceCode' => $this->getRequestData('invoiceCode'),
            'invoiceNumber' => $this->getRequestData('invoiceNumber'),
            'billingDate' => $this->getRequestData('billingDate'),
            'totalAmount' => $this->getRequestData('totalAmount'),
            'checkCode' => $this->getRequestData('checkCode'),
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new GuoPiaoService())->setCheckRespFlag(true)->getInvoiceCheck($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getAuthentication(): bool
    {
        $appId = $this->getRequestData('appId', '');
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $callback = $this->getRequestData('callback', 'https://pc.meirixindong.com/');

        $orderNo = control::getUuid(20);

        $res = (new GuoPiaoService())->getAuthentication($entName, $callback, $orderNo);

        $res = jsonDecode($res);

        !(isset($res['code']) && $res['code'] == 0) ?: $res['code'] = 200;

        //添加授权信息
        try {
            $check = AuthBook::create()->where([
                'phone' => $appId, 'entName' => $entName, 'code' => $code, 'type' => 2
            ])->get();
            if (empty($check)) {
                AuthBook::create()->data([
                    'phone' => $appId,
                    'entName' => $entName,
                    'code' => $code,
                    'status' => 1,
                    'type' => 2,//深度报告，发票数据
                    'remark' => $orderNo
                ])->save();
            } else {
                $check->update([
                    'phone' => $appId,
                    'entName' => $entName,
                    'code' => $code,
                    'status' => 1,
                    'type' => 2,
                    'remark' => $orderNo
                ]);
            }
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        if (strpos($res['data'], '?url=')) {
            $arr = explode('?url=', $res['data']);
            $res['data'] = 'https://api.meirixindong.com/Static/vertify.html?url=' . $arr[1];
        }

        return $this->writeJson($res['code'], null, $res['data'], $res['message']);
    }

    function getFinanceIncomeStatementAnnualReport(): bool
    {
        $code = $this->getRequestData('code');

        $res = (new GuoPiaoService())->setCheckRespFlag(true)->getFinanceIncomeStatementAnnualReport($code);

        return $this->checkResponse($res);
    }

    function getFinanceIncomeStatement(): bool
    {
        $code = $this->getRequestData('code');

        $res = (new GuoPiaoService())->setCheckRespFlag(true)->getFinanceIncomeStatement($code);

        return $this->checkResponse($res);
    }

    function getFinanceBalanceSheetAnnual(): bool
    {
        $code = $this->getRequestData('code');

        $res = (new GuoPiaoService())->setCheckRespFlag(true)->getFinanceBalanceSheetAnnual($code);

        return $this->checkResponse($res);
    }

    function getFinanceBalanceSheet(): bool
    {
        $code = $this->getRequestData('code');

        $res = (new GuoPiaoService())->setCheckRespFlag(true)->getFinanceBalanceSheet($code);

        return $this->checkResponse($res);
    }

}



