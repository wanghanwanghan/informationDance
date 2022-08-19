<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Admin\SaibopengkeAdmin\FinanceChargeLog;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminNewUser;
use App\HttpController\Models\AdminV2\AdminUserChargeConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceChargeInfo;
use App\HttpController\Models\AdminV2\AdminUserFinanceConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceData;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataQueue;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadDataRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadeRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecordV3;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\CarInsuranceInstallment;
use App\HttpController\Models\AdminV2\CarInsuranceInstallmentMatchedRes;
use App\HttpController\Models\AdminV2\FinanceLog;
use App\HttpController\Models\AdminV2\NewFinanceData;
use App\HttpController\Models\AdminV2\OperatorLog;
use App\HttpController\Models\Api\AuthBook;
use App\HttpController\Models\MRXD\OnlineGoodsUser;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\GuoPiao\GuoPiaoService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use App\HttpController\Service\Sms\SmsService;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\Mysqli\QueryBuilder;
use Vtiful\Kernel\Format;
use wanghanwanghan\someUtils\control;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\XinDong\XinDongService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use EasySwoole\RedisPool\Redis;



class RunDealCarInsuranceInstallment extends AbstractCronTask
{
    public $crontabBase;
    public $filePath = ROOT_PATH . '/Static/Temp/';
    public static $workPath;

    static function strtr_func($str): string
    {
        $str = trim($str);

        if (empty($str)) {
            return '';
        }

        $arr = [
            '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4', '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
            'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E', 'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
            'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O', 'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
            'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y', 'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
            'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i', 'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
            'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's', 'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
            'ｙ' => 'y', 'ｚ' => 'z',
            '（' => '(', '）' => ')', '〔' => '(', '〕' => ')', '【' => '[', '】' => ']', '〖' => '[', '〗' => ']',
            '｛' => '{', '｝' => '}', '《' => '<', '》' => '>', '％' => '%', '＋' => '+', '—' => '-', '－' => '-',
            '～' => '~', '：' => ':', '。' => '.', '，' => ',', '、' => ',', '；' => ';', '？' => '?', '！' => '!', '…' => '-',
            '‖' => '|', '”' => '"', '’' => '`', '‘' => '`', '｜' => '|', '〃' => '"', '　' => ' ', '×' => '*', '￣' => '~', '．' => '.', '＊' => '*',
            '＆' => '&', '＜' => '<', '＞' => '>', '＄' => '$', '＠' => '@', '＾' => '^', '＿' => '_', '＂' => '"', '￥' => '$', '＝' => '=',
            '＼' => '\\', '／' => '/', '“' => '"', PHP_EOL => ''
        ];

        return str_replace([',', ' '], '', strtr($str, $arr));
    }

    //每次执行任务都会执行构造函数
    function __construct()
    {
        $this->crontabBase = new CrontabBase();
        $this->createDir(); 
    }

