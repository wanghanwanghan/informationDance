<?php

namespace App\HttpController\Service\Pay;

use App\HttpController\Models\Api\Charge;
use App\HttpController\Models\Api\Wallet;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;
use EasySwoole\Http\Request;

class ChargeService extends ServiceBase
{
    use Singleton;

    private $moduleInfo = [
        0 => ['name' => '财务资产', 'desc' => '详情', 'basePrice' => 35],
        1 => ['name' => '开庭公告', 'desc' => '详情', 'basePrice' => 1],
        2 => ['name' => '裁判文书', 'desc' => '详情', 'basePrice' => 1],
        3 => ['name' => '法院公告', 'desc' => '详情', 'basePrice' => 1],
        4 => ['name' => '执行公告', 'desc' => '详情', 'basePrice' => 1],
        5 => ['name' => '失信公告', 'desc' => '详情', 'basePrice' => 1],
        6 => ['name' => '司法查封冻结扣押', 'desc' => '详情', 'basePrice' => 1],
        7 => ['name' => '司法拍卖', 'desc' => '详情', 'basePrice' => 1],
        8 => ['name' => '欠税公告', 'desc' => '详情', 'basePrice' => 1],
        9 => ['name' => '涉税处罚公示', 'desc' => '详情', 'basePrice' => 1],
        10 => ['name' => '税务非正常户公示', 'desc' => '详情', 'basePrice' => 1],
        11 => ['name' => '纳税信用等级', 'desc' => '详情', 'basePrice' => 1],
        12 => ['name' => '税务登记', 'desc' => '详情', 'basePrice' => 1],
        13 => ['name' => '税务许可', 'desc' => '详情', 'basePrice' => 1],
        200 => ['name' => '简版报告', 'desc' => '', 'basePrice' => 400],
    ];

    private function getEntName(Request $request)
    {
        return trim($request->getRequestParam('entName'));
    }

    private function getPhone(Request $request)
    {
        return trim($request->getRequestParam('phone'));
    }

    private function getModuleInfo(int $index = 500): array
    {
        if ($index == 500) return $this->moduleInfo;

        return $this->moduleInfo[$index];
    }

    //乾启计费
    function QianQi(Request $request, $moduleNum): array
    {
        $phone = $this->getPhone($request);

        $entName = $this->getEntName($request);

        if (empty($phone) || empty($entName)) return ['code' => 201, 'msg' => '手机号或公司名不能是空'];

        //是否免费
        try {
            $charge = Charge::create()
                ->where('phone', $phone)
                ->where('entName', $entName)
                ->where('detailKey', '')
                ->where('moduleId', $moduleNum)
                ->order('created_at', 'desc')
                ->get();
        } catch (\Throwable $e) {
            $this->writeErr($e, __CLASS__);
        }

        //获取扣费详情
        $moduleInfo = $this->getModuleInfo($moduleNum);

        if (!empty($charge)) {
            //取出上次计费时间
            $time = $charge->created_at;
            //缓存过期时间
            $limitDay = CreateConf::getInstance()->getConf('qianqi.chargeLimit');
            //还在免费状态
            if (time() - $time < $limitDay * 86400) {
                //写入记录
                try {
                    $insert = [
                        'moduleId' => $moduleNum,
                        'moduleName' => $moduleInfo['name'] . $moduleInfo['desc'],
                        'entName' => $entName,
                        'phone' => $phone,
                        'price' => 0,
                    ];
                    Charge::create()->data($insert, false)->save();
                } catch (\Throwable $e) {
                    $this->writeErr($e, __CLASS__);
                }
                return ['code' => 200];
            };
        }

        try {
            //取得用户钱包余额
            $info = Wallet::create()->where('phone', $phone)->get();
            if (empty($info)) return ['code' => 201, 'msg' => '无用户钱包信息'];
            $userMoney = $info->money;
        } catch (\Throwable $e) {
            $this->writeErr($e, __CLASS__);
            return ['code' => 201, 'msg' => '取得用户钱包信息失败'];
        }

        if ($userMoney < $moduleInfo['basePrice']) return ['code' => 201, 'msg' => '用户余额不足'];

        try {
            //扣费
            $money = $userMoney - $moduleInfo['basePrice'];
            $info->update(['money' => $money > 0 ? $money : 0]);
        } catch (\Throwable $e) {
            $this->writeErr($e, __CLASS__);
            return ['code' => 201, 'msg' => '扣费失败'];
        }

        //写入记录
        try {
            $insert = [
                'moduleId' => $moduleNum,
                'moduleName' => $moduleInfo['name'] . $moduleInfo['desc'],
                'entName' => $entName,
                'phone' => $phone,
                'price' => $moduleInfo['basePrice'],
            ];
            Charge::create()->data($insert, false)->save();
        } catch (\Throwable $e) {
            $this->writeErr($e, __CLASS__);
        }

        return ['code' => 200, 'msg' => '扣费成功'];
    }

