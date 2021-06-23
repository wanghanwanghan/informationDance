<?php

namespace App\HttpController\Service\Pay;

use App\HttpController\Models\Api\Charge;
use App\HttpController\Models\Api\User;
use App\HttpController\Models\Api\Wallet;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\ServiceBase;
use Carbon\Carbon;
use EasySwoole\Component\Singleton;
use EasySwoole\Http\Request;
use EasySwoole\ORM\DbManager;

class ChargeService extends ServiceBase
{
    use Singleton;

    //最高255
    private $moduleInfo = [
        0 => ['name' => '财务资产', 'desc' => 'qq', 'basePrice' => 35],
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
        14 => ['name' => '实际控制人和控制路径', 'desc' => '', 'basePrice' => 16],
        50 => ['name' => '风险监控', 'desc' => '', 'basePrice' => 50],
        51 => ['name' => '财务资产', 'desc' => 'lx', 'basePrice' => 35],
        52 => ['name' => '二次特征', 'desc' => '', 'basePrice' => 50],
        53 => ['name' => '超级搜索', 'desc' => '', 'basePrice' => 10],

        210 => ['name' => '极简报告', 'desc' => '', 'basePrice' => 80],
        211 => ['name' => '极简报告定制', 'desc' => '', 'basePrice' => 80],
        220 => ['name' => '简版报告', 'desc' => '', 'basePrice' => 300],
        221 => ['name' => '简版报告定制', 'desc' => '炜衡', 'basePrice' => 300],
        230 => ['name' => '深度报告', 'desc' => '', 'basePrice' => 500],
        231 => ['name' => '深度报告定制', 'desc' => '', 'basePrice' => 500],
    ];

    private function getEntName(Request $request)
    {
        return trim($request->getRequestParam('entName'));
    }

    private function getPhone(Request $request)
    {
        return trim($request->getRequestParam('phone'));
    }

    private function getPay(Request $request)
    {
        $pay = $request->getRequestParam('pay') ?? 0;

        if ($pay === 'false' || $pay === false || $pay === 0 || $pay === '0' || empty($pay)) {
            $pay = 0;
        } else {
            $pay = 1;
        }

        return $pay;
    }

    private function getModuleInfo(int $index = 500): array
    {
        if ($index == 500) return $this->moduleInfo;

        return $this->moduleInfo[$index];
    }

