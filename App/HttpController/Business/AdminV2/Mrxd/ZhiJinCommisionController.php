<?php

namespace App\HttpController\Business\AdminV2\Mrxd;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Business\OnlineGoods\Mrxd\DaiKuanController;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminUserBussinessOpportunityUploadRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\AdminUserWechatInfoUploadRecord;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\AdminV2\DeliverDetailsHistory;
use App\HttpController\Models\AdminV2\DeliverHistory;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\AdminV2\FinanceLog;
use App\HttpController\Models\AdminV2\MailReceipt;
use App\HttpController\Models\AdminV2\ToolsUploadQueue;
use App\HttpController\Models\MRXD\OnlineGoodsCommissions;
use App\HttpController\Models\MRXD\OnlineGoodsDaikuanBank;
use App\HttpController\Models\MRXD\OnlineGoodsDaikuanProducts;
use App\HttpController\Models\MRXD\OnlineGoodsTiXianJiLu;
use App\HttpController\Models\MRXD\OnlineGoodsUser;
use App\HttpController\Models\MRXD\OnlineGoodsUserBaoXianOrder;
use App\HttpController\Models\MRXD\OnlineGoodsUserDaikuanOrder;
use App\HttpController\Models\MRXD\OnlineGoodsUserInviteRelation;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Models\RDS3\CompanyInvestor;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\XinDong\XinDongService;

