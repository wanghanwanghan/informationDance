<?php

namespace App\HttpController\Business\Api\Export\Word;

use App\HttpController\Business\Api\Export\ExportBase;
use App\HttpController\Models\Api\AuthBook;
use App\HttpController\Models\Api\User;
use App\HttpController\Service\Common\CommonService;
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

    private function createReportNum(): string
    {
        return Carbon::now()->format('YmdHis') . '_' . control::randNum(8);
    }

    //生成一个极简报告
    function createVeryEasy(): bool
    {
        $reportNum = $this->request()->getRequestParam('reportNum') ?? $this->createReportNum();
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $email = $this->request()->getRequestParam('email') ?? '';
        $type = $this->request()->getRequestParam('type') ?? 'xd';
        $pay = $this->request()->getRequestParam('pay') ?? 0;

        if ($email !== '20210425修改') {
            if (!CommonService::getInstance()->validateEmail($email) && $pay == 1) {
                return $this->writeJson(201, null, null, 'email格式错误');
            }
        }

        try {
            $userInfo = User::create()->where('phone', $phone)->get();
            if ($email !== '20210425修改') {
                $pay != 1 || $userInfo->update(['email' => $email]);
            }
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        $charge = ChargeService::getInstance()->VeryEasyReport($this->request(), 210, $reportNum);

        if ($charge['code'] != 200) {
            $code = $charge['code'];
            $paging = $res = null;
            $msg = $charge['msg'];
        } else {
            $code = 200;
            $paging = null;
            $res = ReportService::getInstance()->createVeryEasy($entName, $reportNum, $phone, $type);
            $msg = '极简报告生成中';
        }

        return $this->writeJson($code, $paging, $res, $msg);
    }

    //生成一个简版报告
    function createEasy(): bool
    {
        $reportNum = $this->request()->getRequestParam('reportNum') ?? $this->createReportNum();
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $email = $this->request()->getRequestParam('email') ?? '';
        $type = $this->request()->getRequestParam('type') ?? 'xd';
        $pay = $this->request()->getRequestParam('pay') ?? 0;

        if ($phone == '11111111111') $email = '2129971986@qq.com';

        if ($email !== '20210425修改') {
            if (!CommonService::getInstance()->validateEmail($email) && $pay == 1) {
                return $this->writeJson(201, null, null, 'email格式错误');
            }
        }

        try {
            $userInfo = User::create()->where('phone', $phone)->get();
            if ($email !== '20210425修改') {
                $pay != 1 || $userInfo->update(['email' => $email]);
            }
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        $charge = ChargeService::getInstance()->EasyReport($this->request(), 220, $reportNum);

        if ($charge['code'] != 200) {
            $code = $charge['code'];
            $paging = $res = null;
            $msg = $charge['msg'];
        } else {
            $code = 200;
            $paging = null;
            $res = ReportService::getInstance()->createEasy($entName, $reportNum, $phone, $type);
            $msg = '简版报告生成中';
        }

        return $this->writeJson($code, $paging, $res, $msg);
    }

    //生成一个两表报告
    function createTwoTable(): bool
    {
        $reportNum = $this->request()->getRequestParam('reportNum') ?? $this->createReportNum();
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $email = $this->request()->getRequestParam('email') ?? '';
        $type = $this->request()->getRequestParam('type') ?? 'xd';
        $pay = $this->request()->getRequestParam('pay') ?? 0;

        $check = AuthBook::create()->where([
            'entName' => $entName,
            'phone' => $phone,
            'status' => 3,
        ])->get();

        if (empty($check)) {
            return $this->writeJson(201, null, null, '企业未授权');
        }

        if ($phone == '11111111111') $email = '2129971986@qq.com';

        if ($email !== '20210425修改') {
            if (!CommonService::getInstance()->validateEmail($email) && $pay == 1) {
                return $this->writeJson(201, null, null, 'email格式错误');
            }
        }

        try {
            $userInfo = User::create()->where('phone', $phone)->get();
            if ($email !== '20210425修改') {
                $pay != 1 || $userInfo->update(['email' => $email]);
            }
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        $charge = ChargeService::getInstance()->TwoTableReport($this->request(), 240, $reportNum);

        if ($charge['code'] != 200) {
            $code = $charge['code'];
            $paging = $res = null;
            $msg = $charge['msg'];
        } else {
            $code = 200;
            $paging = null;
            $res = ReportService::getInstance()->createTwoTable($entName, $reportNum, $phone, $type);
            $msg = '税务报告生成中';
        }

        return $this->writeJson($code, $paging, $res, $msg);
    }

    //生成一个深度报告
    function createDeep(): bool
    {
        $reportNum = $this->request()->getRequestParam('reportNum') ?? $this->createReportNum();
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $email = $this->request()->getRequestParam('email') ?? '';
        $type = $this->request()->getRequestParam('type') ?? 'xd';
        $pay = $this->request()->getRequestParam('pay') ?? 0;

        if ($email !== '20210425修改') {
            if (!CommonService::getInstance()->validateEmail($email) && $pay == 1) {
                return $this->writeJson(201, null, null, 'email格式错误');
            }
        }

        try {
            $userInfo = User::create()->where('phone', $phone)->get();
            if ($email !== '20210425修改') {
                $pay != 1 || $userInfo->update(['email' => $email]);
            }
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        $charge = ChargeService::getInstance()->DeepReport($this->request(), 230, $reportNum);

        if ($charge['code'] != 200) {
            $code = $charge['code'];
            $paging = $res = null;
            $msg = $charge['msg'];
        } else {
            $code = 200;
            $paging = null;
            $res = ReportService::getInstance()->createDeep($entName, $reportNum, $phone, $type);
            $msg = '深度报告生成中';
        }

        return $this->writeJson($code, $paging, $res, $msg);
    }

}