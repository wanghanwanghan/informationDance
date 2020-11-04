<?php

namespace App\HttpController\Business\Api\Export\Word;

use App\HttpController\Business\Api\Export\ExportBase;
use App\HttpController\Models\Api\User;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateTable\CreateTableService;
use App\HttpController\Service\Pay\ChargeService;
use App\HttpController\Service\Report\ReportService;
use Carbon\Carbon;
use wanghanwanghan\someUtils\control;

class WordController extends ExportBase
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

    //生成一个极简报告
    function createVeryEasy()
    {
        $reportNum = $this->createReportNum();

        $phone = $this->request()->getRequestParam('phone') ?? '';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $email = $this->request()->getRequestParam('email') ?? '';

        if (!CommonService::getInstance()->validateEmail($entName)) return $this->writeJson(201, null, null, 'email格式错误');

        try
        {
            $userInfo = User::create()->where('phone',$phone)->get();
            $userInfo->update(['email'=>$email]);

        }catch (\Throwable $e)
        {
            return $this->writeErr($e,__FUNCTION__);
        }

        $charge = ChargeService::getInstance()->VeryEasyReport($this->request(), 210, $reportNum);

        if ($charge['code'] != 200) {
            $code = $charge['code'];
            $paging = $res = null;
            $msg = $charge['msg'];
        } else {
            $code = 200;
            $paging = null;
            $res = ReportService::getInstance()->createVeryEasy($entName, $reportNum, $phone);
            $msg = '极简报告生成中';
        }

        return $this->writeJson($code, $paging, $res, $msg);
    }

    //生成一个简版报告
    function createEasy()
    {
        $reportNum = $this->createReportNum();

        $phone = $this->request()->getRequestParam('phone') ?? '';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $email = $this->request()->getRequestParam('email') ?? '';

        if (!CommonService::getInstance()->validateEmail($entName)) return $this->writeJson(201, null, null, 'email格式错误');

        try
        {
            $userInfo = User::create()->where('phone',$phone)->get();
            $userInfo->update(['email'=>$email]);

        }catch (\Throwable $e)
        {
            return $this->writeErr($e,__FUNCTION__);
        }

        $charge = ChargeService::getInstance()->EasyReport($this->request(), 220, $reportNum);

        if ($charge['code'] != 200) {
            $code = $charge['code'];
            $paging = $res = null;
            $msg = $charge['msg'];
        } else {
            $code = 200;
            $paging = null;
            $res = ReportService::getInstance()->createEasy($entName, $reportNum, $phone);
            $msg = '简版报告生成中';
        }

        return $this->writeJson($code, $paging, $res, $msg);
    }


}