    static function getRule(): string
    {
        return '*/1 * * * *';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function createDir(): bool
    {
       
        self::$workPath = $this->filePath ;

        return true;
    }  
    
    static function setworkPath($filePath): bool
    {

        self::$workPath = $filePath ;

        return true;
    }  

    static function getYieldData($xlsx_name){
        $excel_read = new \Vtiful\Kernel\Excel(['path' => self::$workPath]);
        $excel_read->openFile($xlsx_name)->openSheet();

        $datas = [];
        while (true) {

            $one = $excel_read->nextRow([
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
            ]);

            if (empty($one)) {
                break;
            }

            $value0 = self::strtr_func($one[0]);  
            $value1 = self::strtr_func($one[1]);  
            $value2 = self::strtr_func($one[2]);  
            $value3 = self::strtr_func($one[3]);
            $tmpData = [
                $value0,
                $value1, 
                $value2, 
                $value3, 
            ] ;
            yield $datas[] = $tmpData;
        }
    }
     /*
      * step1:上传客户名单
      * step2:定时将客户名单解析到数据库
      * step3:定时算一下价格（大致价格|有的缺失数据的 需要拉完财务数据重算） 算一下总价
      * step4:点击导出-加入到队列
      * step5:跑api数据
      * step7:先去确认
      * step8:重算价格
      * step9:生成文件，扣费，记录导出详情
      * */
    function run(int $taskId, int $workerIndex): bool
    {

        //return  true;
        //防止重复跑
        if(
            !ConfigInfo::checkCrontabIfCanRun(__CLASS__)
        ){
            return     CommonService::getInstance()->log4PHP(json_encode(
                [
                    __CLASS__ . ' is already running  ',
                ]
            ));
        }

        //设置为正在执行中
        ConfigInfo::setIsRunning(__CLASS__);
        self::runMatch();
        ConfigInfo::setIsDone(__CLASS__);

        return true ;   
    }

    /**
        实际跑匹配
     */
    static function runMatch(){
        // 贷款产品
        //  AND   created_at <= ".strtotime("-30 minutes",time()) ."
        $rawDatas = CarInsuranceInstallment::findBySql(
            " WHERE 
                        status =  ".CarInsuranceInstallment::$status_init ."        
                  "
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'runMatch'=>[
                    'msg'=>'start',
                    'data_count'=>count($rawDatas),
                ]
            ])
        );
        foreach ($rawDatas as $rawDataItem){
            if(
                $rawDataItem['auth_id'] <= 0
            ){

                CarInsuranceInstallment::updateById(
                    $rawDataItem['id'],
                    [
                        'status'=>CarInsuranceInstallment::$status_matched_failed,
                    ]
                );
                continue ;
            }

            $authBook = AuthBook::findByIdV2($rawDataItem['auth_id']);
            if(
                $authBook['status'] != 5
            ){
                continue ;
            }

              //  匹配微商贷
            $res1 =  CarInsuranceInstallment::runMatchSuNing(intval($rawDataItem['id']));
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'runMatch'=>[
                        'msg'=>'run_match_su_ning',
                        'param_id'=>$rawDataItem['id'],
                        'res'=>$res1,
                    ]
                ])
            );
            //匹配结果
            $status = CarInsuranceInstallmentMatchedRes::$status_matched_failed;
            if( $res1['res']){
                $status = CarInsuranceInstallmentMatchedRes::$status_matched_succeed;
            }

            //保存匹配结果
            CarInsuranceInstallmentMatchedRes::addRecordV2(
                [
                    'user_id' => $rawDataItem['user_id'],
                    'product_id' => CarInsuranceInstallmentMatchedRes::$pid_wei_shang_dai,
                    'name' => CarInsuranceInstallmentMatchedRes::$pid_wei_shang_dai_cname,
                    'car_insurance_id' => $rawDataItem['id'],
                    'status' => $status,
                    'msg' => empty($res1['msg'])?'':json_encode($res1['msg']),
                    'created_at' => time(),
                    'updated_at' => time(),
                ]
            );

            //匹配金企贷
            $res2 =  CarInsuranceInstallment::runMatchJinCheng($rawDataItem['id']);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'runMatch'=>[
                        'msg'=>'run_match_jin_cheng',
                        'param_id'=>$rawDataItem['id'],
                        'res'=>$res2,
                    ]
                ])
            );
            $status = CarInsuranceInstallmentMatchedRes::$status_matched_failed;
            if($res2['res']){
                $status = CarInsuranceInstallmentMatchedRes::$status_matched_succeed;
            }
            CarInsuranceInstallmentMatchedRes::addRecordV2(
                [
                    'user_id' => $rawDataItem['user_id'],
                    'product_id' => CarInsuranceInstallmentMatchedRes::$pid_jin_qi_dai,
                    'name' => CarInsuranceInstallmentMatchedRes::$pid_jin_qi_dai_cname,
                    'car_insurance_id' => $rawDataItem['id'],
                    'status' => $status,
                    'msg' => empty($res2['msg'])?'':json_encode($res2['msg']),
                    'created_at' => time(),
                    'updated_at' => time(),
                ]
            );

              //匹配浦慧贷
            $res3 =  CarInsuranceInstallment::runMatchPuFa($rawDataItem['id']);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'runMatch'=>[
                        'msg'=>'run_match_pu_fa',
                        'param_id'=>$rawDataItem['id'],
                        'res'=>$res3,
                    ]
                ])
            );
            $status = CarInsuranceInstallmentMatchedRes::$status_matched_failed;
            if( $res3['res']){
                $status = CarInsuranceInstallmentMatchedRes::$status_matched_succeed;
            }

            CarInsuranceInstallmentMatchedRes::addRecordV2(
                [
                    'user_id' => $rawDataItem['user_id'],
                    'product_id' => CarInsuranceInstallmentMatchedRes::$pid_pu_hui_dai,
                    'name' => CarInsuranceInstallmentMatchedRes::$pid_pu_hui_dai_cname,
                    'car_insurance_id' => $rawDataItem['id'],
                    'status' => $status,
                    'msg' => empty($res3['msg'])?'':json_encode($res3['msg']),
                    'created_at' => time(),
                    'updated_at' => time(),
                ]
            );

            if(
                $res1['res'] ||
                $res2['res'] ||
                $res3['res']
            ){
                CarInsuranceInstallment::updateById(
                    $rawDataItem['id'],
                    [
                        'status'=>CarInsuranceInstallment::$status_matched_succeed
                    ]
                );
            }
            else{
                CarInsuranceInstallment::updateById(
                    $rawDataItem['id'],
                    [
                        'status'=>CarInsuranceInstallment::$status_matched_failed
                    ]
                );
            }

            //匹配结果
            $companyBasic = CompanyBasic::findByCode($rawDataItem['social_credit_code']);
            if(empty($companyBasic)){
                CarInsuranceInstallment::updateById(
                    $rawDataItem['id'],
                    [
                        'math_res' =>  json_encode(
                            [
                                'companyInfo' => [

                                ],
                                'essentialFinanceInfo' => [],
                                'mapedByDateNumsRes' => [],
                                'mapedByDateAmountRes' => [],
                                'topSupplier' => [],
                                'topCustomer' => [],
                                'matchedRes' => [],
                                'remark'=>[
                                    'match_company_failed'=>[
                                        'social_credit_code'=>$rawDataItem['social_credit_code']
                                    ]
                                ],
                            ]
                        )
                    ]
                );
                continue;
            }

            //=======================================
            $companyBasic = $companyBasic->toArray();
            $companyRes = (new XinDongService())->getEsBasicInfoV2($companyBasic['companyid']);

            //税务信息(今年)
            $essentialRes = (new GuoPiaoService())->getEssential($rawDataItem['social_credit_code']);
            $mapedEssentialRes = [
                "owingType" => $essentialRes['data']['owingType'],
                "payTaxes" => $essentialRes['data']['payTaxes'],
                "regulations" => $essentialRes['data']['regulations'],
                "nature" => $essentialRes['data']['nature'],
                "creditPoint" => $essentialRes['data']['essential'][0]['creditPoint'],
                "creditLevel" => $essentialRes['data']['essential'][0]['creditLevel'],
                "year" => $essentialRes['data']['essential'][0]['year'],
                "taxpayerId" => $essentialRes['data']['essential'][0]['taxpayerId'],
            ];
            //近两年发票开票金额 需要分页拉取后计算结果
            // $startDate 往前推一个月  推两年
            //纳税数据取得是两年的数据 取下开始结束时间
            $lastMonth = date("Y-m-01",strtotime("-1 month"));
            //两年前的开始月
            $last2YearStart = date("Y-m-d",strtotime("-2 years",strtotime($lastMonth)));
            //进销项发票信息 信动专用
            $allInvoiceDatas = CarInsuranceInstallment::getYieldInvoiceMainData(
                $rawDataItem['social_credit_code'],
                $last2YearStart,
                $lastMonth
            );

            //按日期格式化
            $mapedByDateAmountRes = [
                '01' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
                '02' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
                '03' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
                '04' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
                '05' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
                '06' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
                '07' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
                '08' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
                '09' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
                '10' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
                '11' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
                '12' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
            ];
            $mapedByDateNumsRes = [
                '01' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
                '02' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
                '03' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
                '04' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
                '05' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
                '06' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
                '07' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
                '08' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
                '09' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
                '10' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
                '11' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
                '12' => [
                    '2020'=>'',
                    '2021'=>'',
                    '2022'=>'',
                ],
            ];
            foreach ($allInvoiceDatas as $InvoiceData){
                $month = date('m',strtotime($InvoiceData['billingDate']));
                $year = date('Y',strtotime($InvoiceData['billingDate']));
                $mapedByDateAmountRes[$month][$year] += $InvoiceData['totalAmount'];
                //$mapedByDateAmountRes[$month][$year] = number_format($mapedByDateAmountRes[$month][$year],2);
                $mapedByDateNumsRes[$month][$year] ++;
            }

            //进销项发票信息 信动专用
            $allInvoiceDatas = CarInsuranceInstallment::getYieldInvoiceMainData(
                $rawDataItem['social_credit_code'],
                $last2YearStart,
                $lastMonth
            );
            foreach ($allInvoiceDatas as $InvoiceData){
                $month = date('m',strtotime($InvoiceData['billingDate']));
                $year = date('Y',strtotime($InvoiceData['billingDate']));
                $mapedByDateAmountRes[$month][$year] += $InvoiceData['totalAmount'];
                //$mapedByDateAmountRes[$month][$year] = number_format($mapedByDateAmountRes[$month][$year],2);
                $mapedByDateNumsRes[$month][$year] ++;
            }
            //销项
            $allInvoiceDatas = CarInsuranceInstallment::getYieldInvoiceMainData(
                $rawDataItem['social_credit_code'],
                $last2YearStart,
                $lastMonth,
                2
            );
            foreach ($allInvoiceDatas as $InvoiceData){
                $month = date('m',strtotime($InvoiceData['billingDate']));
                $year = date('Y',strtotime($InvoiceData['billingDate']));
                $mapedByDateAmountRes[$month][$year] += $InvoiceData['totalAmount'];
                //$mapedByDateAmountRes[$month][$year] = number_format($mapedByDateAmountRes[$month][$year],2);
                $mapedByDateNumsRes[$month][$year] ++;
            }
            //十大供应商
            //进销项发票信息 信动专用
            $allInvoiceDatas = CarInsuranceInstallment::getYieldInvoiceMainData(
                $rawDataItem['social_credit_code'],
                $last2YearStart,
                $lastMonth
            );
            $supplier = [];
            foreach ($allInvoiceDatas as $InvoiceData){
                $supplier[$InvoiceData['salesTaxName']]['entName'] = $InvoiceData['salesTaxName'] ;
                $supplier[$InvoiceData['salesTaxName']]['totalAmount'] += $InvoiceData['totalAmount'] ;
                //$supplier[$InvoiceData['salesTaxName']]['totalAmount'] = number_format($supplier[$InvoiceData['salesTaxName']]['totalAmount'],2);
            }
            //按时间倒叙排列
            usort($supplier, function($a, $b) {
                return $b['totalAmount'] <=> $a['totalAmount'];
            });
            $newSupplier = array_slice($supplier, 0, 10);


            //十大客户
            //销项项发票信息 信动专用
            $allInvoiceDatas = CarInsuranceInstallment::getYieldInvoiceMainData(
                $rawDataItem['social_credit_code'],
                $last2YearStart,
                $lastMonth,
                2
            );
            $customers = [];
            foreach ($allInvoiceDatas as $InvoiceData){
                $customers[$InvoiceData['purchaserName']]['entName'] = $InvoiceData['purchaserName'] ;
                $customers[$InvoiceData['purchaserName']]['totalAmount'] += $InvoiceData['totalAmount'] ;
                //$customers[$InvoiceData['purchaserName']]['totalAmount'] = number_format($customers[$InvoiceData['purchaserName']]['totalAmount'],2);
            }
            //按时间倒叙排列
            usort($customers, function($a, $b) {
                return $b['totalAmount'] <=> $a['totalAmount'];
            });
            $newCustomers =  array_slice($customers, 0, 10);


            //匹配结果
            $matchedRes = CarInsuranceInstallmentMatchedRes::findAllByCondition(
                [
                    'car_insurance_id'=>$rawDataItem['id']
                ]
            );
            $mathedResData = [];
            $unmathedResData = [];
            foreach ($matchedRes as &$matchedResItem){
                $matchedResItem['status_cname'] =  CarInsuranceInstallmentMatchedRes::getStatusMap()[$matchedResItem['status']];
                $matchedResItem['msg_arr'] =  $matchedResItem['msg']? json_decode($matchedResItem['msg'],true):[];
                if($matchedResItem['status']== CarInsuranceInstallmentMatchedRes::$status_matched_succeed){
                    $mathedResData[] =  $matchedResItem;
                }
                if($matchedResItem['status']== CarInsuranceInstallmentMatchedRes::$status_matched_failed){
                    $unmathedResData[] =  $matchedResItem;
                }
            }

            $userData = OnlineGoodsUser::findById($rawDataItem['user_id']);
            $userData = $userData->toArray();
            ;

            $res = SmsService::getInstance()->sendByTemplete(
                $userData['phone'], 'SMS_244025473',[
                'name' => '有询价的结果了',
                'money' => '多钱'
            ]);

            //只保留两位小鼠
            foreach ($mapedByDateNumsRes as $dataitem){
                foreach ($dataitem as &$subDataItem){
                    $subDataItem = number_format($subDataItem,2,".","");
                }
            }
            foreach ($mapedByDateAmountRes as $dataItem){
                foreach ($dataItem as &$subDataItem){
                    $subDataItem = number_format($subDataItem,2,".","");
                }
            }
            foreach ($newSupplier as &$dataItem){
                $dataItem['totalAmount'] = number_format($dataItem['totalAmount'],2,".","");
            }
            foreach ($newCustomers as &$dataItem){
                $dataItem['totalAmount'] = number_format($dataItem['totalAmount'],2,".","");
            }

            CarInsuranceInstallment::updateById(
                $rawDataItem['id'],
                [
                    'math_res' =>  json_encode(
                        [
                            'companyInfo' => [
                                'ENTNAME' => $companyRes['ENTNAME'],
                                'NAME' => $companyRes['NAME'],
                                'ESDATE' => $companyRes['ESDATE'],
                                'REGCAP' => $companyRes['REGCAP'],
                                'UNISCID' => $companyRes['UNISCID'],
                                'DOM' => $companyRes['DOM'],
                                'OPSCOPE' => $companyRes['OPSCOPE'],
                            ],
                            'essentialFinanceInfo' => $mapedEssentialRes,
                            'mapedByDateNumsRes' => $mapedByDateNumsRes,
                            'mapedByDateAmountRes' => $mapedByDateAmountRes,
                            'topSupplier' => $newSupplier,
                            'topCustomer' => $newCustomers,
                            'matchedRes' => $mathedResData,
                            'unmatchedRes' => $unmathedResData,
                        ]
                    )
                ]
            );

        }
        return true;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString());
    }

}
