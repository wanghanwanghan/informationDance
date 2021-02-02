<?php

namespace App\HttpController\Business\Api\Export\Pdf;

use App\HttpController\Business\Api\Export\ExportBase;
use App\HttpController\Models\Api\User;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateTable\CreateTableService;
use App\HttpController\Service\Pay\ChargeService;
use App\HttpController\Service\Report\ReportService;
use Carbon\Carbon;
use wanghanwanghan\someUtils\control;

class PdfController extends ExportBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    private function createReportNum()
    {
        return Carbon::now()->format('YmdHis') . '_' . control::randNum(8);
    }

    //生成一个简版报告
    function createEasy()
    {
        $reportNum = $this->request()->getRequestParam('reportNum') ?? $this->createReportNum();
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $email = $this->request()->getRequestParam('email') ?? '';
        $type = $this->request()->getRequestParam('type') ?? 'xd';
        $pay = $this->request()->getRequestParam('pay') ?? 0;
        $dataKey = $this->request()->getRequestParam('dataKey') ?? '';

        if (!CommonService::getInstance()->validateEmail($email) && $pay == 1) {
            return $this->writeJson(201, null, null, 'email格式错误');
        }

        try {
            $userInfo = User::create()->where('phone', $phone)->get();
            $pay != 1 ?: $userInfo->update(['email' => $email]);
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        $charge = ChargeService::getInstance()->EasyReportCustomized($this->request(), 221, $reportNum);

        if ($charge['code'] != 200) {
            $code = $charge['code'];
            $paging = $res = null;
            $msg = $charge['msg'];
        } else {
            $code = 200;
            $paging = null;
            $res = ReportService::getInstance()->createEasyPdf($entName, $reportNum, $phone, $type, $dataKey);
            $msg = '简版报告定制版生成中';
        }

        return $this->writeJson($code, $paging, $res, $msg);
    }





}