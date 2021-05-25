<?php

namespace App\HttpController\Business\Api\User;

use App\HttpController\Models\Api\AuthBook;
use App\HttpController\Models\Api\Charge;
use App\HttpController\Models\Api\PurchaseInfo;
use App\HttpController\Models\Api\PurchaseList;
use App\HttpController\Models\Api\ReportInfo;
use App\HttpController\Models\Api\SupervisorEntNameInfo;
use App\HttpController\Models\Api\SupervisorPhoneEntName;
use App\HttpController\Models\Api\SupervisorPhoneLimit;
use App\HttpController\Models\Api\User;
use App\HttpController\Models\Api\Wallet;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\OneSaid\OneSaidService;
use App\HttpController\Service\Pay\ChargeService;
use App\HttpController\Service\Pay\wx\wxPayService;
use App\HttpController\Service\User\UserService;
use Carbon\Carbon;
use EasySwoole\RedisPool\Redis;
use wanghanwanghan\someUtils\control;

class UserController extends UserBase
{
    public $connDB;

    function onRequest(?string $action): ?bool
    {
        //DB链接名称
        $this->connDB = CreateConf::getInstance()->getConf('env.mysqlDatabase');

        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //注册
    function reg()
    {
        $company = $this->request()->getRequestParam('company') ?? '';
        $username = $this->request()->getRequestParam('username') ?? '';
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $email = $this->request()->getRequestParam('email') ?? 'test@test.com';
        $pidPhone = $this->request()->getRequestParam('pidPhone') ?? 0;//注册裂变

        $password = $this->request()->getRequestParam('password') ?? '';
        empty(trim($password)) ? $password = control::randNum(6) : $password = trim($password);
        $avatar = $this->request()->getRequestParam('avatar') ?? '';

        $vCode = $this->request()->getRequestParam('vCode') ?? '';

        if (empty($phone) || empty($vCode)) return $this->writeJson(201, null, null, '手机号或验证码不能是空');

        if (!is_numeric($phone) || !is_numeric($vCode)) return $this->writeJson(201, null, null, '手机号或验证码必须是数字');

        if (strlen($phone) !== 11) return $this->writeJson(201, null, null, '手机号错误');

        if (!CommonService::getInstance()->validateEmail($email)) return $this->writeJson(201, null, null, 'email格式错误');

        $redis = Redis::defer('redis');

        $redis->select(14);

        $vCodeInRedis = $redis->get($phone . 'reg');

        if ((int)$vCodeInRedis !== (int)$vCode) return $this->writeJson(201, null, null, '验证码错误');

        try {
            $res = User::create()->where('phone', $phone)->get();
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        //已经注册过了
        if ($res) return $this->writeJson(201, null, null, '手机号已经注册过了');

        //找出pidPhone的id
        try {
            $pid = User::create()->where('phone', $pidPhone)->get();
            empty($pid) ? $pid = 0 : $pid = $pid->id;
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        try {
            $token = UserService::getInstance()->createAccessToken($phone, $password);
            $insert = [
                'username' => $username,
                'password' => $password,
                'phone' => $phone,
                'email' => $email,
                'avatar' => $avatar,
                'token' => $token,
                'company' => $company,
                'pid' => $pid
            ];
            User::create()->data($insert)->save();
            if ($company === '火眼推广20210420') {
                Wallet::create()->data([
                    'phone' => $phone,
                    'money' => 5 * 5 * 35
                ])->save();
            } else {
                Wallet::create()->data(['phone' => $phone])->save();
            }
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        return $this->writeJson(200, null, $insert, '注册成功');
    }

    //登录
    function login()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $vCode = $this->request()->getRequestParam('vCode') ?? '';
        $password = $this->request()->getRequestParam('password') ?? '';

        if (empty($phone) || (empty($vCode) && empty($password)))
            return $this->writeJson(201, null, null, '手机号或密码或验证码不能是空');

        try {
            $userInfo = User::create()->where('phone', $phone)->get();
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        if (empty($userInfo)) return $this->writeJson(201, null, null, '手机号不存在');

        if ($userInfo->getAttr('isDestroy') == 1) return $this->writeJson(201, null, null, '手机号已注销');

        //密码或者验证码登陆
        if (!empty($vCode)) {
            $redis = Redis::defer('redis');
            $redis->select(14);
            $vCodeInRedis = $redis->get($phone . 'login');
            if (!is_numeric($vCodeInRedis) || $vCodeInRedis <= 1000) {
                $vCodeInRedis = $redis->get($phone . 'reg');
            }
            if ((int)$vCodeInRedis !== (int)$vCode) return $this->writeJson(201, null, null, '验证码错误');
        } elseif (!empty($password)) {
            !is_numeric($password) ?: $password -= 0;
            !is_numeric($userInfo->getAttr('password')) ?
                $mysql_pwd = $userInfo->getAttr('password') :
                $mysql_pwd = $userInfo->getAttr('password') - 0;
            if ($password !== $mysql_pwd) return $this->writeJson(201, null, null, '密码错误');
        } else {
            return $this->writeJson(201, null, null, '干啥呢');
        }

        $newToken = UserService::getInstance()->createAccessToken($userInfo->phone, $userInfo->password);

        try {
            User::create()->get($userInfo->getAttr('id'))->update(['token' => $newToken]);
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        $userInfo->newToken = $newToken;

        return $this->writeJson(200, null, $userInfo, '登录成功');
    }

    //修改密码
    function setLoginPassword()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $pwd_moto = $this->request()->getRequestParam('pwd_moto') ?? '';
        $pwd_saki = $this->request()->getRequestParam('pwd_saki') ?? '';
        $pwd_saki_confirm = $this->request()->getRequestParam('pwd_saki_confirm') ?? '';

        try {
            $user_info = User::create()->where([
                'phone' => $phone,
                'password' => $pwd_moto,
            ])->get();
            if (empty($user_info)) return $this->writeJson(201, null, null, '旧密码错误');
            if (mb_strlen($pwd_saki) > 10) return $this->writeJson(201, null, null, '新密码最多10位');
            if (mb_strlen($pwd_saki) < 6) return $this->writeJson(201, null, null, '新密码最少6位');
            if ($pwd_saki !== $pwd_saki_confirm) return $this->writeJson(201, null, null, '新密码输入不一致');
            $user_info->update(['password' => $pwd_saki]);
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        return $this->writeJson();
    }

    //注销账户
    function destroyUser()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $type = $this->request()->getRequestParam('type') ?? false;

        if (empty($type) || $type == false) return $this->writeJson(201, null, null, '确认要注销账户吗？');

        try {
            $info = User::create()->where('phone', $phone)->get();

            if (empty($info)) return $this->writeJson(201, null, null, '未找到用户信息');

            $info->update(['token' => 'isDestroy', 'isDestroy' => 1]);

        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        return $this->writeJson(200, null, null, '注销成功');
    }

    //获取充值商品列表
    function purchaseGoods()
    {
        $phone = $this->request()->getRequestParam('phone');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        try {
            $info = PurchaseList::create()->limit($this->exprOffset($page, $pageSize), (int)$pageSize)->all();

            //拿到数据
            $info = obj2Arr($info);

            //数据的总记录条数
            $total = PurchaseList::create()->count();

        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        !empty($info) ?: $info = null;

        return $this->writeJson(200, ['page' => $page, 'pageSize' => $pageSize, 'total' => $total], $info, '查询成功');
    }

    //获取用户充值详情列表
    function purchaseList()
    {
        $phone = $this->request()->getRequestParam('phone');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        try {

            $list = PurchaseInfo::create()
                ->alias('info')
                ->field([
                    'info.id',
                    'info.phone',
                    'info.orderId',
                    'info.orderStatus',
                    'info.purchaseType',
                    'info.payMoney',
                    'info.payWay',
                    'info.created_at',
                    'list.name',
                    'list.desc',
                    'list.money',
                ])
                ->join('information_dance_purchase_list as list', 'list.id = info.purchaseType')
                ->where('phone', $phone)
                ->where('orderStatus', '待支付', '<>')
                ->order('info.updated_at', 'desc')
                ->limit($this->exprOffset($page, $pageSize), (int)$pageSize)
                ->all();

            //拿到数据
            $list = obj2Arr($list);

            //数据的总记录条数
            $total = PurchaseInfo::create()->where('phone', $phone)
                ->where('orderStatus', '待支付', '<>')->count();

        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        !empty($list) ?: $list = null;

        $userInfo = UserService::getInstance()->getUserInfo($phone);

        return $this->writeJson(200, [
            'page' => $page, 'pageSize' => $pageSize, 'total' => $total
        ], ['userInfo' => $userInfo, 'list' => $list], '查询成功');
    }

    //获取用户消费详情列表
    function payList()
    {
        $phone = $this->request()->getRequestParam('phone');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        try {

            $list = Charge::create()
                ->where('phone', $phone)
                ->order('created_at', 'desc')
                ->limit($this->exprOffset($page, $pageSize), (int)$pageSize)
                ->all();

            //拿到数据
            $list = obj2Arr($list);

            //数据的总记录条数
            $total = Charge::create()->where('phone', $phone)->count();

        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        !empty($list) ?: $list = null;

        $userInfo = UserService::getInstance()->getUserInfo($phone);

        return $this->writeJson(200, [
            'page' => $page, 'pageSize' => $pageSize, 'total' => $total
        ], ['userInfo' => $userInfo, 'list' => $list], '查询成功');
    }

    //充值
    function purchaseDo()
    {
        $jsCode = $this->request()->getRequestParam('jsCode') ?? '';
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $type = $this->request()->getRequestParam('type') ?? 1;
        $payConfType = $this->request()->getRequestParam('payConfType') ?? 'xd';

        try {
            $list = PurchaseList::create()->where('id', $type)->get();
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        //后三位备用
        $orderId = Carbon::now()->format('YmdHis') . control::randNum(2) . str_pad(0, 3, 0, STR_PAD_LEFT);

        //创建订单
        $insert = [
            'phone' => $phone,
            'orderId' => $orderId,
            'orderStatus' => '待支付',
            'purchaseType' => $type,
            'payMoney' => $list->money,
            'payWay' => '微信小程序',
        ];

        try {
            PurchaseInfo::create()->data($insert)->save();
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        //创建小程序支付对象
        $payObj = (new wxPayService())->setPayConfType($payConfType)
            ->miniAppPay($jsCode, $orderId, $list->money, $list->name . ' - ' . $list->desc);

        return $this->writeJson(200, null, ['orderId' => $orderId, 'payObj' => $payObj], '生成订单成功');
    }

    //通过orderId查询充值状态
    function purchaseCheck()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $orderId = $this->request()->getRequestParam('orderId') ?? '';

        try {
            $res = PurchaseInfo::create()->where([
                'phone' => $phone,
                'orderId' => $orderId,
                'orderStatus' => '已支付',
            ])->get();
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        return empty($res) ? $this->writeJson(201) : $this->writeJson(200);
    }

    //发布一句话
    function createOneSaid()
    {
        $phone = $this->request()->getRequestParam('phone');
        $oneSaid = $this->request()->getRequestParam('oneSaid') ?? '';
        $moduleId = $this->request()->getRequestParam('moduleId') ?? '';
        $entName = $this->request()->getRequestParam('entName') ?? '';

        $entName = trim($entName);

        if (empty($entName)) return $this->writeJson(201, null, null, 'entName错误');

        if (!is_numeric($moduleId)) return $this->writeJson(201, null, null, 'moduleId错误');

        $oneSaid = trim($oneSaid);

        if (empty($oneSaid) || mb_strlen($oneSaid) > 255) return $this->writeJson(201, null, null, 'oneSaid错误');

        return $this->writeJson(200, null, OneSaidService::getInstance()->createOneSaid($phone, $oneSaid, $moduleId, $entName), '发布成功');
    }

    //修改一句话
    function editOneSaid()
    {
        $phone = $this->request()->getRequestParam('phone');
        $oneSaid = $this->request()->getRequestParam('oneSaid') ?? '';
        $moduleId = $this->request()->getRequestParam('moduleId') ?? '';
        $entName = $this->request()->getRequestParam('entName') ?? '';

        $entName = trim($entName);

        if (empty($entName)) return $this->writeJson(201, null, null, 'entName错误');

        if (!is_numeric($moduleId)) return $this->writeJson(201, null, null, 'moduleId错误');

        $oneSaid = trim($oneSaid);

        if (empty($oneSaid) || mb_strlen($oneSaid) > 255) return $this->writeJson(201, null, null, 'oneSaid错误');

        return $this->writeJson(200, null, OneSaidService::getInstance()->createOneSaid($phone, $oneSaid, $moduleId, $entName), '修改成功');
    }

    //获取用户发布一句话
    function getOneSaid()
    {
        $phone = $this->request()->getRequestParam('phone');
        $moduleId = $this->request()->getRequestParam('moduleId') ?? '';
        $entName = $this->request()->getRequestParam('entName') ?? '';

        $entName = trim($entName);

        if (empty($entName)) return $this->writeJson(201, null, null, 'entName错误');

        if (!is_numeric($moduleId)) return $this->writeJson(201, null, null, 'moduleId错误');

        return $this->writeJson(200, null, OneSaidService::getInstance()->getOneSaid($phone, $moduleId, $entName, false), '获取成功');
    }

    //创建风险监控
    function createSupervisor()
    {
        $phone = $this->request()->getRequestParam('phone');
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $type = $this->request()->getRequestParam('type') ?? 3;//1争议2合作3全部

        $charge = ChargeService::getInstance()->Supervisor($this->request(), 50);

        if ($charge['code'] != 200) return $this->writeJson($charge['code'], null, null, $charge['msg']);

        try {

            //先看添加没添加过
            $data = SupervisorPhoneEntName::create()->where('phone', $phone)->where('entName', $entName)->get();

            if (empty($data)) {

                //没添加过
                $data = [
                    'phone' => $phone,
                    'entName' => $entName,
                    'type' => $type,
                    'status' => 1,
                    'totalNum' => [
                        'zyf' => 0,
                        'hzf' => [
                            'sf' => 0,
                            'gs' => 0,
                            'gl' => 0,
                            'jy' => 0,
                        ]
                    ],
                    'currentNum' => [
                        'zyf' => 0,
                        'hzf' => [
                            'sf' => 0,
                            'gs' => 0,
                            'gl' => 0,
                            'jy' => 0,
                        ]
                    ],
                    'expireTime' => time() + CreateConf::getInstance()->getConf('supervisor.chargeLimit') * 86400,
                ];

                SupervisorPhoneEntName::create()->data($data)->save();

            } else {

                //添加过了
                $data->update([
                    'type' => $type,
                    'status' => 1,
                    'expireTime' => time() + CreateConf::getInstance()->getConf('supervisor.chargeLimit') * 86400
                ]);
                $data = $data->toArray();
            }

        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        return $this->writeJson(200, null, $data, '添加成功');
    }

    //获取用户风险监控数据
    function getSupervisor()
    {
        $phone = $this->request()->getRequestParam('phone');
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $supervisorType = $this->request()->getRequestParam('supervisorType') ?? 3;
        $title = $this->request()->getRequestParam('title') ?? '';
        $level = $this->request()->getRequestParam('level') ?? '';
        $type = $this->request()->getRequestParam('type') ?? '';
        $typeDetail = $this->request()->getRequestParam('typeDetail') ?? '';
        $timeRange = $this->request()->getRequestParam('timeRange') ?? '';
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        //先确定是一个公司，还是全部公司
        try {
            $entList = SupervisorPhoneEntName::create()->where([
                'phone' => $phone,
                'status' => 1,
                'type' => $supervisorType - 0,
            ])->all();

            if (empty($entList)) {
                //没有监控任何类型的公司
                $entList = [];
            } else {
                //监控了一堆公司
                $tmp = [];
                foreach ($entList as $one) {
                    $tmp[] = $one->entName;
                }
                $entList = $tmp;
            }
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        $entList = array_filter($entList);
        if (empty($entList)) return $this->writeJson(201, null, null, '没有监控任何公司');

        //查一个公司，还是全部公司
        if (!empty($entName) && in_array($entName, $entList)) {
            $entList = [$entName];
        }

        $detail = SupervisorEntNameInfo::create()->where('entName', $entList, 'IN');
        $resTotle = SupervisorEntNameInfo::create()->where('entName', $entList, 'IN');

        if (!empty($title) && $title !== '全部') {
            if ($title === '争议方') $tmp = 1;
            if ($title === '合作/投资对象') $tmp = 2;

            $detail->where('title', $tmp);
            $resTotle->where('title', $tmp);
        }

        if (!empty($level) && $level !== '全部') {
            if ($level === '高风险') $tmp = 1;
            if ($level === '风险') $tmp = 2;
            if ($level === '警示') $tmp = 3;
            if ($level === '提示') $tmp = 4;
            if ($level === '利好') $tmp = 5;

            $detail->where('level', $tmp);
            $resTotle->where('level', $tmp);
        }

        if (!empty($type) && $type !== '全部') {
            if ($type === '争议方') $tmp = 0;
            if ($type === '司法风险') $tmp = 1;
            if ($type === '工商风险') $tmp = 2;
            if ($type === '管理风险') $tmp = 3;
            if ($type === '经营风险') $tmp = 4;

            $detail->where('type', $tmp);
            $resTotle->where('type', $tmp);
        }

        if (!empty($typeDetail) && $typeDetail !== '全部') {
            if (in_array($typeDetail, ['股东变更', '股权冻结', '工商变更', '严重违法', '经营异常'])) $tmp = 1;
            if (in_array($typeDetail, ['注销/吊销', '法院公告', '实际控制人', '环保处罚', '动产抵押'])) $tmp = 2;
            if (in_array($typeDetail, ['行政处罚', '查封冻结扣押', '最终受益人', '税收违法', '土地抵押'])) $tmp = 3;
            if (in_array($typeDetail, ['开庭公告', '对外投资', '欠税公告', '股权出质'])) $tmp = 4;
            if (in_array($typeDetail, ['失信被执行人', '主要成员', '海关处罚', '股权质押'])) $tmp = 5;
            if (in_array($typeDetail, ['被执行信息', '一行两会', '对外担保'])) $tmp = 6;
            if (in_array($typeDetail, ['裁判文书'])) $tmp = 7;
            if (in_array($typeDetail, ['新闻舆情'])) $tmp = 8;

            $detail->where('typeDetail', $tmp);
            $resTotle->where('typeDetail', $tmp);
        }

        if (!empty($timeRange) && $timeRange !== '全部') {
            is_numeric($timeRange) ?: $timeRange = 3;
            $date = Carbon::now()->subDays($timeRange)->timestamp;
            $detail->where('timeRange', $date, '>');
            $resTotle->where('timeRange', $date, '>');
        }

        try {
            $detail = $detail->order('created_at', 'desc')
                ->limit($this->exprOffset($page, $pageSize), $pageSize)
                ->all();
            $detail = obj2Arr($detail);
            $resTotle = $resTotle->count();
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        try {
            $entList = SupervisorPhoneEntName::create()->where([
                'phone' => $phone,
                'status' => 1,
                'type' => $supervisorType - 0,
            ])->all();
            $entList = obj2Arr($entList);
            $i = 1;
            foreach ($entList as $key => $one) {
                $entList[$key]['indexId'] = $i++;//序号
                $entList[$key]['totalNum'] = jsonDecode($one['totalNum']);
                $entList[$key]['currentNum'] = jsonDecode($one['currentNum']);
            }
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $resTotle
        ], ['entList' => $entList, 'detail' => $detail], '查询成功');
    }

    //获取风险阈值
    function getSupervisorLimit()
    {
        $phone = $this->request()->getRequestParam('phone');
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $entName = trim($entName);

        if (empty($phone) || empty($entName)) return $this->writeJson(201, null, null, '手机号和企业名不能是空');

        try {
            $data = SupervisorPhoneLimit::create()
                ->where('phone', $phone)
                ->where('entName', $entName)
                ->get();
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        empty($data) ? $data = [
            'phone' => $phone,
            'entName' => $entName,
            'sf' => 0,
            'gs' => 0,
            'gl' => 0,
            'jy' => 0,
        ] : $data = $data->toArray();

        return $this->writeJson(200, null, $data, '成功');
    }

    //导出列表
    function getSupervisorListByExcel()
    {
        $phone = $this->request()->getRequestParam('phone');
        $entNameList = $this->request()->getRequestParam('entNameList') ?? '';
        $entNameList = array_filter(explode(',', $entNameList));

        $config = [
            'path' => TEMP_FILE_PATH // xlsx文件保存路径
        ];

        $excel = new \Vtiful\Kernel\Excel($config);

        $filename = control::getUuid(8) . '.xlsx';

        $header = [
            '序号',
            '企业名称',
            '监控类别',
            '争议方',
            '合作方-司法',
            '合作方-工商',
            '合作方-管理',
            '合作方-经营',
            '最后监控时间',
        ];

        try {
            $list = SupervisorPhoneEntName::create()
                ->where('phone', $phone)
                ->where('entName', $entNameList, 'IN')
                ->all();
            $data = [];
            $i = 1;
            foreach ($list as $one) {
                $num = jsonDecode($one->currentNum);
                $tmp = [
                    $i,
                    $one->entName,
                    $one->type === 1 ? '争议方' : $one->type === 2 ? '合作方' : '全部',
                    ($one->type === 1 || $one->type === 3) ? $num['zyf'] : 0,
                    ($one->type === 2 || $one->type === 3) ? $num['hzf']['sf'] : 0,
                    ($one->type === 2 || $one->type === 3) ? $num['hzf']['gs'] : 0,
                    ($one->type === 2 || $one->type === 3) ? $num['hzf']['gl'] : 0,
                    ($one->type === 2 || $one->type === 3) ? $num['hzf']['jy'] : 0,
                    date('Y-m-d', $one->updated_at),
                ];
                array_push($data, $tmp);
                $i++;
            }
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        $fileObject = $excel->fileName($filename, '汇总');
        $fileHandle = $fileObject->getHandle();

        //==========================================================================================================
        $format = new \Vtiful\Kernel\Format($fileHandle);

        $colorStyle = $format
            ->fontColor(\Vtiful\Kernel\Format::COLOR_ORANGE)
            ->border(\Vtiful\Kernel\Format::BORDER_DASH_DOT)
            ->align(\Vtiful\Kernel\Format::FORMAT_ALIGN_CENTER, \Vtiful\Kernel\Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->toResource();

        $format = new \Vtiful\Kernel\Format($fileHandle);

        $alignStyle = $format
            ->align(\Vtiful\Kernel\Format::FORMAT_ALIGN_CENTER, \Vtiful\Kernel\Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->toResource();
        //==========================================================================================================

        $fileObject
            ->defaultFormat($colorStyle)
            ->header($header)
            ->defaultFormat($alignStyle)
            ->data($data);

        $format = new \Vtiful\Kernel\Format($fileHandle);
        $wrapStyle = $format->wrap()->toResource();

        //导出每个公司的监控详情
        foreach ($entNameList as $one_ent_name) {
            if (false) {
                $fileObject
                    ->addSheet($one_ent_name)
                    ->header(['name', 'age'])
                    ->defaultFormat($wrapStyle)
                    ->data([
                        ["wanghan\n123", 22]
                    ]);
            }
        }

        $fileObject->output();

        return $this->writeJson(200, null, 'Static/Temp/' . $filename);
    }

    //修改风险阈值
    function editSupervisorLimit()
    {
        $phone = $this->request()->getRequestParam('phone');
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $sf = $this->request()->getRequestParam('sf') ?? 1;
        $gs = $this->request()->getRequestParam('gs') ?? 1;
        $gl = $this->request()->getRequestParam('gl') ?? 1;
        $jy = $this->request()->getRequestParam('jy') ?? 1;
        $entName = trim($entName);

        if (empty($phone) || empty($entName)) return $this->writeJson(201, null, null, '手机号和企业名不能是空');

        try {
            $info = SupervisorPhoneLimit::create()->where('phone', $phone)->where('entName', $entName)->get();

            $data = [
                'phone' => $phone,
                'entName' => $entName,
                'sf' => $sf,
                'gs' => $gs,
                'gl' => $gl,
                'jy' => $jy,
            ];

            if (empty($info)) {
                SupervisorPhoneLimit::create()->data($data)->save();
            } else {
                $info->update($data);
            }

        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        return $this->writeJson(200, null, $data, '成功');
    }

    //获取报告列表
    function getReportList()
    {
        $phone = $this->request()->getRequestParam('phone');
        $type = $this->request()->getRequestParam('type') ?? 255;
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        try {
            $info = ReportInfo::create()->where('phone', $phone)->order('updated_at', 'desc')
                ->limit($this->exprOffset($page, $pageSize), (int)$pageSize);

            if (is_numeric($type) && $type != 255) $info->where('type', $type);

            //拿到数据
            $info = obj2Arr($info->all());

            $total = ReportInfo::create()->where('phone', $phone);

            if (is_numeric($type) && $type != 255) $total->where('type', $type);

            //数据的总记录条数
            $total = $total->count();

        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        if (!empty($info)) {
            //整理一下数据
            foreach ($info as &$one) {
                if ($one['status'] == 1) $one['statusWord'] = '异常';
                if ($one['status'] == 2) $one['statusWord'] = '完成';
                if ($one['status'] == 3) $one['statusWord'] = '生成中';
                if ($one['status'] == 4) $one['statusWord'] = '待审核';

                if ($one['type'] == 10) $one['typeWord'] = '企业速透版';
                if ($one['type'] == 30) $one['typeWord'] = '律师自用版';
                if ($one['type'] == 50) $one['typeWord'] = '尽调专用版';

                if (!empty($one['created_at'])) $one['created_atWord'] = date('Y-m-d H:i:s', $one['created_at']);
            }
            unset($one);

        } else {
            $info = null;
        }

        return $this->writeJson(200, [
            'page' => $page, 'pageSize' => $pageSize, 'total' => $total
        ], $info, '查询成功');
    }

    //上传授权书后的确认按钮
    function createAuthBook()
    {
        $phone = $this->request()->getRequestParam('phone');
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $code = $this->request()->getRequestParam('code') ?? '';
        $type = $this->request()->getRequestParam('type') ?? '';
        $path = $this->request()->getRequestParam('path') ?? '';

        if (empty($entName) || empty($path) || !is_numeric($type))
            return $this->writeJson(201, null, null, '授权书path或企业名或文件类型不能是空');

        $filename = explode(DIRECTORY_SEPARATOR, $path);

        $filename = end($filename);

        try {
            AuthBook::create()->data([
                'phone' => $phone,
                'entName' => $entName,
                'code' => $code,
                'name' => $filename,
                'status' => 1,
                'type' => $type,
                'remark' => '',
            ])->save();

            CommonService::getInstance()->sendSMS([
                '18618457910',
                '18600047886',
                '18201611816',
            ], '03', '1');

        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        return $this->writeJson(200, null, null, '提交成功');
    }

    //获取用户授权书审核列表
    function getAuthBook()
    {
        $phone = $this->request()->getRequestParam('phone');
        $type = $this->request()->getRequestParam('type');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        try {
            if (is_numeric($type) && ($type == 1 || $type == 2)) {
                $info = AuthBook::create()
                    ->where('phone', $phone)
                    ->where('type', $type)
                    ->order('created_at', 'desc')
                    ->limit($this->exprOffset($page, $pageSize), $pageSize)
                    ->all();
                $info = obj2Arr($info);
                $total = AuthBook::create()->where('phone', $phone)->where('type', $type)->count();
            } else {
                $info = AuthBook::create()->where('phone', $phone)
                    ->order('created_at', 'desc')
                    ->limit($this->exprOffset($page, $pageSize), $pageSize)
                    ->all();
                $info = obj2Arr($info);
                $total = AuthBook::create()->where('phone', $phone)->count();
            }
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        !empty($info) ?: $info = null;

        if (!empty($info)) {
            foreach ($info as &$one) {
                if ($one['status'] == 1) $one['statusWord'] = '审核中';
                if ($one['status'] == 2) $one['statusWord'] = '未通过';
                if ($one['status'] == 3) $one['statusWord'] = '已通过';

                $one['created_atWord'] = date('Y-m-d H:i:s', $one['created_at']);
            }
            unset($one);
        }

        $paging = [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
        ];

        return $this->writeJson(200, $paging, $info, '查询成功');
    }

    //检查用户上没上传过企业授权书
    function checkAuthBook()
    {
        $phone = $this->request()->getRequestParam('phone');
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $type = $this->request()->getRequestParam('type') ?? '';

        if (empty($entName) || !is_numeric($type))
            return $this->writeJson(201, null, null, '企业名或文件类型不能是空');

        try {
            switch ($type) {
                case '1':
                    //查财务授权时，只要有就行
                    $res = AuthBook::create()->where([
                        'phone' => $phone,
                        'entName' => $entName,
                        'type' => 1,
                    ])->order('created_at', 'desc')->get();
                    break;
                case '2':
                    //查深度报告授权时，有效期只有半年
                    $beforeHalfYear = Carbon::now()->subMonths(6)->timestamp;
                    $res = AuthBook::create()->where([
                        'phone' => $phone,
                        'entName' => $entName,
                        'type' => 2,
                    ])->where('created_at', $beforeHalfYear, '>')->order('created_at', 'desc')->get();
                    break;
                default:
                    $res = [];
            }
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        if (empty($res)) {
            return $this->writeJson(201, null, null, '请上传授权书以供我方向有关部门备案');
        } elseif ($res->status !== 3) {
            return $this->writeJson(202, null, null, '请等待审核');
        } else {
            return $this->writeJson(200, null, null, '成功');
        }
    }


}