    //退钱到钱包
    function refundToWallet(Request $request, $moduleNum)
    {
        $phone = $this->getPhone($request);
        $entName = $this->getEntName($request);

        if (empty($phone) || !is_numeric($phone)) return ['code' => 201, 'msg' => '手机号错误'];
        if (empty($entName)) return ['code' => 201, 'msg' => '企业名称错误'];
        if (empty($moduleNum) || !is_numeric($moduleNum)) return ['code' => 201, 'msg' => '扣费模块错误'];

        try {
            $info = Charge::create()
                ->where('phone', $phone)
                ->where('entName', $entName)
                ->where('moduleId', $moduleNum)
                ->where('created_at', time() - 5, '>')//首先要看这个人在5秒之前有没有真的消费并扣钱
                ->where('price', 0, '<')//是否有被扣费
                ->get();

            if (empty($info)) return ['code' => 201, 'msg' => '未找到订单'];

            //用户消费的金额，需要加回钱包里
            $addPrice = abs($info->price);

            //修改订单金额
            $info->update([
                'price' => 0,
                'remark' => '已退款',
            ]);

            //把扣的钱返回
            $userWalletInfo = Wallet::create()->where('phone', $phone)->get();

            $userWalletInfo->money += $addPrice;

            $userWalletInfo->update();

        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        switch ($moduleNum) {
            case 14:
                $msg = '因穿透股东中有政府部门或国资单位等特殊机构，故不予显示，退款成功';
                break;
            case 51:
                $msg = '财务数据最近3年数据为空，故不予显示，退款成功';
                break;
            default:
                $msg = '退款成功';
        }

        return ['code' => 200, 'msg' => $msg];
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
                ->where('price', 0, '<')
                ->order('created_at', 'desc')
                ->get();
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
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
                    Charge::create()->data($insert)->save();
                } catch (\Throwable $e) {
                    $this->writeErr($e, 'ChargeService');
                }
                return ['code' => 200];
            }
        }

        //等于false说明用户还没点确定支付，等于true说明用户点了确认支付
        $pay = $this->getPay($request);

        if (!$pay) return ['code' => 210, 'msg' => "此信息需消耗 {$moduleInfo['basePrice']} 元，有效期 7 天"];

        try {
            //取得用户钱包余额
            $info = Wallet::create()->where('phone', $phone)->get();
            if (empty($info)) return ['code' => 201, 'msg' => '无用户钱包信息'];
            $userMoney = $info->money;
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
            return ['code' => 201, 'msg' => '取得用户钱包信息失败'];
        }

        if ($userMoney < $moduleInfo['basePrice']) return ['code' => 220, 'msg' => '用户余额不足'];

        try {
            //扣费
            $money = $userMoney - $moduleInfo['basePrice'];
            $info->update(['money' => $money > 0 ? $money : 0]);
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
            return ['code' => 201, 'msg' => '扣费失败'];
        }

        //写入记录
        try {
            $insert = [
                'moduleId' => $moduleNum,
                'moduleName' => $moduleInfo['name'] . $moduleInfo['desc'],
                'entName' => $entName,
                'phone' => $phone,
                'price' => -$moduleInfo['basePrice'],
            ];
            Charge::create()->data($insert)->save();
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
        }

        return ['code' => 200, 'msg' => '扣费成功'];
    }

    //龙信计费
    function LongXin(Request $request, $moduleNum, $entName = null): array
    {
        $phone = $this->getPhone($request);

        if (empty($entName)) {
            $entName = $this->getEntName($request);
        }

        if (empty($phone) || empty($entName)) return ['code' => 201, 'msg' => '手机号或公司名不能是空'];

        //获取扣费详情
        $moduleInfo = $this->getModuleInfo($moduleNum);

        //51号前5次是免费直接免费的
        if ($moduleNum === 51) {
            try {
                $num = Charge::create()
                    ->where('phone', $phone)
                    ->where('moduleId', $moduleNum)
                    ->group('entName')
                    ->all();
                empty($num) ? $num = 0 : $num = count(obj2Arr($num));
                if ($num <= 5) {
                    $insert = [
                        'moduleId' => $moduleNum,
                        'moduleName' => $moduleInfo['name'] . $moduleInfo['desc'],
                        'entName' => $entName,
                        'phone' => $phone,
                        'price' => 0,
                    ];
                    Charge::create()->data($insert)->save();
                    return ['code' => 200, 'msg' => '新用户免费'];
                }
            } catch (\Throwable $e) {
                $this->writeErr($e, 'ChargeService');
            }
        }

        //是否免费
        try {
            $charge = Charge::create()
                ->where('phone', $phone)
                ->where('entName', $entName)
                ->where('detailKey', '')
                ->where('moduleId', $moduleNum)
                ->where('price', 0, '<')
                ->order('created_at', 'desc')
                ->get();
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
        }

        if (!empty($charge)) {
            //取出上次计费时间
            $time = $charge->created_at;
            //缓存过期时间
            $limitDay = CreateConf::getInstance()->getConf('longxin.chargeLimit');
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
                    Charge::create()->data($insert)->save();
                } catch (\Throwable $e) {
                    $this->writeErr($e, 'ChargeService');
                }
                return ['code' => 200];
            }
        }

        //等于false说明用户还没点确定支付，等于true说明用户点了确认支付
        $pay = $this->getPay($request);

        if (!$pay) return ['code' => 210, 'msg' => "此信息需消耗 {$moduleInfo['basePrice']} 元，有效期 7 天"];

        try {
            //取得用户钱包余额
            $info = Wallet::create()->where('phone', $phone)->get();
            if (empty($info)) return ['code' => 201, 'msg' => '无用户钱包信息'];
            $userMoney = $info->money;
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
            return ['code' => 201, 'msg' => '取得用户钱包信息失败'];
        }

        if ($userMoney < $moduleInfo['basePrice']) return ['code' => 220, 'msg' => '用户余额不足'];

        try {
            //扣费
            $money = $userMoney - $moduleInfo['basePrice'];
            $info->update(['money' => $money > 0 ? $money : 0]);
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
            return ['code' => 201, 'msg' => '扣费失败'];
        }

        //写入记录
        try {
            $insert = [
                'moduleId' => $moduleNum,
                'moduleName' => $moduleInfo['name'] . $moduleInfo['desc'],
                'entName' => $entName,
                'phone' => $phone,
                'price' => -$moduleInfo['basePrice'],
            ];
            Charge::create()->data($insert)->save();
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
        }

        return ['code' => 200, 'msg' => '扣费成功'];
    }

    //法研院计费
    function FaYanYuan(Request $request, $moduleNum, $entName): array
    {
        $id = $request->getRequestParam('id');

        $phone = $this->getPhone($request);

        if (empty($moduleNum) || !is_numeric($moduleNum)) return ['code' => 999, 'msg' => '暂时免费'];

        if (empty($phone) || empty($entName) || empty($id)) return ['code' => 201, 'msg' => '手机号或公司名或id不能是空'];

        //是否免费
        try {
            $charge = Charge::create()
                ->where('phone', $phone)
                ->where('entName', $entName)
                ->where('detailKey', $id)
                ->where('moduleId', $moduleNum)
                ->where('price', 0, '<')
                ->order('created_at', 'desc')
                ->get();
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
        }

        //获取扣费详情
        $moduleInfo = $this->getModuleInfo($moduleNum);

        if (!empty($charge)) {
            //取出上次计费时间
            $time = $charge->created_at;
            //缓存过期时间
            $limitDay = CreateConf::getInstance()->getConf('fayanyuan.chargeLimit');
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
                    Charge::create()->data($insert)->save();
                } catch (\Throwable $e) {
                    $this->writeErr($e, 'ChargeService');
                }
                return ['code' => 200];
            }
        }

        //等于false说明用户还没点确定支付，等于true说明用户点了确认支付
        $pay = $this->getPay($request);

        if (!$pay) return ['code' => 210, 'msg' => "此信息需消耗 {$moduleInfo['basePrice']} 元，有效期 7 天"];

        try {
            //取得用户钱包余额
            $info = Wallet::create()->where('phone', $phone)->get();
            if (empty($info)) return ['code' => 201, 'msg' => '无用户钱包信息'];
            $userMoney = $info->money;
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
            return ['code' => 201, 'msg' => '取得用户钱包信息失败'];
        }

        if ($userMoney < $moduleInfo['basePrice']) return ['code' => 220, 'msg' => '用户余额不足'];

        try {
            //扣费
            $money = $userMoney - $moduleInfo['basePrice'];
            $info->update(['money' => $money > 0 ? $money : 0]);
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
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
                'price' => -$moduleInfo['basePrice'],
            ];
            Charge::create()->data($insert)->save();
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
        }

        return ['code' => 200, 'msg' => '扣费成功'];
    }

    //龙盾计费
    function LongDun(Request $request, $moduleNum, $entName): array
    {
        $id = $request->getRequestParam('id') ?? '';

        $phone = $this->getPhone($request);

        if (empty($phone) || empty($entName)) return ['code' => 201, 'msg' => '手机号或公司名不能是空'];

        //是否免费
        try {
            $charge = Charge::create()
                ->where('phone', $phone)
                ->where('entName', $entName)
                ->where('detailKey', $id)
                ->where('moduleId', $moduleNum)
                ->where('price', 0, '<')
                ->order('created_at', 'desc')
                ->get();
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
        }

        //获取扣费详情
        $moduleInfo = $this->getModuleInfo($moduleNum);

        if (!empty($charge)) {
            //取出上次计费时间
            $time = $charge->created_at;
            //缓存过期时间
            $limitDay = CreateConf::getInstance()->getConf('longdun.chargeLimit');
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
                    Charge::create()->data($insert)->save();
                } catch (\Throwable $e) {
                    $this->writeErr($e, 'ChargeService');
                }
                return ['code' => 200];
            }
        }

        //等于false说明用户还没点确定支付，等于true说明用户点了确认支付
        $pay = $this->getPay($request);

        if (!$pay) return ['code' => 210, 'msg' => "此信息需消耗 {$moduleInfo['basePrice']} 元，有效期 7 天"];

        try {
            //取得用户钱包余额
            $info = Wallet::create()->where('phone', $phone)->get();
            if (empty($info)) return ['code' => 201, 'msg' => '无用户钱包信息'];
            $userMoney = $info->money;
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
            return ['code' => 201, 'msg' => '取得用户钱包信息失败'];
        }

        if ($userMoney < $moduleInfo['basePrice']) return ['code' => 220, 'msg' => '用户余额不足'];

        try {
            //扣费
            $money = $userMoney - $moduleInfo['basePrice'];
            $info->update(['money' => $money > 0 ? $money : 0]);
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
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
                'price' => -$moduleInfo['basePrice'],
            ];
            Charge::create()->data($insert)->save();
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
        }

        return ['code' => 200, 'msg' => '扣费成功'];
    }

    //风险监控
    function Supervisor(Request $request, $moduleNum)
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
                ->where('price', 0, '<')
                ->order('created_at', 'desc')
                ->get();
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
        }

        //获取扣费详情
        $moduleInfo = $this->getModuleInfo($moduleNum);

        if (!empty($charge)) {
            //取出上次计费时间
            $time = $charge->created_at;
            //缓存过期时间
            $limitDay = CreateConf::getInstance()->getConf('supervisor.chargeLimit');
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
                    Charge::create()->data($insert)->save();
                } catch (\Throwable $e) {
                    $this->writeErr($e, 'ChargeService');
                }
                return ['code' => 200];
            }
        }

        //等于false说明用户还没点确定支付，等于true说明用户点了确认支付
        $pay = $this->getPay($request);

        if (!$pay) {
            $date = CreateConf::getInstance()->getConf('supervisor.chargeLimit');
            return ['code' => 210, 'msg' => "此信息需消耗 {$moduleInfo['basePrice']} 元，有效期 {$date} 天"];
        }

        try {
            //取得用户钱包余额
            $info = Wallet::create()->where('phone', $phone)->get();
            if (empty($info)) return ['code' => 201, 'msg' => '无用户钱包信息'];
            $userMoney = $info->money;
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
            return ['code' => 201, 'msg' => '取得用户钱包信息失败'];
        }

        if ($userMoney < $moduleInfo['basePrice']) return ['code' => 220, 'msg' => '用户余额不足'];

        try {
            //扣费
            $money = $userMoney - $moduleInfo['basePrice'];
            $info->update(['money' => $money > 0 ? $money : 0]);
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
            return ['code' => 201, 'msg' => '扣费失败'];
        }

        //写入记录
        try {
            $insert = [
                'moduleId' => $moduleNum,
                'moduleName' => $moduleInfo['name'] . $moduleInfo['desc'],
                'entName' => $entName,
                'phone' => $phone,
                'price' => -$moduleInfo['basePrice'],
            ];
            Charge::create()->data($insert)->save();
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
        }

        return ['code' => 200, 'msg' => '扣费成功'];
    }

    //二次特征
    function Features(Request $request, $moduleNum)
    {
        $phone = $this->getPhone($request);

        $entName = $this->getEntName($request);

        if (empty($phone) || empty($entName)) return ['code' => 201, 'msg' => '手机号或公司名不能是空'];

        //获取扣费详情
        $moduleInfo = $this->getModuleInfo($moduleNum);

        //新用户前5天，每天前3个企业免费
        try {
            $info = User::create()->where('phone', $phone)->get();
            if (empty($info)) return ['code' => 201, 'msg' => '无用户信息'];
            //取得注册时间
            $regTime = $info->created_at;
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
            return ['code' => 201, 'msg' => '取得用户信息失败'];
        }

        //新用户前5天，每天前3个企业免费
        if (time() - $regTime < 5 * 86400) {
            //是否免费
            try {
                //当天查询企业个数
                $star = Carbon::now()->startOfDay()->timestamp;
                $end = Carbon::now()->endOfDay()->timestamp;
                $num = Charge::create()
                    ->where('phone', $phone)
                    ->where('moduleId', $moduleNum)
                    ->where("(created_at > {$star} and created_at < {$end})")
                    ->group('entName')
                    ->all();
                $num = obj2Arr($num);
                empty($num) ? $num = 0 : $num = count($num);
                if ($num <= 3) {
                    //还在免费
                    Charge::create()->data([
                        'moduleId' => $moduleNum,
                        'moduleName' => $moduleInfo['name'] . $moduleInfo['desc'],
                        'entName' => $entName,
                        'phone' => $phone,
                        'price' => 0,
                    ])->save();
                    return ['code' => 200, 'msg' => '成功'];
                }
                //当天不免费了
                $charge = Charge::create()
                    ->where('phone', $phone)
                    ->where('entName', $entName)
                    ->where('moduleId', $moduleNum)
                    ->order('created_at', 'desc')
                    ->get();
            } catch (\Throwable $e) {
                $this->writeErr($e, 'ChargeService');
            }
        } else {
            //是否免费
            try {
                $charge = Charge::create()
                    ->where('phone', $phone)
                    ->where('entName', $entName)
                    ->where('moduleId', $moduleNum)
                    ->where('price', 0, '<')
                    ->order('created_at', 'desc')
                    ->get();
            } catch (\Throwable $e) {
                $this->writeErr($e, 'ChargeService');
            }
        }

        if (!empty($charge)) {
            //取出上次计费时间
            $time = $charge->created_at;
            //缓存过期时间
            $limitDay = CreateConf::getInstance()->getConf('xindong.chargeLimit');
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
                    Charge::create()->data($insert)->save();
                } catch (\Throwable $e) {
                    $this->writeErr($e, 'ChargeService');
                }
                return ['code' => 200];
            }
        }

        //等于false说明用户还没点确定支付，等于true说明用户点了确认支付
        $pay = $this->getPay($request);

        if (!$pay) return ['code' => 210, 'msg' => "此信息需消耗 {$moduleInfo['basePrice']} 元，有效期 7 天"];

        try {
            //取得用户钱包余额
            $info = Wallet::create()->where('phone', $phone)->get();
            if (empty($info)) return ['code' => 201, 'msg' => '无用户钱包信息'];
            $userMoney = $info->money;
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
            return ['code' => 201, 'msg' => '取得用户钱包信息失败'];
        }

        if ($userMoney < $moduleInfo['basePrice']) return ['code' => 220, 'msg' => '用户余额不足'];

        try {
            //扣费
            $money = $userMoney - $moduleInfo['basePrice'];
            $info->update(['money' => $money > 0 ? $money : 0]);
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
            return ['code' => 201, 'msg' => '扣费失败'];
        }

        //写入记录
        try {
            $insert = [
                'moduleId' => $moduleNum,
                'moduleName' => $moduleInfo['name'] . $moduleInfo['desc'],
                'entName' => $entName,
                'phone' => $phone,
                'price' => -$moduleInfo['basePrice'],
            ];
            Charge::create()->data($insert)->save();
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
        }

        return ['code' => 200, 'msg' => '扣费成功'];
    }

    //极简报告
    function VeryEasyReport(Request $request, $moduleNum, $reportNum)
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
            $this->writeErr($e, 'ChargeService');
            return ['code' => 201, 'msg' => '取得用户钱包信息失败'];
        }

        if ($userMoney < $moduleInfo['basePrice']) return ['code' => 220, 'msg' => '用户余额不足'];

        //等于false说明用户还没点确定支付，等于true说明用户点了确认支付
        $pay = $this->getPay($request);

        if (!$pay) return ['code' => 210, 'msg' => "此信息需消耗 {$moduleInfo['basePrice']} 元，有效期 7 天"];

        try {
            //扣费
            $money = $userMoney - $moduleInfo['basePrice'];
            $info->update(['money' => $money > 0 ? $money : 0]);
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
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
                'price' => -$moduleInfo['basePrice'],
            ];
            Charge::create()->data($insert)->save();
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
        }

        return ['code' => 200, 'msg' => '扣费成功'];
    }

    //极简报告定制
    function VeryEasyReportCustomized(Request $request, $moduleNum, $reportNum)
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
            $this->writeErr($e, 'ChargeService');
            return ['code' => 201, 'msg' => '取得用户钱包信息失败'];
        }

        if ($userMoney < $moduleInfo['basePrice']) return ['code' => 220, 'msg' => '用户余额不足'];

        //等于false说明用户还没点确定支付，等于true说明用户点了确认支付
        $pay = $this->getPay($request);

        if (!$pay) return ['code' => 210, 'msg' => "此信息需消耗 {$moduleInfo['basePrice']} 元，有效期 7 天"];

        try {
            //扣费
            $money = $userMoney - $moduleInfo['basePrice'];
            $info->update(['money' => $money > 0 ? $money : 0]);
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
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
                'price' => -$moduleInfo['basePrice'],
            ];
            Charge::create()->data($insert)->save();
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
        }

        return ['code' => 200, 'msg' => '扣费成功'];
    }

    //简版报告
    function EasyReport(Request $request, $moduleNum, $reportNum)
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
            $this->writeErr($e, 'ChargeService');
            return ['code' => 201, 'msg' => '取得用户钱包信息失败'];
        }

        if ($userMoney < $moduleInfo['basePrice']) return ['code' => 220, 'msg' => '用户余额不足'];

        //等于false说明用户还没点确定支付，等于true说明用户点了确认支付
        $pay = $this->getPay($request);

        if (!$pay) return ['code' => 210, 'msg' => "此信息需消耗 {$moduleInfo['basePrice']} 元，有效期 7 天"];

        try {
            //扣费
            $money = $userMoney - $moduleInfo['basePrice'];
            $info->update(['money' => $money > 0 ? $money : 0]);
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
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
                'price' => -$moduleInfo['basePrice'],
            ];
            Charge::create()->data($insert)->save();
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
        }

        return ['code' => 200, 'msg' => '扣费成功'];
    }

    //简版报告定制
    function EasyReportCustomized(Request $request, $moduleNum, $reportNum)
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
            $this->writeErr($e, 'ChargeService');
            return ['code' => 201, 'msg' => '取得用户钱包信息失败'];
        }

        if ($userMoney < $moduleInfo['basePrice']) return ['code' => 220, 'msg' => '用户余额不足'];

        //等于false说明用户还没点确定支付，等于true说明用户点了确认支付
        $pay = $this->getPay($request);

        if (!$pay) return ['code' => 210, 'msg' => "此信息需消耗 {$moduleInfo['basePrice']} 元，有效期 7 天"];

        try {
            //扣费
            $money = $userMoney - $moduleInfo['basePrice'];
            $info->update(['money' => $money > 0 ? $money : 0]);
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
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
                'price' => -$moduleInfo['basePrice'],
            ];
            Charge::create()->data($insert)->save();
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
        }

        return ['code' => 200, 'msg' => '扣费成功'];
    }

    //深度报告
    function DeepReport(Request $request, $moduleNum, $reportNum)
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
            $this->writeErr($e, 'ChargeService');
            return ['code' => 201, 'msg' => '取得用户钱包信息失败'];
        }

        if ($userMoney < $moduleInfo['basePrice']) return ['code' => 220, 'msg' => '用户余额不足'];

        //等于false说明用户还没点确定支付，等于true说明用户点了确认支付
        $pay = $this->getPay($request);

        if (!$pay) return ['code' => 210, 'msg' => "此信息需消耗 {$moduleInfo['basePrice']} 元，有效期 7 天"];

        try {
            //扣费
            $money = $userMoney - $moduleInfo['basePrice'];
            $info->update(['money' => $money > 0 ? $money : 0]);
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
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
                'price' => -$moduleInfo['basePrice'],
            ];
            Charge::create()->data($insert)->save();
        } catch (\Throwable $e) {
            $this->writeErr($e, 'ChargeService');
        }

        return ['code' => 200, 'msg' => '扣费成功'];
    }

}
