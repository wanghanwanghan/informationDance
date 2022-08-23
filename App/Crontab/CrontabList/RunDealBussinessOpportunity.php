<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Admin\SaibopengkeAdmin\FinanceChargeLog;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminNewUser;
use App\HttpController\Models\AdminV2\AdminUserBussinessOpportunityUploadRecord;
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
use App\HttpController\Models\AdminV2\FinanceLog;
use App\HttpController\Models\AdminV2\NewFinanceData;
use App\HttpController\Models\AdminV2\OperatorLog;
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



class RunDealBussinessOpportunity extends AbstractCronTask
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


    /**
    sheet1：企业名称，税号，手机号（多个的话逗号隔开）

     */
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

            //company name
            $value0 = self::strtr_func($one[0]);
            //social code
            $value1 = self::strtr_func($one[1]);
            //phones | splite by ,
            //$value2 = self::strtr_func($one[2]);
            $value2 = trim($one[2]);
//            CommonService::getInstance()->log4PHP(
//                json_encode([
//                    __CLASS__.__FUNCTION__ .__LINE__,
//                    [
//                        'splitByMobile'=>[
//                            '$value0' => $value0,
//                            '$one[0]' => $one[0],
//                            '$value1' => $value1,
//                            '$one[1]' => $one[1],
//                            '$value2' => $value2,
//                            '$one[2]' => $one[2],
//                        ]
//                    ]
//                ])
//            );
            $tmpData = [
                $value0,
                $value1,
                $value2,
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
        /*

        非公开联系人文件：
        企业名单文件：
        微信名称名单：

        拆成多行
        去空号
        匹配库里微信名
        补全字段


         * */

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

        //将客户名单解析到db
        self::splitByMobile(10);

        //去空号


        //设置为已执行完毕
        ConfigInfo::setIsDone(__CLASS__);

        return true ;   
    }

    //单行拆解成多行
    static function  splitByMobile(){
        $rawDatas = AdminUserBussinessOpportunityUploadRecord::findBySql(
            " WHERE status =  ".AdminUserBussinessOpportunityUploadRecord::$status_init
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                [
                    'splitByMobile'=>[
                        'msg' => 'start',
                    ]
                ]
            ])
        );
        foreach ($rawDatas as $rawDataItem){
            //========================================================
            //========================================================
            //如果不需要拆分
            if(!$rawDataItem['split_mobile']){
                AdminUserBussinessOpportunityUploadRecord::updateById(
                    $rawDataItem['id'],
                    [
                        'status' => AdminUserBussinessOpportunityUploadRecord::$status_split_success
                    ]
                );
                continue ;
            }

            //需要拆分的

            // 找到上传的文件路径
            //$dirPath =  dirname($rawDataItem['file_path']).DIRECTORY_SEPARATOR;
            self::setworkPath( $rawDataItem['file_path'] );

            //先生成第一个sheet
            $config=  [
                'path' => TEMP_FILE_PATH // xlsx文件保存路径
            ];


            $excel = new \Vtiful\Kernel\Excel($config);
            $filename = '1_'.$rawDataItem['name'];
            $fileObject = $excel->fileName($filename, 'Sheet1');
            $fileHandle = $fileObject->getHandle();
            $format = new Format($fileHandle);
            $colorStyle = $format
                ->fontColor(Format::COLOR_ORANGE)
                ->border(Format::BORDER_DASH_DOT)
                ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
                ->toResource();
            $format = new Format($fileHandle);
            $alignStyle = $format
                ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
                ->toResource();
            $file = $fileObject
                ->defaultFormat($colorStyle)
                ->header(
                    [
                        '公司名称', //
                        '信用代码', //
                        '手机号', //
                    ]
                )
                 ->defaultFormat($alignStyle)
            ;

            //按行读取企业数据
            $companyDatas = self::getYieldData($rawDataItem['name']);
            foreach ($companyDatas as $dataItem){
                $fileObject ->data([$dataItem]);
            }

            //生成第二个sheet
            $file->addSheet('Sheet2')
                ->defaultFormat($colorStyle)
                ->header([
                    '公司名称' , //
                    '信用代码' , //
                    '手机号' , //
                ])
                ->defaultFormat($alignStyle)
            ;
            $companyDatas = self::getYieldData($rawDataItem['name']);
            foreach ($companyDatas as $dataItem){
                $mobilesArr = explode(',',$dataItem[2]);
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ .__LINE__,
                        [
                            'splitByMobile'=>[
                                '$mobilesArr' =>$mobilesArr,
                            ]
                        ]
                    ])
                );
                foreach ($mobilesArr as $mobiles){
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            __CLASS__.__FUNCTION__ .__LINE__,
                            [
                                'splitByMobile'=>[
                                    '$mobiles' =>$mobiles,
                                ]
                            ]
                        ])
                    );
                    $file->data(
                        [
                            [
                                $dataItem[0],
                                $dataItem[1],
                                $mobiles,
                            ]
                        ]
                    );
                }
            }
            //==============================================

            $format = new Format($fileHandle);
            //单元格有\n解析成换行
            $wrapStyle = $format
                ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
                ->wrap()
                ->toResource();

            $fileObject->output();

            //新文件
            AdminUserBussinessOpportunityUploadRecord::updateById(
                $rawDataItem['id'],
                [
                    'new_name' => $filename,
                    'status' => AdminUserBussinessOpportunityUploadRecord::$status_split_success,
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
