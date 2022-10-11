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
            CommonService::getInstance()->log4PHP($res);
            $arr = explode('?url=', $res['data']);
            $res['data'] = 'https://api.meirixindong.com/Static/vertify.html?url=' . $arr[1];
        }

        return $this->writeJson($res['code'], null, $res['data'], $res['message']);
    }

    //企业税务基本信息查询
    function getEssential(): bool
    {
        $code = $this->getRequestData('code');

        $this->csp->add($this->cspKey, function () use ($code) {
            return (new GuoPiaoService())
                ->setCheckRespFlag(true)
                ->getEssential($code);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //企业所得税-月（季）度申报表查询
    function getIncometaxMonthlyDeclaration(): bool
    {
        $code = $this->getRequestData('code');

        $this->csp->add($this->cspKey, function () use ($code) {
            return (new GuoPiaoService())
                ->setCheckRespFlag(true)
                ->getIncometaxMonthlyDeclaration($code);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //企业所得税-年报查询
    function getIncometaxAnnualReport(): bool
    {
        $code = $this->getRequestData('code');

        $res = (new GuoPiaoService())->getIncometaxAnnualReport($code);

        return $this->checkResponse($res);
    }

    //利润表 -- 年报查询
    function getFinanceIncomeStatementAnnualReport(): bool
    {
        $code = $this->getRequestData('code');

        $res = (new GuoPiaoService())->getFinanceIncomeStatementAnnualReport($code);

        //正常
        if ($res['code'] - 0 === 0 && !empty($res['data'])) {
            $data = jsonDecode($res['data']);
            $model = [];
            foreach ($data as $row) {
                $year = substr($row['beginDate'], 0, 4) . '';
                if (!isset($model[$year])) {
                    $model[$year] = [];
                }
                $row['sequence'] = $row['sequence'] - 0;
                $model[$year][] = $row;
            }
            //排序
            foreach ($model as $year => $val) {
                $model[$year] = control::sortArrByKey($val, 'sequence', 'asc', true);
            }
            $res['data'] = jsonEncode($model);
        }

        return $this->checkResponse($res);
    }

    //利润表查询
    function getFinanceIncomeStatement(): bool
    {
        $code = $this->getRequestData('code');

        $this->csp->add($this->cspKey, function () use ($code) {
            return (new GuoPiaoService())
                ->setCheckRespFlag(true)
                ->getFinanceIncomeStatement($code);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //资产负债表 -- 年度查询
    function getFinanceBalanceSheetAnnual(): bool
    {
        $code = $this->getRequestData('code');

        $res = (new GuoPiaoService())->getFinanceBalanceSheetAnnual($code);

        //正常
        if ($res['code'] - 0 === 0 && !empty($res['data'])) {
            $data = jsonDecode($res['data']);
            $model = [];
            foreach ($data as $row) {
                $year = substr($row['beginDate'], 0, 4) . '';
                if (!isset($model[$year])) {
                    $model[$year] = [];
                }
                $row['columnSequence'] = $row['columnSequence'] - 0;
                $model[$year][] = $row;
            }
            //排序
            foreach ($model as $year => $val) {
                $model[$year] = control::sortArrByKey($val, 'columnSequence', 'asc', true);
            }
            $res['data'] = jsonEncode($model);
        }

        return $this->checkResponse($res);
    }

    //资产负债表查询
    function getFinanceBalanceSheet(): bool
    {
        $code = $this->getRequestData('code');

        $res = (new GuoPiaoService())->getFinanceBalanceSheet($code);

        //正常
        if ($res['code'] - 0 === 0 && !empty($res['data'])) {
            $data = jsonDecode($res['data']);
            $model = [];
            foreach ($data as $row) {
                $year_month = substr(str_replace(['-'], '', $row['beginDate']), 0, 6) . '';
                if (!isset($model[$year_month])) {
                    $model[$year_month] = [];
                }
                $row['columnSequence'] = $row['columnSequence'] - 0;
                $model[$year_month][] = $row;
            }
            //排序
            foreach ($model as $year => $val) {
                $model[$year] = control::sortArrByKey($val, 'columnSequence', 'asc', true);
            }
            $res['data'] = jsonEncode($model);
        }

        return $this->checkResponse($res);
    }

    //增值税申报表查询
    function getVatReturn(): bool
    {
        $code = $this->getRequestData('code');

        $this->csp->add($this->cspKey, function () use ($code) {
            return (new GuoPiaoService())
                ->setCheckRespFlag(true)
                ->getVatReturn($code);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //进销项发票信息 信动专用
    function getInvoiceMain(): bool
    {
        $code = $this->getRequestData('code');
        $dataType = $this->getRequestData('dataType');
        $startDate = $this->getRequestData('startDate');//date('Y-m-d')
        $endDate = $this->getRequestData('endDate');//date('Y-m-d')
        $page = $this->getRequestData('page');

        if (empty($code) || empty($startDate) || empty($endDate))
            return $this->writeJson(201, null, null, '参数不能是空');

        if (!is_numeric($dataType) || !is_numeric($page))
            return $this->writeJson(201, null, null, '参数必须是数字');

        $res = (new GuoPiaoService())->getInvoiceMain($code, $dataType, $startDate, $endDate, $page);

        return $this->checkResponse($res);
    }

    //进销项发票商品明细 信动专用
    function getInvoiceGoods(): bool
    {
        $code = $this->getRequestData('code');
        $dataType = $this->getRequestData('dataType');
        $startDate = $this->getRequestData('startDate');//date('Y-m-d')
        $endDate = $this->getRequestData('endDate');//date('Y-m-d')
        $page = $this->getRequestData('page');

        if (empty($code) || empty($startDate) || empty($endDate))
            return $this->writeJson(201, null, null, '参数不能是空');

        if (!is_numeric($dataType) || !is_numeric($page))
            return $this->writeJson(201, null, null, '参数必须是数字');

        $res = (new GuoPiaoService())->getInvoiceGoods($code, $dataType, $startDate, $endDate, $page);

        return $this->checkResponse($res);
    }

}



