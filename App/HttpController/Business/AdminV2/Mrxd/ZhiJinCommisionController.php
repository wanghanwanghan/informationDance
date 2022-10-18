<?php

namespace App\HttpController\Business\AdminV2\Mrxd;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
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
use App\HttpController\Models\MRXD\OnlineGoodsDaikuanBank;
use App\HttpController\Models\MRXD\OnlineGoodsDaikuanProducts;
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

     loan-order
     loan-order

     */

    function loanOrderLists(): bool
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



        $datas = OnlineGoodsUserDaikuanOrder::findByConditionV2([],$page,$pageSize);
        $total = $datas['total'] ;
        foreach ($datas['data'] as $dataValue){

        }
        $retrundatas = $datas['data'] ;
        $retrundatas = [
            [
                'id'=>1,
                //产品名称
                'product_name'=>'美人贷',
                //产品id
                'product_id'=>1,
                //购买人
                'purchaser'=>'张小花',
                //订单金额
                'price'=>10000,
                //信动所得佣金 - 佣金表
                'xindong_commission'=>500,
                //设置分佣状态
                'commission_set_state_cname'=>'已设置分佣',
                //分佣状态
                'commission_state_cname'=>'已领取分佣',
                'created_at'=>1665367946,
                'state'=>1,
                'state_cname'=> '已成交',
            ],
            [
                'id'=>2,
                //产品名称
                'product_name'=>'帅哥贷',
                //产品id
                'product_id'=>1,
                //购买人
                'purchaser'=>'张大锤',
                //订单金额
                'price'=>10000,
                //信动所得佣金 - 佣金表
                'xindong_commission'=>500,
                //设置分佣状态
                'commission_set_state_cname'=>'已设置分佣',
                //分佣状态
                'commission_state_cname'=>'已领取分佣',
                'created_at'=>1665367946,
                'state'=>1,
                'state_cname'=> '已成交',
            ]
        ];
        $total = 100;

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

    function baoxianOrderLists(): bool
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



        $datas = OnlineGoodsUserBaoXianOrder::findByConditionV2([],$page,$pageSize);
        $total = $datas['total'] ;
        foreach ($datas['data'] as $dataValue){

        }
        $retrundatas = $datas['data'] ;
        $retrundatas = [
            [
                'id'=>1,
                //产品名称
                'product_name'=>'美人贷',
                //产品id
                'product_id'=>1,
                //购买人
                'purchaser'=>'张小花',
                //订单金额
                'price'=>10000,
                //信动所得佣金 - 佣金表
                'xindong_commission'=>500,
                //设置分佣状态
                'commission_set_state_cname'=>'已设置分佣',
                //分佣状态
                'commission_state_cname'=>'已领取分佣',
                'created_at'=>1665367946,
                'state'=>1,
                'state_cname'=> '已成交',
            ],
            [
                'id'=>2,
                //产品名称
                'product_name'=>'帅哥贷',
                //产品id
                'product_id'=>1,
                //购买人
                'purchaser'=>'张大锤',
                //订单金额
                'price'=>10000,
                //信动所得佣金 - 佣金表
                'xindong_commission'=>500,
                //设置分佣状态
                'commission_set_state_cname'=>'已设置分佣',
                //分佣状态
                'commission_state_cname'=>'已领取分佣',
                'created_at'=>1665367946,
                'state'=>1,
                'state_cname'=> '已成交',
            ]
        ];
        $total = 100;

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

    function applyWithdrawalRecords(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $code = $requestData['code'] ;

        return $this->writeJson(
            200,
            [ ] ,
            [
                [
                    'id'=>1,
                    'money'=>1000,
                    'state_cname' => '审核中',
                    'pay_state_cname' => '待打款',
                    'user_id' => 1,
                    'user_name' =>  '李循环',
                    'user_money' =>  100,
                    // 详情
                    'details' => '',
                    'created_at'=>1665367946,
                    'attaches'=>[],
                    'pass_date'=> '2022-09-10 10:00:00',
                    'pay_date'=> '2022-09-10 10:00:00',
                ],
                [
                    'id'=>2,
                    'money'=>1000,
                    'state_cname' => '审核中',
                    'pay_state_cname' => '待打款',
                    'user_id' => 1,
                    'user_name' =>  '李循环',
                    'user_money' =>  100,
                    // 详情
                    'details' => '',
                    'created_at'=>1665367946,
                    'attaches'=>[],
                    'pass_date'=> '2022-09-10 10:00:00',
                    'pay_date'=> '2022-09-10 10:00:00',
                ],
                [
                    'id'=>3,
                    'money'=>1000,
                    'state_cname' => '审核中',
                    'pay_state_cname' => '待打款',
                    'user_id' => 1,
                    'user_name' =>  '李循环',
                    'user_money' =>  100,
                    // 详情
                    'details' => '',
                    'created_at'=>1665367946,
                    'attaches'=>[],
                    'pass_date'=> '2022-09-10 10:00:00',
                    'pay_date'=> '2022-09-10 10:00:00',
                ]
            ],
            '成功',
            true,
            []
        );
    }

    function getZhiJinBaoXianLists(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $code = $requestData['code'] ;

        $dataRes = (new \App\HttpController\Service\BaoYa\BaoYaService())->getProducts();
        $returnRes = [
            9999 => '车险分期',
        ];
        foreach ($dataRes['data'] as $valueItem){
            $returnRes[$valueItem['id']] = $valueItem['title'];
        }
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                __CLASS__.__FUNCTION__ .__LINE__,
//                'getZhiJinBaoXianLists_$dataRes' => $dataRes
//            ])
//        );
        return $this->writeJson(
            200,
            [ ] ,
            $returnRes,
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
            $inviterData['user_avatar'] = $tmpUserInfo['avatar'] ;
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
                'avatar'=>  '/static/img/aaa.jpg',
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


    function addLoanOrder(): bool
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
          OnlineGoodsUserDaikuanOrder::addRecordV2([
              'product_id' => $requestData['product_id'],
              'purchaser_id' =>intval( $requestData['purchaser_id']),
              'remark' => $requestData['remark'],
              'amount' => $requestData['amount'],
              'purchaser_name' => $requestData['purchaser_name'],
              'purchaser_phone' => $requestData['purchaser_phone'],
              'zhijin_phone' => $requestData['zhijin_phone'],
              'xindong_commission_rate' => $requestData['xindong_commission_rate'],
              'commission_rate' => $requestData['commission_rate'],
              'order_date' => $requestData['order_date'],
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