class ZhiJinCommisionController extends ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    /**


     */

    function loanOrderLists(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']?:1;
        $pageSize =  $requestData['pageSize']?:100;

        $userInfo = $this->loginUserinfo;
        $datas = OnlineGoodsUserDaikuanOrder::findByConditionV2([],$page,$pageSize);
        foreach ($datas['data'] as &$dataValue){
            $productInfo = OnlineGoodsDaikuanProducts::findById($dataValue['product_id']);
            $dataValue['product_name'] = $productInfo?$productInfo->name:'';
            // bank_name
            $bankInfo = OnlineGoodsDaikuanBank::findById($productInfo->bank_id);
            $dataValue['bank_name'] = $bankInfo?$bankInfo->bank_cname:'';
            $dataValue['zhijin_account'] = $dataValue['zhijin_phone'];
            $dataValue['commission_set_state_cname'] = OnlineGoodsUserDaikuanOrder::getCommissionSetStateMap()[$dataValue['commission_set_state']];
            $dataValue['commission_state_cname'] = OnlineGoodsUserDaikuanOrder::getCommissionStateMap()[$dataValue['commission_state']];
            $dataValue['zhijin_account'] = $dataValue['zhijin_phone'];

            //邀请人
            $buyerInfo = OnlineGoodsUser::findByPhone($dataValue['zhijin_account']);
            $invitorInfo =  OnlineGoodsUserInviteRelation::getDirectInviterInfo($buyerInfo->id);
            $vipInvitorInfo =  OnlineGoodsUserInviteRelation::getVipInviterInfo($buyerInfo->id);
            $dataValue['invite_name'] = $invitorInfo['user_name'];
            $dataValue['vip_invite_name'] = $vipInvitorInfo['user_name'];

            $dataValue['created_at'] = date('Y-m-d H:i:s',$dataValue['created_at']);
            $dataValue['created_at'] = date('Y-m-d H:i:s',$dataValue['created_at']);
            $dataValue['commission_money'] = number_format(($dataValue['amount']*$dataValue['commission_rate'])/100,2);

        }

        $total = $datas['total'] ;
        $retrundatas = $datas['data'] ;
        return $this->writeJson(
            200,
            [
                'page' => $page,
                'pageSize' =>$pageSize,
                'total' => $total,
                'totalPage' => ceil( $total/ $pageSize ),
            ] ,
            $retrundatas
            ,
            '成功',
            true,
            []
        );
    }

    //
    function daikuanBank(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']?:1;
        $pageSize =  $requestData['pageSize']?:100;

        $userInfo = $this->loginUserinfo;

        $datas = OnlineGoodsDaikuanBank::findByConditionV2([],1,100);
        $retrundatas =  [];
        foreach ($datas['data'] as $dataItem){
            $retrundatas[$dataItem['id']] = $dataItem['bank_cname'];
        }


        return $this->writeJson(
            200,
            [

            ] ,
            $retrundatas
            ,
            '成功',
            true,
            []
        );
    }

    //保险列表
    function baoxianOrderLists(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']?:1;
        $pageSize =  $requestData['pageSize']?:100;

        $userInfo = $this->loginUserinfo;
        //===========================================
        /**
        purchaser_mobile: 132
        min_money: 132
        max_money: 132
        product_name: 14
        commision_set_state: 5
        min_order_date: 2022-09-25
        max_order_date: 2022-12-03
        invitation_code: 132
        updated[]: 2022-09-25
        updated[]: 2022-12-03
         */
        $whereArr = [];
        if (
            $requestData['commision_set_state'] >= 0
        ) {

            $whereArr[] = [
                'field' => 'commission_set_state',
                'value' => $requestData['commision_set_state'],
                'operate' => '=',
            ];
        }

        if (
            $requestData['product_name'] >= 0
        ) {

            $whereArr[] = [
                'field' => 'product_id',
                'value' => $requestData['product_name'],
                'operate' => '=',
            ];
        }
        if (
            $requestData['min_money'] >= 0
        ) {

            $whereArr[] = [
                'field' => 'amount',
                'value' => $requestData['min_money'],
                'operate' => '>=',
            ];
        }

        if (
            $requestData['max_money'] >= 0
        ) {

            $whereArr[] = [
                'field' => 'amount',
                'value' => $requestData['max_money'],
                'operate' => '<=',
            ];
        }

        if (
             $requestData['min_order_date'] >= 0
        ) {

            $whereArr[] = [
                'field' => 'order_date',
                'value' => $requestData['min_order_date'],
                'operate' => '>=',
            ];
        }
        if (
            $requestData['max_order_date'] >= 0
        ) {

            $whereArr[] = [
                'field' => 'order_date',
                'value' => $requestData['max_order_date'],
                'operate' => '<=',
            ];
        }

        if (
            $requestData['purchaser_mobile'] >= 0
        ) {

            $whereArr[] = [
                'field' => 'purchaser_phone',
                'value' => ''.$requestData['purchaser_mobile'].'%',
                'operate' => 'like',
            ];
        }

        //===========================================


        $datas = OnlineGoodsUserBaoXianOrder::findByConditionV2($whereArr,$page,$pageSize);

        $prodcutsRes = \App\HttpController\Service\BaoYa\BaoYaService::getProductsV2();

        /**
        {
        "id": 29,
        "title": "中路物流责任险年单",
        "category": "WLZRX",
        "company": {
        "flag": "ZL",
        "name": "中路"
        },
        "description": "港、澳、台、新疆（不包括乌鲁木齐）、西藏（不包括拉萨）、青海地区除外，仅适用河北山东地区物流企业投保。",
        "logo": "http:\/\/www.51baoya.com\/uploads\/product_briefs\/BlJgIwwvlgyw5IlaHvWolDU2o.jpg"
        }
         */
        foreach ($datas['data'] as &$dataValue){
            $dataValue['product_name'] = $prodcutsRes[$dataValue['product_id']]?:'';
            $dataValue['zhijin_account'] = $dataValue['zhijin_phone'];
            $dataValue['commission_set_state_cname'] = OnlineGoodsUserBaoXianOrder::getCommissionSetStateMap()[$dataValue['commission_set_state']];
            $dataValue['commission_state_cname'] = OnlineGoodsUserBaoXianOrder::getCommissionStateMap()[$dataValue['commission_state']];
            $dataValue['zhijin_account'] = $dataValue['zhijin_phone'];
            $dataValue['created_at'] = date('Y-m-d H:i:s',$dataValue['created_at']);
            $dataValue['commission_money'] = number_format(($dataValue['amount']*$dataValue['commission_rate'])/100,2);

            //邀请人
            $buyerInfo = OnlineGoodsUser::findByPhone($dataValue['zhijin_account']);
            $invitorInfo =  OnlineGoodsUserInviteRelation::getDirectInviterInfo($buyerInfo->id);
            $vipInvitorInfo =  OnlineGoodsUserInviteRelation::getVipInviterInfo($buyerInfo->id);
            $dataValue['invite_name'] = $invitorInfo['user_name'];
            $dataValue['vip_invite_name'] = $vipInvitorInfo['user_name'];


        }

        $total = $datas['total'] ;
        $retrundatas = $datas['data'] ;

        return $this->writeJson(
            200,
            [
                'page' => $page,
                'pageSize' =>$pageSize,
                'total' => $total,
                'totalPage' => ceil( $total/ $pageSize ),
            ] ,
            $retrundatas
            ,
            '成功',
            true,
            []
        );
    }


    function getZhiJinDaiKuanLists(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $code = $requestData['code'] ;

        $dataRes = OnlineGoodsDaikuanProducts::findByConditionV2([],1,100);
        $returnData = [];
        foreach ($dataRes['data'] as $valueItem){
            $bankRes = OnlineGoodsDaikuanBank::findById($valueItem['bank_id']);
            if($bankRes){
                $bankRes = $bankRes->toArray();
                $valueItem['bank_cname'] = $bankRes['bank_cname'];
            }
            $returnData[$valueItem['id']] = $valueItem['name'];
        }

        return $this->writeJson(
            200,
            [ ] ,
            $returnData,
            '成功',
            true,
            []
        );
    }

    function setApplyWithdrawalRes(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $code = $requestData['code'] ;

        // 5通过  10 拒绝
        $state = OnlineGoodsTiXianJiLu::$audit_state_refuse;
        if($requestData['res'] == 5 ){
            $state = OnlineGoodsTiXianJiLu::$audit_state_pass;
        }
        OnlineGoodsTiXianJiLu::updateById(
            $requestData['id'],
            [
                'audit_state' => $state,
                'audit_date' => date('Y-m-d H:i:s'),
                'audit_details' => $requestData['details']?:'',
            ]
        );

        return $this->writeJson(
            200,
            [ ] ,
            [

            ],
            '成功',
            true,
            []
        );
    }

    function offlinePay(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $code = $requestData['code'] ;

        $TiXianJiLu = OnlineGoodsTiXianJiLu::findById($requestData['id']);
        $uid = $TiXianJiLu->user_id;
        // 5成功 10失败
        $state = OnlineGoodsTiXianJiLu::$pay_state_failed;
        if($requestData['res'] == 5){
            $state = OnlineGoodsTiXianJiLu::$pay_state_succeed;
        }
        $res =  OnlineGoodsTiXianJiLu::updateById(
            $requestData['id'],
            [
                'pay_state' => $state,
                'pay_date' => date('Y-m-d H:i:s'),
                'pay_details' => $requestData['details']?:'',
                'attaches' => $requestData['pay_attaches']?:'',
            ]
        );
        if(!$res){
            return $this->writeJson(
                201,
                [ ] ,
                [

                ],
                '设置失败',
                true,
                []
            );
        }
        $TiXianJiLu = OnlineGoodsTiXianJiLu::findById($requestData['id']);
        //如果是打款成功 变更账户余额
        if($requestData['res'] == 5){
            OnlineGoodsUser::changeBalance(
                $uid,
                $TiXianJiLu->amount,
                OnlineGoodsUser::$banlance_type_jian_shao,
                '申请提现('.$requestData['id'].')_线下打款成功'
            );
        }

        return $this->writeJson(
            200,
            [ ] ,
            [

            ],
            '成功',
            true,
            []
        );
    }


    public function zhiJinUploadeFiles(){
        $requestData =  $this->getRequestData();

        $files = $this->request()->getUploadedFiles();

        $succeedNums = 0;
        $datas = [];
        foreach ($files as $key => $oneFile) {
            try {
                $fileName = $oneFile->getClientFilename();
                $path = OTHER_FILE_PATH . $fileName;
                if(file_exists($path)){
                    return $this->writeJson(203, [], [],'文件已存在！');;
                }

                $res = $oneFile->moveTo($path);
                if(!file_exists($path)){
                    CommonService::getInstance()->log4PHP( json_encode(['uploadeFiles   file_not_exists moveTo false ', 'params $path '=> $path,  ]) );
                    return $this->writeJson(203, [], [],'文件移动失败！');
                }

                $succeedNums ++;
                $datas[] = '/Static/OtherFile/'.$fileName;
            } catch (\Throwable $e) {
                return $this->writeJson(202, [], [],'导入失败'.$e->getMessage());
            }
        }

        return $this->writeJson(200, [], $datas,'导入成功 入库文件数量:'.$succeedNums);
    }


    /**
        *  发放佣金
        *  1: 生成分佣订单
        *  2：实际发放
     */
    function grantDaiKuanCommission(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $code = $requestData['code'] ;
        $id = $requestData['id'] ;

        $checkRes = DataModelExample::checkField(
            [
                'id' => [
                    'not_empty' => 1,
                    'field_name' => 'id',
                    'err_msg' => '请指定置订单',
                ],
            ],
            $requestData
        );
        if(
            !$checkRes['res']
        ){
            return $this->writeJson(203,[ ] , [], $checkRes['msgs'], true, []);
        }

        //先添加佣金记录
        $res = OnlineGoodsUserDaikuanOrder::addCommissionInfoById($id);
        if(
            !$res
        ){
            return $this->writeJson(203,[ ] , [], '添加佣金记录失败', true, []);
        }
        //发放佣金
        $res =  OnlineGoodsUserDaikuanOrder::grantCommissionInfoById($id);
        if(
            !$res
        ){
            return $this->writeJson(203,[ ] , [], '发放佣金失败', true, []);
        }

        return $this->writeJson(
            200,
            [ ] ,
            [

            ],
            '成功',
            true,
            []
        );
    }

    function grantBaoXianCommission(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $code = $requestData['code'] ;
        $id = $requestData['id'] ;

        $checkRes = DataModelExample::checkField(
            [
                'id' => [
                    'not_empty' => 1,
                    'field_name' => 'id',
                    'err_msg' => '请指定置订单',
                ],
            ],
            $requestData
        );
        if(
            !$checkRes['res']
        ){
            return $this->writeJson(203,[ ] , [], $checkRes['msgs'], true, []);
        }

        $res = OnlineGoodsUserBaoXianOrder::addCommissionInfoById($id);
        if(
            !$res
        ){
            return $this->writeJson(203,[ ] , [], '添加佣金失败', true, []);
        }
        $res = OnlineGoodsUserBaoXianOrder::grantCommissionInfoById($id);
        if(
            !$res
        ){
            return $this->writeJson(203,[ ] , [], '发放佣金失败', true, []);
        }

        return $this->writeJson(
            200,
            [ ] ,
            [

            ],
            '成功',
            true,
            []
        );
    }


    //后台-提现审核列表
    function applyWithdrawalRecords(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $page = $requestData['page']?:1;
        $pageSize = $requestData['pageSize']?:20 ;
        $code = $requestData['code'] ;

        //提现审核列表
        $res = OnlineGoodsTiXianJiLu::findByConditionWithCountInfo(
            [

            ],
            $page,
            $pageSize
        );
        foreach ($res['data'] as  &$dataItem){
            $userInfo = OnlineGoodsUser::findById($dataItem['user_id']);
            $userInfo = $userInfo->toArray();
            $dataItem['account_type'] = '普通账户';
            if(OnlineGoodsUser::IsVip($userInfo)){
                $dataItem['account_type'] = 'VIP账户';
            }
            $dataItem['name'] = $userInfo['user_name'];
            $dataItem['zhi_jin_account'] = $userInfo['phone'];
            $dataItem['total_withdraw'] = '';
            $dataItem['total_income'] = '';
            $dataItem['money'] = $dataItem['amount'];
            $dataItem['user_money'] = $userInfo['money'];
            $dataItem['pass_date'] = $dataItem['audit_date'];
            $dataItem['audit_state_cname'] =  OnlineGoodsTiXianJiLu::getAuditStateMap()[$dataItem['audit_state']];
            $dataItem['pay_state_cname'] =  OnlineGoodsTiXianJiLu::getPayStateMap()[$dataItem['pay_state']];
            $dataItem['apply_date'] = date('Y-m-d H:i:s',$dataItem['created_at']);

        }
        return $this->writeJson(
            200,
            [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $res['total'],
                'totalPage' => ceil($res['total']/$pageSize) ,
            ],
            $res['data'],
            '成功',
            true,
            []
        );
    }


    //
    function getZhiJinBaoXianLists(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $code = $requestData['code'] ;

        $dataRes = \App\HttpController\Service\BaoYa\BaoYaService::getProductsV2();

//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                __CLASS__.__FUNCTION__ .__LINE__,
//                'getZhiJinBaoXianLists_$dataRes' => $dataRes
//            ])
//        );
        return $this->writeJson(
            200,
            [ ] ,
            $dataRes,
            '成功',
            true,
            []
        );
    }

    function zhiJinUserLists(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $code = $requestData['code'] ;

        return $this->writeJson(
            200,
            [ ] ,
            [
                1=>'宝花',
                2=>'宝树',
                3=>'小花',
                4=>'小树',
            ],
            '成功',
            true,
            []
        );
    }

    function incomeLists(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']?:1;
        $pageSize =  $requestData['pageSize']?:100;

        $userInfo = $this->loginUserinfo;

        CommonService::writeTestLog(
            [
                'getInvitationCode'=>[
                    '$userInfo'=>[
                        'id'=>$userInfo['id'],
                        'user_name'=>$userInfo['user_name'],
                        'phone'=>$userInfo['phone'],
                    ],
                ]
            ]
        );

        $exampleDatas = [
            [
                'id'=>1,
                //产品名称
                'product_name'=>'美人贷',
                'avatar'=>'http://api.test.meirixindong.com/Static/OtherFile/default_avater.png',
                'purchaser_mobile'=>'132****6193',
                //产品id
                'product_id'=>1,
                //购买人
                'purchaser'=>'张小花',
                //介绍人
                'introducer'=>'张大花',
                //介绍人所得分佣比例
                'introducer_commision'=>'50%',
                //订单金额
                'price'=>10000,
                //信动所得佣金 - 佣金表
                'xindong_commission'=>500,
                'commission'=>50,
                //设置分佣状态
                'commission_set_state_cname'=>'已设置分佣',
                //分佣状态
                'commission_state_cname'=>'已领取分佣',
                //下单时间
                'order_time'=>'2022-09-09',
                'created_at'=>1665367946,
                'state'=>1,
                'state_cname'=> '已成交',
            ]
        ];
        $total = 100 ;
        return $this->writeJson(
            200,
            [
                'page' => $page,
                'pageSize' =>$pageSize,
                'total' => $total,
                'totalPage' => ceil( $total/ $pageSize ),
            ] ,
            $exampleDatas
            ,
            '成功',
            true,
            []
        );
    }



    function ZhiJinFansOrderLists(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']?:1;
        $pageSize =  $requestData['pageSize']?:100;

        $userInfo = $this->loginUserinfo;

        CommonService::writeTestLog(
            [
                'getInvitationCode'=>[
                    '$userInfo'=>[
                        'id'=>$userInfo['id'],
                        'user_name'=>$userInfo['user_name'],
                        'phone'=>$userInfo['phone'],
                    ],
                ]
            ]
        );

        $exampleDatas = [
            [
                'id'=>1,
                //用户姓名
                'name'=>  '张三',
                //邀请人姓名
                'inviter'=>  '张大三',
                //订单数量
                'order_nums'=>  '100',
                //累计收益
                'total_income'=>  '1000',
                //粉丝数量
                'total_fan_nums'=>  '1000',

                //头像
                'avatar'=> 'http://api.test.meirixindong.com/Static/OtherFile/default_avater.png',
                //加入时间
                'join_at'=>'2022-10-09',
                'created_at'=>1665367946,
                'state'=>1,
                'state_cname'=> '',
            ]
        ];
        $total = 100 ;
        return $this->writeJson(
            200,
            [
                'page' => $page,
                'pageSize' =>$pageSize,
                'total' => $total,
                'totalPage' => ceil( $total/ $pageSize ),
            ] ,
            $exampleDatas
            ,
            '成功',
            true,
            []
        );
    }

    //置金粉丝列表
    function ZhiJinFansLists(): bool
    {


        $requestData =  $this->getRequestData();
        $page =  $requestData['page']?:1;
        $pageSize =  $requestData['pageSize']?:100;
        $userInfo = $this->loginUserinfo;

        //$isVip = OnlineGoodsUser::IsVipV2($userInfo['id']);
        $isVip = OnlineGoodsUser::IsVipV2(1);
        $inviters = OnlineGoodsUserInviteRelation::getVipsAllInvitedUser(1);
        foreach ($inviters as $inviterData){
            $tmpUserInfo = OnlineGoodsUser::findById($inviterData['user_id']);
            $tmpUserInfo = $tmpUserInfo->toArray();
            $inviterData['user_commission_amount'] = 1000 ;
            //$inviterData['user_avatar'] = $tmpUserInfo['avatar'] ;
            $inviterData['user_avatar'] = 'http://api.test.meirixindong.com/Static/OtherFile/default_avater.png' ;
            $inviterData['user_name'] = $tmpUserInfo['user_name'] ;
            $inviterData['user_join_time'] = date('Y-m-d H:i:s',$tmpUserInfo['created_at']) ;

            $tmpUserInvoterInfo = OnlineGoodsUser::findById($inviterData['invite_by']);
            $tmpUserInvoterInfo = $tmpUserInvoterInfo->toArray();
            $inviterData['invite_user_name'] = $tmpUserInvoterInfo['user_name'] ;
        }
        //找到所有的粉丝
        // vip 》粉丝》
        CommonService::writeTestLog(
            [
                'ZhiJinFansLists'=>[
                    $inviters
                ]
            ]
        );

        $exampleDatas = [
            [
                'id'=>1,
                //用户姓名
                'name'=>  '张三',
                //邀请人姓名
                'inviter'=>  '张大三',
                //订单数量
                'order_nums'=>  '100',
                //累计收益
                'total_income'=>  '1000',
                //粉丝数量
                'total_fan_nums'=>  '1000',

                //头像
                'avatar'=>  'http://api.test.meirixindong.com/Static/OtherFile/default_avater.png',
                //加入时间
                'join_at'=>'2022-10-09',
                'created_at'=>1665367946,
                'state'=>1,
                'state_cname'=> '',
            ]
        ];
        $total = 100 ;
        return $this->writeJson(
            200,
            [
                'page' => $page,
                'pageSize' =>$pageSize,
                'total' => $total,
                'totalPage' => ceil( $total/ $pageSize ),
            ] ,
            $inviters
            ,
            '成功',
            true,
            []
        );
    }

    /**
    product_id: 2
    purchaser_id:
    phone: 18618457910
    purchaser_name: a
    purchaser_phone: a
    zhijin_phone: 13269706193
    amount: 500
    xindong_commission:
    xindong_commission_rate: 20
    commission_rate: 15
    order_date: 2022-10-09T16:00:00.000Z
    commission_date: 2022-09-25T16:00:00.000Z
    remark:
     */
    function addLoanOrder(): bool
    {
        $requestData =  $this->getRequestData();

        $userInfo = $this->loginUserinfo;

        $checkRes = DataModelExample::checkField(
            [
                'id' => [
                    'not_empty' => 1,
                    'field_name' => 'zhijin_phone',
                    'err_msg' => '请指定置金账号',
                ],
            ],
            $requestData
        );
        if(
            !$checkRes['res']
        ){
            return $this->writeJson(203,[ ] , [], $checkRes['msgs'], true, []);
        }


        //校验置金手机号
        if(
            !OnlineGoodsUser::findByPhone($requestData['zhijin_phone'])
        ){
            return $this->writeJson(203,[ ] , [], '置金账号有问题', true, []);
        };

        CommonService::writeTestLog(
            [
                'getInvitationCode'=>[
                    '$userInfo'=>[
                        'id'=>$userInfo['id'],
                        'user_name'=>$userInfo['user_name'],
                        'phone'=>$userInfo['phone'],
                    ],
                ]
            ]
        );

        if($requestData['xindong_commission_rate'] < $requestData['commission_rate']){
            return $this->writeJson(203,[ ] , [], '用户佣金比例不能大于信动佣金比例', true, []);
        }

      OnlineGoodsUserDaikuanOrder::addRecordV2([
          'product_id' => $requestData['product_id'],
          'purchaser_id' =>intval( $requestData['purchaser_id']),
          'input_person' =>$this->loginUserinfo['id'],
          'remark' => $requestData['remark'],
          'amount' => $requestData['amount'],
          'purchaser_name' => $requestData['purchaser_name'],
          'purchaser_phone' => $requestData['purchaser_phone'],
          'zhijin_phone' => $requestData['zhijin_phone'],
          'xindong_commission_rate' => $requestData['xindong_commission_rate'],
          'commission_rate' => $requestData['commission_rate'],
          'order_date' => $requestData['order_date'],
          'commission_set_state' => OnlineGoodsUserDaikuanOrder::$commission_set_state_succeed,
          'commission_state' => OnlineGoodsUserDaikuanOrder::$commission_state_init,
          'commission_date' => $requestData['commission_date'],
          'xindong_commission' => $requestData['xindong_commission'],
      ]);
        return $this->writeJson(
            200,
            [
            ] ,
           true
            ,
            '成功',
            true,
            []
        );
    }

    function addBaoXianOrder(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']?:1;
        $pageSize =  $requestData['pageSize']?:100;

        $userInfo = $this->loginUserinfo;

        $checkRes = DataModelExample::checkField(
            [
                'id' => [
                    'not_empty' => 1,
                    'field_name' => 'zhijin_phone',
                    'err_msg' => '请指定置金账号',
                ],
            ],
            $requestData
        );
        if(
            !$checkRes['res']
        ){
            return $this->writeJson(203,[ ] , [], $checkRes['msgs'], true, []);
        }

        //校验置金手机号
        if(
            !OnlineGoodsUser::findByPhone($requestData['zhijin_phone'])
        ){
            return $this->writeJson(203,[ ] , [], '置金账号有问题', true, []);
        };

        CommonService::writeTestLog(
            [
                'getInvitationCode'=>[
                    '$userInfo'=>[
                        'id'=>$userInfo['id'],
                        'user_name'=>$userInfo['user_name'],
                        'phone'=>$userInfo['phone'],
                    ],
                ]
            ]
        );

        OnlineGoodsUserBaoXianOrder::addRecordV2([
            'product_id' => $requestData['product_id'],
            'purchaser_id' =>intval( $requestData['purchaser_id']),
            'input_person' =>$this->loginUserinfo['id'],
            'remark' => $requestData['remark'],
            'amount' => $requestData['amount'],
            'purchaser_name' => $requestData['purchaser_name'],
            'purchaser_phone' => $requestData['purchaser_phone'],
            'zhijin_phone' => $requestData['zhijin_phone'],
            'xindong_commission_rate' => $requestData['xindong_commission_rate'],
            'commission_rate' => $requestData['commission_rate'],
            'order_date' => $requestData['order_date'],
            'commission_set_state' => OnlineGoodsUserBaoXianOrder::$commission_set_state_succeed,
            'commission_state' => OnlineGoodsUserBaoXianOrder::$commission_state_init,
            'commission_date' => $requestData['commission_date'],
            'xindong_commission' => $requestData['xindong_commission'],
        ]);
        return $this->writeJson(
            200,
            [
            ] ,
           true
            ,
            '成功',
            true,
            []
        );
    }

    // 用户-上传客户名单
    public function uploadBussinessFile(){
        $requestData =  $this->getRequestData();
        $files = $this->request()->getUploadedFiles();

        $succeedNums = 0;
        foreach ($files as $key => $oneFile) {
            try {
                $fileName = $oneFile->getClientFilename();
                $fileName = date('His').'_'.$fileName;
                $path = TEMP_FILE_PATH .$fileName;

                $ext = pathinfo($path);
                if(
                    $ext['extension']!='xlsx'
                ){
                    return $this->writeJson(203, [], [],'不是xlsx文件('.$ext['extension'].')！');;
                }


                if(file_exists($path)){
                    return $this->writeJson(203, [], [],'文件已存在！');;
                }

                $res = $oneFile->moveTo($path);
                if(!file_exists($path)){
                    return $this->writeJson(203, [], [],'文件移动失败！');
                }

                $addUploadRecordRes = AdminUserBussinessOpportunityUploadRecord::addRecordV2(
                    [
                        'user_id' => $this->loginUserinfo['id'],
                        'file_path' => TEMP_FILE_PATH,
                        'title' => $requestData['title']?:'',
                        'size' => filesize($path),
                        //是否拉取url联系人
                        'pull_api' => $requestData['pull_api']?1:0,
                        //按手机号拆分成多行
                        'split_mobile' => 1,
                        //删除空号
                        'del_empty' => 1,
                        //匹配微信
                        'match_by_weixin' => 1,
                        //取全字段
                        'get_all_field' => $requestData['get_all_field']?1:0,
                        //填充旧的微信
                        'fill_weixin' => 1,
                        'batch' =>  'BO'.date('YmdHis'),
                        'reamrk' => $requestData['reamrk']?:'',
                        'name' =>  $fileName,
                        'status' => AdminUserFinanceUploadRecord::$stateInit,
                    ]
                );

                if(!$addUploadRecordRes){
                    return $this->writeJson(203, [], [],'入库失败，请联系管理员');
                }
                $succeedNums ++;
            } catch (\Throwable $e) {
                return $this->writeJson(202, [], [],'导入失败'.$e->getMessage());
            }
        }

        return $this->writeJson(200, [], [],'导入成功 入库文件数量:'.$succeedNums);
    }

    public function uploadWeiXinFile(){
        $requestData =  $this->getRequestData();
        $files = $this->request()->getUploadedFiles();
        //return $this->writeJson(200, [], [],'导入成功 入库文件数量:');
        $succeedNums = 0;
        foreach ($files as $key => $oneFile) {
            try {
                $fileName = $oneFile->getClientFilename();
                $path = TEMP_FILE_PATH . $fileName;
                if(file_exists($path)){
                    return $this->writeJson(203, [], [],'文件已存在！');;
                }

                $res = $oneFile->moveTo($path);
                if(!file_exists($path)){
                    return $this->writeJson(203, [], [],'文件移动失败！');
                }

                $addUploadRecordRes = AdminUserWechatInfoUploadRecord::addRecordV2(
                    [
                        'user_id' => $this->loginUserinfo['id'],
                        'file_path' => TEMP_FILE_PATH,
                        'title' => $requestData['title']?:'',
                        'size' => filesize($path),
                        'batch' =>  'BO'.date('YmdHis'),
                        'reamrk' => $requestData['reamrk']?:'',
                        'name' =>  $fileName,
                        'status' => AdminUserWechatInfoUploadRecord::$status_init,
                    ]
                );

                if(!$addUploadRecordRes){
                    return $this->writeJson(203, [], [],'入库失败，请联系管理员');
                }
                $succeedNums ++;
            } catch (\Throwable $e) {
                return $this->writeJson(202, [], [],'导入失败'.$e->getMessage());
            }
        }

        return $this->writeJson(200, [], [],'导入成功 入库文件数量:'.$succeedNums);
    }

    public function WeiXinFilesList(){
        $requestData =  $this->getRequestData();
        $page = $requestData['page']?:1;
        $size = $requestData['pageSize']?:10;
        $records = AdminUserWechatInfoUploadRecord::findByConditionV2(
            [ ],
            $page
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                '$records'   => $records
            ])
        );
        foreach ($records['data'] as &$dataitem){
            $dataitem['status_cname'] = AdminUserWechatInfoUploadRecord::getStatusMap()[$dataitem['status']];
            $dataitem['size'] = self::convert($dataitem['size']) ;
        }
        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' => $size,
            'total' => $records['total'],
            'totalPage' => ceil($records['total']/$size) ,
        ],  $records['data'],'成功');
    }


    //用户-上传客户列表
    public function bussinessFilesList(){
        $requestData =  $this->getRequestData();
        $page = $requestData['page']?:1;
        $size = $requestData['pageSize']?:10;

        //bussinessFilesList
        $conditions = [];
        if(
            $requestData['name']
        ){
            $conditions[] = [
                'field' => 'name',
                'value' => '%'.$requestData['name'].'%',
                'operate' => 'like',
            ];
        }
        $createdAtStr = $requestData['created_at'];
        $createdAtArr = explode('|||',$createdAtStr);

        if (
            !empty($createdAtArr) &&
            !empty($createdAtStr)
        ) {
            $conditions[] =  [
                'field' => 'created_at',
                'value' => strtotime($createdAtArr[0].' 00:00:00'),
                'operate' => '>=',
            ];
            $conditions[] = [
                'field' => 'created_at',
                'value' => strtotime($createdAtArr[1]." 23:59:59"),
                'operate' => '<=',
            ];

        }

        $records = AdminUserBussinessOpportunityUploadRecord::findByConditionV2(
            $conditions,
            $page,
            $size
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                '$records'   => $records
            ])
        );
        foreach ($records['data'] as &$dataitem){
            $dataitem['status_cname'] = AdminUserBussinessOpportunityUploadRecord::getStatusMap()[$dataitem['status']];
            $dataitem['size'] = self::convert($dataitem['size']) ;
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'size1'   => $dataitem['size'],
                    'size2'   => self::convert($dataitem['size']),
                ])
            );
        }
        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' => $size,
            'total' => $records['total'],
            'totalPage' => ceil($records['total']/$size) ,
        ],  $records['data'],'成功');
    }

    static  function convert($size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }

    public function redownloadBussinessFile(){
        $requestData =  $this->getRequestData();
        if(
            $requestData['id'] <= 0
        ){
            return $this->writeJson(201, [
               ],  [],'参数缺失');
        }
        return $this->writeJson(200, [],
            AdminUserBussinessOpportunityUploadRecord::updateById(
                $requestData['id'],
                [
                    'status'=>AdminUserBussinessOpportunityUploadRecord::$status_check_mobile_success
                ]
            )
            ,'成功 ');
    }

}