    //法海计费
    function FaHai(Request $request, $moduleNum, $entName): array
    {
        $id = $request->getRequestParam('id');

        $phone = $this->getPhone($request);

        if (empty($phone) || empty($entName) || empty($id)) return ['code' => 201, 'msg' => '手机号或公司名或id不能是空'];

        //是否免费
        try {
            $charge = Charge::create()
                ->where('phone', $phone)
                ->where('entName', $entName)
                ->where('detailKey', $id)
                ->where('moduleId', $moduleNum)
                ->order('created_at', 'desc')
                ->get();
        } catch (\Throwable $e) {
            $this->writeErr($e, __CLASS__);
        }

        //获取扣费详情
        $moduleInfo = $this->getModuleInfo($moduleNum);

        if (!empty($charge)) {
            //取出上次计费时间
            $time = $charge->created_at;
            //缓存过期时间
            $limitDay = CreateConf::getInstance()->getConf('fahai.chargeLimit');
            //还在免费状态
            if (time() - $time < $limitDay * 86400) {
                //写入记录
                try {
                    $insert = [
                        'moduleId' => $moduleNum,
                        'moduleName' => $moduleInfo['name'] . $moduleInfo['desc'],
                        'entName' => $entName,
                        'detailKey' => $id,
                        'phone' => $phone,
                        'price' => 0,
                    ];
                    Charge::create()->data($insert, false)->save();
                } catch (\Throwable $e) {
                    $this->writeErr($e, __CLASS__);
                }
                return ['code' => 200];
            };
        }

        try {
            //取得用户钱包余额
            $info = Wallet::create()->where('phone', $phone)->get();
            if (empty($info)) return ['code' => 201, 'msg' => '无用户钱包信息'];
            $userMoney = $info->money;
        } catch (\Throwable $e) {
            $this->writeErr($e, __CLASS__);
            return ['code' => 201, 'msg' => '取得用户钱包信息失败'];
        }

        if ($userMoney < $moduleInfo['basePrice']) return ['code' => 201, 'msg' => '用户余额不足'];

        try {
            //扣费
            $money = $userMoney - $moduleInfo['basePrice'];
            $info->update(['money' => $money > 0 ? $money : 0]);
        } catch (\Throwable $e) {
            $this->writeErr($e, __CLASS__);
            return ['code' => 201, 'msg' => '扣费失败'];
        }

        //写入记录
        try {
            $insert = [
                'moduleId' => $moduleNum,
                'moduleName' => $moduleInfo['name'] . $moduleInfo['desc'],
                'entName' => $entName,
                'detailKey' => $id,
                'phone' => $phone,
                'price' => $moduleInfo['basePrice'],
            ];
            Charge::create()->data($insert, false)->save();
        } catch (\Throwable $e) {
            $this->writeErr($e, __CLASS__);
        }

        return ['code' => 200, 'msg' => '扣费成功'];
    }

