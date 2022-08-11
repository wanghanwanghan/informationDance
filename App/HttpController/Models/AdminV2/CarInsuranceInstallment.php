<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Service\ChuangLan\ChuangLanService;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\GuoPiao\GuoPiaoService;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class CarInsuranceInstallment extends ModelBase
{

    protected $tableName = 'car_insurance_installment';

    static $status_init = 1;
    static $status_init_cname = '初始化';

    static $status_email_succeed = 5;
    static $status_email_succeed_cname = '发邮件成功';


    static  function  addRecordV2($info){

        if(
            self::findByUserIdAndEntName($info['ent_name'],$info['user_id'])
        ){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'CarInsuranceInstallment has old  record'=>[
                        'ent_name'=>$info['ent_name'],
                        'user_id'=>$info['user_id'],
                    ],

                ])
            );
            return  true;
        }

        return CarInsuranceInstallment::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  CarInsuranceInstallment::create()->data([
                'user_id' => $requestData['user_id'],
                'product_id' => $requestData['product_id']?:0,
                'ent_name' => $requestData['ent_name'],
                'legal_phone' => $requestData['legal_phone'],
                'order_no' => $requestData['order_no'],
               'legal_person' => $requestData['legal_person'],
               'legal_person_id_card' => $requestData['legal_person_id_card'],
               'social_credit_code' => $requestData['social_credit_code'],
               'auth_id' => $requestData['auth_id'],
                'url' => $requestData['url']?:'',
                'raw_return' => $requestData['raw_return']?:'',
                'status' => $requestData['status']?:1,
               'created_at' => time(),
               'updated_at' => time(),
           ])->save();

        } catch (\Throwable $e) {
            return CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'failed',
                    '$requestData' => $requestData
                ])
            );
        }
        return $res;
    }

    public static function cost(){
        $start = microtime(true);
        $startMemory = memory_get_usage();

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'memory_use' => round((memory_get_usage()-$startMemory)/1024/1024,3).' M',
                'costs_seconds '=> number_format(microtime(true) - $start,3)
            ])
        );
    }

    public static function findAllByCondition($whereArr){
        $res =  CarInsuranceInstallment::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = CarInsuranceInstallment::findById($id);

        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }

    public static function updateById(
        $id,$data
    ){
        $info = self::findById($id);
        return $info->update($data);
    }

    public static function findByConditionWithCountInfo($whereArr,$page){
        $model = CarInsuranceInstallment::create()
                ->where($whereArr)
                ->page($page)
                ->order('id', 'DESC')
                ->withTotalCount();

        $res = $model->all();

        $total = $model->lastQueryResult()->getTotalCount();
        return [
            'data' => $res,
            'total' =>$total,
        ];
    }

    public static function findByConditionV2($whereArr,$page){
        $model = CarInsuranceInstallment::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        $model->page($page)
            ->order('id', 'DESC')
            ->withTotalCount();

        $res = $model->all();

        $total = $model->lastQueryResult()->getTotalCount();
        return [
            'data' => $res,
            'total' =>$total,
        ];
    }

    public static function findById($id){
        $res =  CarInsuranceInstallment::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByUserIdAndEntName($ent_name,$user_id){
        $res =  CarInsuranceInstallment::create()
            ->where('ent_name',$ent_name)
            ->where('user_id',$user_id)
            ->get();
        return $res;
    }


    public static function findByName($user_id,$name,$product_id){
        $res =  CarInsuranceInstallment::create()
            ->where('name',$name)
            ->where('product_id',$product_id)
            ->where('user_id',$user_id)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = CarInsuranceInstallment::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    // 用完今日余额的
    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `car_insurance_installment` 
                            $where
        ";
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }


    public static function getDataLists($where,$page){
        $res =   CarInsuranceInstallment::findByConditionV2(
            $where,$page
        );
        $newData = [];
        foreach ($res['data'] as &$dataItem){
            $dataArr = json_decode(
                $dataItem['post_params'],true
            );
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    '$dataArr' => $dataArr,
                    '$dataItem'=>$dataItem,

                    'product_id'=>$dataArr['product_id'],
                    'post_params'=>$dataItem['post_params']
                ])
            );
            $dataRes = (new \App\HttpController\Service\BaoYa\BaoYaService())->getProductDetail
            (
                $dataArr['product_id']
            );

            $tmp = [
                'id'=>$dataItem['id'],
                'title' =>$dataRes['data']['title'],
                'description' =>$dataRes['data']['description'],
                'logo' =>$dataRes['data']['logo'],
                'created_at' => date('Y-m-d H:i:s',$dataItem['created_at']),
            ];
            $tmp = array_merge($tmp,$dataArr);
            $newData[] = $tmp;
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    '$dataRes' => $dataRes
                ])
            );
        }

        return [
            'data' => $newData,
            'total' => $res['total'],
        ];
    }

    /**
    苏宁银行-微商贷
    公司成立2年以上，不能是分公司
    申请人：贷款年龄：22-59周岁；申请人为法人（对占股无要求）、近6个月法人或最大股东变更满6个月
    法人手机在网时长大于1年
    正常纳税满18个月
    年纳税金额1.5万以上（PS：不做强要求，但不能为0）
    纳税等级A/B//M（个体工商户可准入）
    无连续6个月不纳税情况
    近3个月有纳税申报记录
    纳税系统内录入最近一季资产负债表、利润表，必须有三期以上财务报表
    企业当前无欠税
    近半年销售波动较小


    注意 ❗❗❗

    上文所有“所得税”指的是：所得税接口里，16栏的本期金额
    上文所有“增值税”指的是：增值税接口里，34,39,40,41项金额的总和

     */
    static  function  runMatch($carInsuranceDataId){
        $carInsuranceData = CarInsuranceInstallment::findById($carInsuranceDataId);
        $carInsuranceData = $carInsuranceData->toArray();

        $retrunData = [];

        //企业信息
        $companyRes = CompanyBasic::findByCode($carInsuranceData['social_credit_code']);
        $retrunData['social_credit_code'] = $carInsuranceData['social_credit_code'];
        $retrunData['companyBasic'] = $companyRes;
        if(empty($companyRes)){
            return $retrunData;
        }

        //成立日期
        $retrunData['company_ESDATE'] = $companyRes['ESDATE'];

        //身份证年龄
        $retrunData['legal_person_id_card'] = $carInsuranceData['legal_person_id_card'];
        $retrunData['legal_person_age'] =  self::ageVerification($carInsuranceData['legal_person_id_card']);

        //法人手机在网状态
        $res = (new ChuangLanService())->getCheckPhoneStatus([
            'mobiles' => $carInsuranceData['legal_phone'],
        ]);
        $retrunData['legal_phone_check_res'] = $res;
        if (!empty($res['data'])){
            foreach($res['data'] as $dataItem){
                if($dataItem['mobile'] == $carInsuranceData['legal_phone']){
                    $retrunData['legal_phone_check_resV2'] = $dataItem;
                }
            }
        }

        // 正常纳税满18个月 正常缴纳？每月有同时缴纳所得税或增值税即算正常缴纳。
        // 企业所得税
        $res = (new GuoPiaoService())->getIncometaxMonthlyDeclaration(
            $carInsuranceData['social_credit_code']
        );
        $data = jsonDecode($res['data']);
        foreach ($data as $dataItem){
            if($dataItem['columnSequence'] == 16){
                $retrunData['所得税'][] =  $dataItem;
            }
        }

        //所得税最长连续缴纳情况
        $retrunData['所得税-连续缴纳'] = self::getMaxContinuousDateLength(
            $retrunData['所得税'],'beginDate',"-3 months"
        );

        //企业税务基本信息查询
        $retrunData['企业税务基本信息'] = (new GuoPiaoService())->getEssential($carInsuranceData['social_credit_code']);

        //增值税信息
        $res = (new GuoPiaoService())->getVatReturn($carInsuranceData['social_credit_code']);
        $data = jsonDecode($res['data']);
        foreach ($data as $dataItem){
            if($dataItem['columnSequence'] == 16){
                $retrunData['所得税'][] =  $dataItem;
            }
        }
        $retrunData['增值税信息'] ;
        //年度资产负债
        $retrunData['年度资产负债'] = (new GuoPiaoService())->setCheckRespFlag(true)->getFinanceBalanceSheetAnnual($carInsuranceData['social_credit_code']);

        //年度利润表
        $retrunData['年度利润表'] =  (new GuoPiaoService())->getFinanceIncomeStatementAnnualReport($carInsuranceData['social_credit_code']);

        return  $retrunData;
    }

    /**
    获取企业季度纳税信息:
    1：企业所得税+增值税
    2：按季度
     */
      function  getQuarterTaxInfo($social_credit_code){
        //纳税数据取得是两年的数据 取下开始结束时间
        $lastMonth = date("Y-m-01",strtotime("-1 month"));
        $last2YearStart = date("Y-m-d",strtotime("-2 years",strtotime($lastMonth)));

        // 企业所得税是按照季度返回的
        $suoDeShui = [];
        $res = (new GuoPiaoService())->getIncometaxMonthlyDeclaration(
            $social_credit_code
        );
        $data = jsonDecode($res['data']);
        foreach ($data as $dataItem){
            if($dataItem['columnSequence'] == 16){
                $suoDeShui[] =  $dataItem;
            }
        }
        // Sort the array
          usort($suoDeShui, function($a, $b) {
              return new \DateTime($a['beginDate']) <=> new \DateTime($b['beginDate']);
          });

        // 企业所得税是按照季度返回的  以企业所得税的季度为准
//          2021 01
//          2022-2
//          2021 04
      $begainQuarter = "";
      foreach ($suoDeShui as $dateItem){
          $day1 =  date('Y-m-d',strtotime($dateItem['beginDate']));
          $day2 = date('Y-m-d',strtotime('+3 months',strtotime($day1)));
          if(
              $day1 >= $last2YearStart  
          ){
              $begainQuarter = $day1 ;
              break;
          }
      }

        return [
            $begainQuarter,
            $last2YearStart,
            $lastMonth
        ];
    }

    //获取最长连续时间
    static  function getMaxContinuousDateLength($tmpData,$field,$calStr){
        //最近一次的比较时间
        $lastDate = '';
        //运行次数
        $i = 1;
        //最大连续长度
        $length = 1;
        $ContinuousDateArr = [];
        foreach ($tmpData as $tmpDataItem) {

            $beginDate = date('Y-m-d', strtotime($tmpDataItem[$field]));
            // 第一次
            if ($i == 1) {
                $lastDate = $beginDate;
                CommonService::getInstance()->log4PHP(json_encode(
                    [
                        __CLASS__ ,
                        'times'=>$i,
                        'item date'=>$beginDate,
                        'last cal date'=>$lastDate,
                    ]
                ));
                $ContinuousDateArr = [$tmpDataItem];
                $i ++;
                continue;
            }

            $nextDate = date("Y-m-d", strtotime("$calStr", strtotime($lastDate)));
            CommonService::getInstance()->log4PHP(json_encode(
                [
                    __CLASS__ ,
                    'times'=>$i,
                    'item date'=>$beginDate,
                    'last cal date'=>$lastDate,
                    'next cal date'=>$nextDate,
                ]
            ));
            //如果连续了
            if (
                $beginDate == $nextDate
            ) {
                //连续长度加1
                $length++;
                $ContinuousDateArr[] = $tmpDataItem;
                CommonService::getInstance()->log4PHP(json_encode(
                    [
                        __CLASS__ ,
                        'times'=>$i,
                        'item date'=>$beginDate,
                        'last cal date'=>$lastDate,
                        'next cal date'=>$nextDate,
                        'ok'=>1,
                        '$length'=>$length,
                    ]
                ));
            } else {
                $length = 1;
                CommonService::getInstance()->log4PHP(json_encode(
                    [
                        __CLASS__ ,
                        'times'=>$i,
                        'item date'=>$beginDate,
                        'last cal date'=>$lastDate,
                        'next cal date'=>$nextDate,
                        'ok'=>0,
                        '$length'=>$length,
                    ]
                ));
                $ContinuousDateArr = [$tmpDataItem];
            }

            //重置上次连续时间
            $lastDate = $beginDate;
            $i++;
        }
        return [
            'ContinuousDate'=>$ContinuousDateArr,
            'length'=>$length,
        ];
    }

    //根据身份证获取年龄
    static function  ageVerification($code)

    {
        $age_time = strtotime(substr($code, 6, 8));
        if($age_time === false){
            return false;
        }
        list($y1,$m1,$d1) = explode("-",date("Y-m-d",$age_time));

        $now_time = strtotime("now");

        list($y2,$m2,$d2) = explode("-",date("Y-m-d",$now_time));
        $age = $y2 - $y1;
        if((int)($m2.$d2) < (int)($m1.$d1)){
            $age -= 1;
        }
        return $age;
    }

}
