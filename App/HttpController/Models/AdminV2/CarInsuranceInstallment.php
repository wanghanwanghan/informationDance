<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Models\RDS3\HdSaic\CodeCa16;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Service\ChuangLan\ChuangLanService;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\GuoPiao\GuoPiaoService;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
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

    public static function findOneByUserId($userId){
        $res =  CarInsuranceInstallment::create()
            ->where('user_id',$userId)
            ->order('id', 'DESC')
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
 *
*
* 苏宁银行-微商贷
    * //es 能放的
 * 税务信息table  今年(first one)
    * 税务信息
    * 纳税状态
    * 纳税信用等级
    * 纳税人种类
    * 历史有无欠税记录
    * 滞纳金
    * 评价分数
     *
    * //发票开票金额 ==    近两年
     *
     *      2021  2022
     * 01
     * 02
     *
     * 发票开票数量== //    近两年
     *      2021  2022
    * 01
    * 02
     *
    近两年十大客户  name code num money
    2021
    2022
    2023
    *
    *
    * 近两年十大供应商
    *
    * //苏宁
    *
    *
 *
*
*
* 金城银行-金企贷
 *
* 企业成立时间：满两年，纳税满1年 -- h库和财务三表
    * 纳税评级要求：ABCM （浙江C级不可做，其他地区可做） -- 国票接口
    * 企业类型：个人独资企业、有限责任公司准入；普通合伙人禁入 -- h库
    * 近两年任意一年纳税总额不得为0 -- 财务三表
    * ??? 无经营异常，近6个月无主营业务方向变更 -- 经营异常在h库
    * ??? 关联企业/法人无重大负面信息 -- 关联企业可以用企查查接口
    * 申请人：企业法人，无占股要求 -- h库
    * 年龄：18-60周岁 -- 用户输入
    * 变更要求：近6个月法人无变更 -- h库
 *
*
*
* 浦发银行-浦慧贷
 *
* 申请人年龄：20-65周岁，具有中国国籍（不含港、澳、台） -- 用户输入
    * 申请人：企业法定代表人，持股5%以上 -- h库
    * 法人变更要求：企业法人变更满6个月 -- h库
    * 实际经营时长不少于1年。 -- 发票
    * 年开票金额100万以上，近1年开票10月及以上 -- 发票
    * 连续未开票天数≤45天（2、3、4月除外） -- 发票
    * 近12个月累计开票张数≥35 -- 发票
 *
*
*
* 企业名称
    * 统一代码
    * 法人名称
    * 法人手机
    * 法人身份证
 */
    static  function  getEstablishmentYear($social_credit_code){
        $companyRes = CompanyBasic::findByCode($social_credit_code);
        $companyRes = $companyRes->toArray();
        //成立日期
        if($companyRes['ESDATE'] <= 0){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'getEstablishmentYear_$social_credit_code' => $social_credit_code,
                    'getEstablishmentYear_retrun_EstablishDate' => $companyRes['ESDATE'],
                    'getEstablishmentYear_retrun_EstablishYears' => 0,
                ])
            );
            return [
                'EstablishDate' => $companyRes['ESDATE'],
                'EstablishYears' => 0,
            ];
        };

        $d1 = new \DateTime(date('Y-m-d'));
        $d2 = new \DateTime('2008-03-09');

        $diff = $d2->diff($d1);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'getEstablishmentYear_$social_credit_code' => $social_credit_code,
                'getEstablishmentYear_retrun_EstablishDate' => $companyRes['ESDATE'],
                'getEstablishmentYear_retrun_EstablishYears' => $diff->y,
            ])
        );
     return [
         'EstablishDate' => $companyRes['ESDATE'],
         'EstablishYears' => $diff->y,
     ];
    }

    static  function  checkIfIsBrsanchCompany($social_credit_code) {
        $companyRes = CompanyBasic::findByCode($social_credit_code);
        $companyRes = $companyRes->toArray();
        $codeRes = CodeCa16::findByCode($companyRes['ENTTYPE']);
        $codeRes = $codeRes->toArray();
        if(strpos($codeRes['name'],'分公司') !== false){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'is_branch_company' => $codeRes['name'],
                ])
            );
            return true;
        }
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'not_branch_company' => $codeRes['name'],
            ])
        );
        return  false;
    }

    static  function  runMatch($carInsuranceDataId){
        $oneMonthsAgo = date("Y-m-01",strtotime("-1 month"));
        $twoMonthsAgo = date("Y-m-01",strtotime("-2 month"));
        $threeMonthsAgo = date("Y-m-01",strtotime("-3 month"));

         // 苏宁银行-微商贷
        $suNingWeiShangDai = true;
        $suNingWeiShangDaiErrMsg = [];

        $carInsuranceData = CarInsuranceInstallment::findById($carInsuranceDataId);
        $carInsuranceData = $carInsuranceData->toArray();

        $retrunData = [];

        //企业成立年限
        $EstablishRes = self::getEstablishmentYear($carInsuranceData['social_credit_code']);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'EstablishYears ' => $EstablishRes,
                '$suNingWeiShangDai' => $suNingWeiShangDai
            ])
        );
        // 苏宁银行-微商贷：公司成立2年以上
        if($EstablishRes['EstablishYears'] <= 2){
            $suNingWeiShangDai = false;
            $suNingWeiShangDaiErrMsg[] = '公司成立不到2年以上';
        }

        //是分公司  // 苏宁银行-微商贷： 不能是分公司
        $isBranchCompanyRes =  self::checkIfIsBrsanchCompany($carInsuranceData['social_credit_code']);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                '$isBranchCompanyRes ' => $isBranchCompanyRes,
                '$suNingWeiShangDai' => $suNingWeiShangDai
            ])
        );
        if( $isBranchCompanyRes ){
            $suNingWeiShangDai = false;
            $suNingWeiShangDaiErrMsg[] = '是分公司';
        }

        // 苏宁银行-微商贷：  申请人：贷款年龄：22-59周岁；申请人为法人（对占股无要求）、近6个月法人或最大股东变更满6个月 -- h库，用户输入
        //身份证年龄
        $legal_person_age =  self::ageVerification($carInsuranceData['legal_person_id_card']);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                '$legal_person_age ' => $legal_person_age,
                '$suNingWeiShangDai' => $suNingWeiShangDai
            ])
        );
        if(
            $legal_person_age <22 ||
            $legal_person_age >59
        ){
            $suNingWeiShangDai = false;
            $suNingWeiShangDaiErrMsg[] = '贷款年龄小于22或大于59';
        }

        // 苏宁银行-微商贷：   法人手机在网时长大于1年 -- 创蓝接口
        $phoneOnlineTimeRes = (new ChuangLanService())->yhzwsc($carInsuranceData['legal_phone']);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                '$phoneOnlineTimeRes ' => $phoneOnlineTimeRes,
                '$suNingWeiShangDai' => $suNingWeiShangDai
            ])
        );
        if(
            $phoneOnlineTimeRes['data']['result']['rangeStart'] < 12
        ){
            $suNingWeiShangDai = false;
            $suNingWeiShangDaiErrMsg[] = '在网时长小于一年';
        };

        // 苏宁银行-微商贷：   正常纳税满18个月 -- 财务三表
        $taxInfo = self::getQuarterTaxInfo($carInsuranceData['social_credit_code']);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                '$taxInfo ' => $taxInfo
            ])
        );
        if(
            $taxInfo['validQuarterLength'] <6
        ){
            $suNingWeiShangDai = false;
            $suNingWeiShangDaiErrMsg[] = '正常纳税不满18个月';
        }
        // 苏宁银行-微商贷：年纳税金额1.5万以上（PS：不做强要求，但不能为0） -- 财务三表
        $yearsTotal = 0;
        foreach ($taxInfo['validyearTaxInfo'] as $year=>$total){
            $yearsTotal += $total;
        };
        if($yearsTotal <= 0 ){
            $suNingWeiShangDai = false;
            $suNingWeiShangDaiErrMsg[] = '年纳税金额为0';
        }

        //企业税务基本信息查询
        $taxBasicInfo = (new GuoPiaoService())->getEssential($carInsuranceData['social_credit_code']);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                '$taxBasicInfo ' => $taxBasicInfo
            ])
        );
        //苏宁银行-微商贷：纳税等级A/B//M（个体工商户可准入） -- 国票接口
        if(
            !in_array($taxBasicInfo['data']['essential'][0]['creditLevel'],[
                'A','B','M'
            ])
        ){
            $suNingWeiShangDai = false;
            $suNingWeiShangDaiErrMsg[] = '纳税等级不属于A/B/M';
        };

        // 企业当前无欠税 -- 国票接口


    /*
    * 苏宁银行-微商贷
    * 纳税系统内录入最近一季资产负债表（53）、利润表（19 ），必须有三期以上财务报表 -- 财务三表
    * 企业当前无欠税 -- 国票接口
    * 近半年销售波动较小 -- 发票
         * */
        // 苏宁银行-微商贷： 无连续6个月不纳税情况 -- 财务三表
        if(
            $taxInfo['inValidQuarterLength'] >2
        ){
            $suNingWeiShangDai = false;
            $suNingWeiShangDaiErrMsg[] = '有连续6个月不纳税情况 ';
        }

        // 苏宁银行-微商贷：近3个月都有纳税申报记录 -- 增值税申报表
        $oneMonthsAgoHasNoTax = true;
        $twoMonthsAgoHasNoTax = true;
        $threeMonthsAgoHasNoTax = true;
        foreach ($taxInfo['zengZhiShuiReverseOrder'] as $dataItem){
            if(
                $dataItem['date'] == $oneMonthsAgo &&
                $dataItem['total'] > 0
            ){
                $oneMonthsAgoHasNoTax = false;
            }
            if(
                $dataItem['date'] == $twoMonthsAgo &&
                $dataItem['total'] > 0
            ){
                $twoMonthsAgoHasNoTax = false;
            }
            if(
                $dataItem['date'] == $threeMonthsAgo &&
                $dataItem['total'] > 0
            ){
                $threeMonthsAgoHasNoTax = true;
            }
        }

        if(
            $oneMonthsAgoHasNoTax ||
            $twoMonthsAgoHasNoTax ||
            $threeMonthsAgoHasNoTax
        ){
            $suNingWeiShangDai = false;
            $suNingWeiShangDaiErrMsg[] = '近3个月有未纳税申报记录 ';
        }

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
        //两年前的开始月
        $last2YearStart = date("Y-m-d",strtotime("-2 years",strtotime($lastMonth)));

        // 企业所得税是按照季度返回的
        $suoDeShui = [];
        $res = (new GuoPiaoService())->getIncometaxMonthlyDeclaration(
            $social_credit_code
        );
          CommonService::getInstance()->log4PHP(
              json_encode([
                  __CLASS__.__FUNCTION__ .__LINE__,
                  'getIncometaxMonthlyDeclaration$res' => $res
              ])
          );
        $data = jsonDecode($res['data']);
        foreach ($data as $dataItem){
            if($dataItem['columnSequence'] == 16){
                // $suoDeShui[] =  $dataItem;
                $suoDeShui[] =  [
                    'beginDate'=>$dataItem['beginDate'],
                    'currentAmount'=>$dataItem['currentAmount'],
                ];
            }
        }
        //按时间顺序排列
        usort($suoDeShui, function($a, $b) {
              return new \DateTime($a['beginDate']) <=> new \DateTime($b['beginDate']);
        });

        //最早的季度开始月份
        $QuarterBegainRaw = $last2YearStart;
        foreach ($suoDeShui as $dateItem){
          $day1 =  date('Y-m-d',strtotime($dateItem['beginDate']));
          $day2 = date('Y-m-d',strtotime('+3 months',strtotime($day1)));
          if(
              $day1 <= $last2YearStart &&
              $day2 >= $last2YearStart
          )
          {
              $QuarterBegainRaw = $day1;
          }
        }

        //从开始季度开始 到上个月
        $QuarterTaxInfo = [];
        $QuarterBegain  = $QuarterBegainRaw;
        while (true){
            if($QuarterBegain >=$lastMonth ){
                break;
            }

            $tmp = [
                'QuarterBegain' => $QuarterBegain,
            ];
            //匹配所得税
            foreach ($suoDeShui as $suoDeShuiItem){
                $tmpBeginDate = date('Y-m-d',strtotime($suoDeShuiItem['beginDate']));
                if(
                    $tmpBeginDate == $QuarterBegain
                ){
                    $tmp['suoDeShui_currentAmount'] = $suoDeShuiItem['currentAmount'];
                    break;
                }
            }

            $QuarterTaxInfo[] = $tmp;
            $QuarterBegain = date('Y-m-d',strtotime('+3 months',strtotime($QuarterBegain)));

        }

        //增值税
        $res = (new GuoPiaoService())->getVatReturn(
            $social_credit_code
        );

          $data = jsonDecode($res['data']);
          $zengZhiShuiRes = [];
          foreach ($data as $dataItem){
              if(in_array($dataItem['columnSequence'],[34,39,40,41]) ){
                  $zengZhiShuiRes[] =  $dataItem;
              }
          }

          $zengZhiShuiMapedRes = [];
          foreach ($zengZhiShuiRes as $zengZhiShuiItem){
              $beginDate = date('Y-m-d',strtotime($zengZhiShuiItem['beginDate'])) ;
              $zengZhiShuiMapedRes[$beginDate][$zengZhiShuiItem['columnSequence']] = $zengZhiShuiItem['generalMonthAmount'];
          }

          $zengZhiShuiResV2 = [];
          foreach ($zengZhiShuiMapedRes as $dateKey => $zengZhiShuiItem){
                $tmpRes = 0;
                foreach($zengZhiShuiItem as  $amount){
                    $tmpRes += $amount;
                }
              $zengZhiShuiResV2[$dateKey] = [
                  'date' => $dateKey,
                  'total' => $tmpRes,
                  'datails' => $zengZhiShuiItem
              ];
          }

        foreach ($QuarterTaxInfo as &$QuarterTaxItem){
            // 'QuarterBegain' => $QuarterBegain,
            // suoDeShui_currentAmount
            $zengZhiShuiRes = 0;
            $tmpDate1 =  $QuarterTaxItem['QuarterBegain'];
            $tmpDate2 =  date('Y-m-d',strtotime('+1 month',strtotime($QuarterTaxItem['QuarterBegain'])));
            $tmpDate3 =  date('Y-m-d',strtotime('+2 month',strtotime($QuarterTaxItem['QuarterBegain'])));
            foreach ($zengZhiShuiResV2 as $zengZhiShuiItem){
                if(
                    in_array($zengZhiShuiItem['date'],[ $tmpDate1, $tmpDate2, $tmpDate3])
                ){
                    $zengZhiShuiRes += $zengZhiShuiItem['total'];
                }
            }
            $QuarterTaxItem['zengZhiShui_currentAmount'] = $zengZhiShuiRes;
            $QuarterTaxItem['totalAmount'] = (
                $QuarterTaxItem['zengZhiShui_currentAmount'] + $QuarterTaxItem['suoDeShui_currentAmount']
            )  ;
            $QuarterTaxItem['totalAmount'] = number_format( $QuarterTaxItem['totalAmount'],2);
        }
        $validQuarterTaxInfo = [];
        $inValidQuarterTaxInfo = [];
        foreach ($QuarterTaxInfo as  $dataItem){
            if($dataItem['totalAmount']<=0){
                $inValidQuarterTaxInfo[]= $dataItem;
                continue;
            }
            $validQuarterTaxInfo[]= $dataItem;
        }
          //按时间顺序排列
          usort($validQuarterTaxInfo, function($a, $b) {
              return new \DateTime($a['QuarterBegain']) <=> new \DateTime($b['QuarterBegain']);
          });
          usort($inValidQuarterTaxInfo, function($a, $b) {
              return new \DateTime($a['QuarterBegain']) <=> new \DateTime($b['QuarterBegain']);
          });

          $validLength = CarInsuranceInstallment::getMaxContinuousDateLength(
              $validQuarterTaxInfo,'QuarterBegain',"+3 months"
          );
          $inValidLength = CarInsuranceInstallment::getMaxContinuousDateLength(
              $inValidQuarterTaxInfo,'QuarterBegain',"+3 months"
          );

        $validyearTaxInfo = [];

        foreach ($validQuarterTaxInfo as $dataItem){
            $tmpYear =  date('Y',strtotime($dataItem['QuarterBegain']));
            $validyearTaxInfo[$tmpYear]['totalAmount'] += $dataItem['totalAmount'];
            $validyearTaxInfo[$tmpYear]['totalAmount'] = number_format($validyearTaxInfo[$tmpYear]['totalAmount'],2);
        }

      $zengZhiShuiResV3 = $zengZhiShuiResV2;
      usort($zengZhiShuiResV3, function($a, $b) {
          return new \DateTime($b['date']) <=> new \DateTime($a['date']);
      });

        return [
            'suoDeShui' => $suoDeShui,
            'zengZhiShui' => $zengZhiShuiResV2,
            'zengZhiShuiReverseOrder' => $zengZhiShuiResV3,
            'quarterBeganDay' =>$QuarterBegainRaw,
            'QuarterTaxInfo' => $QuarterTaxInfo,
            'validQuarterTaxInfo'=>$validQuarterTaxInfo,
            'validQuarterLength'=>$validLength,
            'inValidQuarterLength'=>$inValidLength,
            'validyearTaxInfo'=>$validyearTaxInfo,
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

    static function getYieldInvoiceMainData($social_credit_code,$Start,$end,$type =1 ){
        $datas = [];
        $page = 1;
        $size = 10;

        while (true) {
            $jinXiaoXiangFaPiaoRes = (new GuoPiaoService())->getInvoiceMain(
                $social_credit_code,
                $type,
                $Start,
                $end,
                $page
            );
            $invoices = $jinXiaoXiangFaPiaoRes['data']['invoices'];
            if(empty($invoices)){
                break;
            }
            else{
                foreach ($invoices as $invoiceItem){
                    yield $datas[] = [

                        'totalAmount' => $invoiceItem['totalAmount'],
                        'billingDate' => $invoiceItem['billingDate'],
                        // $type = 1 时 本公司|进项|买方
                        'purchaserName' => $invoiceItem['purchaserName'],
                        //卖方
                        'salesTaxName' => $invoiceItem['salesTaxName'],
                    ];
                }
            }
            $page ++;
        }
    }
}