    //企查查计费
    function QiChaCha(Request $request, $moduleNum, $entName): array
    {
        $id = $request->getRequestParam('id');

        $phone = $this->getPhone($request);

        if (empty($phone) || empty($entName) || empty($id)) return ['code' => 201, 'msg' => '手机号或公司名或id不能是空'];

        //是否免费
        try {
            $charge = Charge::create()
                ->where('phone', $phone)
                ->where('entName', $entName)
                ->where('detailKey', $id)
                ->where('moduleId', $moduleNum)
                ->order('created_at', 'desc')
                ->get();
        } catch (\Throwable $e) {
            $this->writeErr($e, __CLASS__);
        }

        //获取扣费详情
        $moduleInfo = $this->getModuleInfo($moduleNum);

        if (!empty($charge)) {
            //取出上次计费时间
            $time = $charge->created_at;
            //缓存过期时间
            $limitDay = CreateConf::getInstance()->getConf('qichacha.chargeLimit');
            //还在免费状态
            if (time() - $time < $limitDay * 86400) {
                //写入记录
                try {
                    $insert = [
                        'moduleId' => $moduleNum,
                        'moduleName' => $moduleInfo['name'] . $moduleInfo['desc'],
                        'entName' => $entName,
                        'detailKey' => $id,
                        'phone' => $phone,
                        'price' => 0,
                    ];
                    Charge::create()->data($insert, false)->save();
                } catch (\Throwable $e) {
                    $this->writeErr($e, __CLASS__);
                }
                return ['code' => 200];
            };
        }

        try {
            //取得用户钱包余额
            $info = Wallet::create()->where('phone', $phone)->get();
            if (empty($info)) return ['code' => 201, 'msg' => '无用户钱包信息'];
            $userMoney = $info->money;
        } catch (\Throwable $e) {
            $this->writeErr($e, __CLASS__);
            return ['code' => 201, 'msg' => '取得用户钱包信息失败'];
        }

        if ($userMoney < $moduleInfo['basePrice']) return ['code' => 201, 'msg' => '用户余额不足'];

        try {
            //扣费
            $money = $userMoney - $moduleInfo['basePrice'];
            $info->update(['money' => $money > 0 ? $money : 0]);
        } catch (\Throwable $e) {
            $this->writeErr($e, __CLASS__);
            return ['code' => 201, 'msg' => '扣费失败'];
        }

        //写入记录
        try {
            $insert = [
                'moduleId' => $moduleNum,
                'moduleName' => $moduleInfo['name'] . $moduleInfo['desc'],
                'entName' => $entName,
                'detailKey' => $id,
                'phone' => $phone,
                'price' => $moduleInfo['basePrice'],
            ];
            Charge::create()->data($insert, false)->save();
        } catch (\Throwable $e) {
            $this->writeErr($e, __CLASS__);
        }

        return ['code' => 200, 'msg' => '扣费成功'];
    }

    //简版报告
    function EasyReport(Request $request, $moduleNum,$reportNum)
    {
        $phone = $this->getPhone($request);

        $entName = $this->getEntName($request);

        if (empty($phone) || empty($entName)) return ['code' => 201, 'msg' => '手机号或公司名不能是空'];

        //获取扣费详情
        $moduleInfo = $this->getModuleInfo($moduleNum);

        try {
            //取得用户钱包余额
            $info = Wallet::create()->where('phone', $phone)->get();
            if (empty($info)) return ['code' => 201, 'msg' => '无用户钱包信息'];
            $userMoney = $info->money;
        } catch (\Throwable $e) {
            $this->writeErr($e, __CLASS__);
            return ['code' => 201, 'msg' => '取得用户钱包信息失败'];
        }

        if ($userMoney < $moduleInfo['basePrice']) return ['code' => 201, 'msg' => '用户余额不足'];

        try {
            //扣费
            $money = $userMoney - $moduleInfo['basePrice'];
            $info->update(['money' => $money > 0 ? $money : 0]);
        } catch (\Throwable $e) {
            $this->writeErr($e, __CLASS__);
            return ['code' => 201, 'msg' => '扣费失败'];
        }

        //写入记录
        try {
            $insert = [
                'moduleId' => $moduleNum,
                'moduleName' => $moduleInfo['name'] . $moduleInfo['desc'],
                'entName' => $entName,
                'detailKey' => $reportNum,
                'phone' => $phone,
                'price' => $moduleInfo['basePrice'],
            ];
            Charge::create()->data($insert, false)->save();
        } catch (\Throwable $e) {
            $this->writeErr($e, __CLASS__);
        }

        return ['code' => 200, 'msg' => '扣费成功'];
    }





}
