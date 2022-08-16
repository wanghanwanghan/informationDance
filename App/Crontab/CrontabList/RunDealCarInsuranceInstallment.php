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
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Service\Common\CommonService;
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

    static function runMatch(){
        // 车险分期
        $rawDatas = CarInsuranceInstallment::findBySql(
            " WHERE status =  ".CarInsuranceInstallment::$status_init ."
                        AND   created_at <= ".strtotime("-30 minutes",time()) ."
            
            "
        );
        foreach ($rawDatas as $rawDataItem){
              //微商贷
              $res1 =  CarInsuranceInstallment::runMatchSuNing(intval($rawDataItem['id']));
              $status = CarInsuranceInstallmentMatchedRes::$status_matched_failed;
              if( $res1['res']){
                  $status = CarInsuranceInstallmentMatchedRes::$status_matched_succeed;
              }
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

              //金企贷
              $res2 =  CarInsuranceInstallment::runMatchJinCheng($rawDataItem['id']);
                $status = CarInsuranceInstallmentMatchedRes::$status_matched_failed;
                if( $res2['res']){
                    $status = CarInsuranceInstallmentMatchedRes::$status_matched_succeed;
                }
            CarInsuranceInstallmentMatchedRes::addRecordV2(
                [
                    'user_id' => $rawDataItem['user_id'],
                    'product_id' => CarInsuranceInstallmentMatchedRes::$pid_jin_qi_dai,
                    'name' => CarInsuranceInstallmentMatchedRes::$pid_jin_qi_dai_cname,
                    'car_insurance_id' => $rawDataItem['id'],
                    'status' => $status,
                    'msg' => empty($res1['msg'])?'':json_encode($res1['msg']),
                    'created_at' => time(),
                    'updated_at' => time(),
                ]
            );

              //浦慧贷
              $res3 =  CarInsuranceInstallment::runMatchPuFa($rawDataItem['id']);
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
                    'msg' => empty($res1['msg'])?'':json_encode($res1['msg']),
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

        }
        return true;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString());
    }

}
