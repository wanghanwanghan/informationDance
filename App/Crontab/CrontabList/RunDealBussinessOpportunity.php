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
use App\HttpController\Models\AdminV2\AdminUserWechatInfoUploadRecord;
use App\HttpController\Models\AdminV2\BussinessOpportunityDetails;
use App\HttpController\Models\AdminV2\FinanceLog;
use App\HttpController\Models\AdminV2\NewFinanceData;
use App\HttpController\Models\AdminV2\OperatorLog;
use App\HttpController\Models\BusinessBase\WechatInfo;
use App\HttpController\Models\RDS3\HdSaic\CodeCa16;
use App\HttpController\Models\RDS3\HdSaic\CodeEx02;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Models\RDS3\NicCode;
use App\HttpController\Service\ChuangLan\ChuangLanService;
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
    static $sheet1 = 'Sheet1';
    static $sheet2 = 'Sheet2';

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

        self::delEmptyMobile();
        self::generateNewFile();
        self::addWeChatInfo();


        //设置为已执行完毕
        ConfigInfo::setIsDone(__CLASS__);

        return true ;   
    }
    static  function getYieldDataBySheet($excel_read){
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

            //企业名称
            $value0 = self::strtr_func($one[0]);
            //手机号
            $value1 = self::strtr_func($one[1]);
            //微信名
            $value2 = self::strtr_func($one[2]);
            $value3 = self::strtr_func($one[3]);

            yield $datas[] = [
                $value0,
                $value1,
                $value2,
            ];
        }
    }
    //单行拆解成多行
    static function  splitByMobileOld(){
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
            $fileObject = $excel->fileName($filename, self::$sheet1);
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
            $file->addSheet(self::$sheet2)
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

    //拆解成多行
    static function  splitByMobileOld2(){
        $rawDatas = AdminUserBussinessOpportunityUploadRecord::findBySql(
            " WHERE status =  ".AdminUserBussinessOpportunityUploadRecord::$status_check_mobile_success
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

            // 找到上传的文件路径
            //$dirPath =  dirname($rawDataItem['file_path']).DIRECTORY_SEPARATOR;
            self::setworkPath( $rawDataItem['file_path'] );

            //先生成第一个sheet
            $config=  [
                'path' => TEMP_FILE_PATH // xlsx文件保存路径
            ];

            $excel = new \Vtiful\Kernel\Excel($config);
            $filename = '1_'.$rawDataItem['new_name'];
            $fileObject = $excel->fileName($filename, self::$sheet1);
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
            $file->addSheet(self::$sheet2)
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


    //去空号
    static function  delEmptyMobileOld(){
        $rawDatas = AdminUserBussinessOpportunityUploadRecord::findBySql(
            " WHERE status =  ".AdminUserBussinessOpportunityUploadRecord::$status_split_success
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                [
                    'delEmptyMobile'=>[
                        'msg' => 'start',
                    ]
                ]
            ])
        );
        foreach ($rawDatas as $rawDataItem){
            //========================================================
            //========================================================
            //如果不需要去空号
            if(!$rawDataItem['del_empty']){
                AdminUserBussinessOpportunityUploadRecord::updateById(
                    $rawDataItem['id'],
                    [
                        'status' => AdminUserBussinessOpportunityUploadRecord::$status_check_mobile_success
                    ]
                );
                continue ;
            }

            //需要去空号的

            // 找到上传的文件路径
            //$dirPath =  dirname($rawDataItem['file_path']).DIRECTORY_SEPARATOR;
            self::setworkPath( $rawDataItem['file_path'] );

            //=====================按sheet读取旧的文件======================================
            $excel = new \Vtiful\Kernel\Excel(['path' =>  TEMP_FILE_PATH]);
            // 打开示例文件
            $sheetList = $excel->openFile($rawDataItem['new_name'])
                ->sheetList();
            foreach ($sheetList as $sheetName) {
                // 通过工作表名称获取工作表数据
                $excel = $excel
                    ->openSheet($sheetName);// ->getSheetData();
                if(
                    $sheetName == self::$sheet1
                ){
                    $sheet1 =   RunDealBussinessOpportunity::getYieldDataBySheet($excel);
                }
                if(
                    $sheetName == self::$sheet2
                ){
                    $sheet2 =   RunDealBussinessOpportunity::getYieldDataBySheet($excel);
                }
            }

            //=====================按sheet读取旧的文件======================================
            //先生成第一个sheet
            $config=  [
                //'path' => TEMP_FILE_PATH // xlsx文件保存路径
                'path' => $rawDataItem['file_path'] // xlsx文件保存路径
            ];


            $excel = new \Vtiful\Kernel\Excel($config);
            $filename = '1_'.$rawDataItem['name'];
            if($rawDataItem['new_name']){
                $filename = '1_'.$rawDataItem['new_name'];
            }

            $fileObject = $excel->fileName($filename, self::$sheet1);
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
                        '有效手机号', //
                    ]
                )
                 ->defaultFormat($alignStyle)
            ;

            //按行读取企业数据
            foreach ($sheet1 as $dataItem){

                $mobileStr = str_replace(";", ",", trim($dataItem[2]));
                $newmobileStr = "";
                if(!empty($mobileStr)){
                    $res = (new ChuangLanService())->getCheckPhoneStatus([
                        'mobiles' => $mobileStr,
                    ]);
                    if (!empty($res['data'])){// $res['data']还能是空呢?
                        foreach($res['data'] as $dataItem){
                            if($dataItem['status'] == 1){
                                $newmobileStr .= $dataItem["mobile"].';';
                            }
                        }
                    }
                }
                $fileObject ->data([
                    [
                        $dataItem[0],
                        $dataItem[1],
                        $dataItem[2],
                        $newmobileStr,
                    ]
                ]);
            }

            //生成第二个sheet
            $file->addSheet(self::$sheet2)
                ->defaultFormat($colorStyle)
                ->header([
                    '公司名称' , //
                    '信用代码' , //
                    '手机号' , //
                    '状态' , //
                ])
                ->defaultFormat($alignStyle)
            ;
            foreach ($sheet2 as $dataItem){
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

    static function  delEmptyMobileOld2(){
        $rawDatas = AdminUserBussinessOpportunityUploadRecord::findBySql(
            " WHERE status =  ".AdminUserBussinessOpportunityUploadRecord::$status_init
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                [
                    'delEmptyMobile'=>[
                        'msg' => 'start',
                    ]
                ]
            ])
        );
        foreach ($rawDatas as $rawDataItem){
            //========================================================
            //========================================================
            //如果不需要去空号
            if(!$rawDataItem['del_empty']){
                AdminUserBussinessOpportunityUploadRecord::updateById(
                    $rawDataItem['id'],
                    [
                        'status' => AdminUserBussinessOpportunityUploadRecord::$status_check_mobile_success
                    ]
                );
                continue ;
            }

            // 找到上传的文件路径
            self::setworkPath( $rawDataItem['file_path'] );

            $newName = '1_'.$rawDataItem['name'];
            //==============================生成文件start=====================================
            $config=  [
                'path' => TEMP_FILE_PATH // xlsx文件保存路径
            ];

            $excel = new \Vtiful\Kernel\Excel($config);
            $fileObject = $excel->fileName($newName, self::$sheet1);
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
                        '企业名称' , //
                        '税号' , //
                        '手机号' , //
                        '有效手机号' , //
                    ]
                )
                 ->defaultFormat($alignStyle)  ;

            $companyDatas = self::getYieldData($rawDataItem['name']);
            foreach ($companyDatas as $dataItem){
                //============================================
                $mobileStr = str_replace(";", ",", trim($dataItem[2]));
                $newmobileStr = "";
                if(!empty($mobileStr)){
                    $res = (new ChuangLanService())->getCheckPhoneStatus([
                        'mobiles' => $mobileStr,
                    ]);
                    if (!empty($res['data'])){
                        foreach($res['data'] as $resdataItem){
                            if($dataItem['status'] == 1){
                                $newmobileStr .= $resdataItem["mobile"].';';
                            }
                        }
                    }
                }

                $fileObject ->data(
                    [
                        [
                            $dataItem[0],
                            $dataItem[1],
                            $dataItem[2],
                            $newmobileStr
                        ]
                    ]
                );
            }

            $format = new Format($fileHandle);
            //单元格有\n解析成换行
            $wrapStyle = $format
                ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
                ->wrap()
                ->toResource();

            $fileObject->output();
            //===============================
            //==============================生成文件end=====================================
            AdminUserBussinessOpportunityUploadRecord::updateById(
                $rawDataItem['id'],
                [
                    'status' => AdminUserBussinessOpportunityUploadRecord::$status_check_mobile_success,
                    'new_name' => $newName,

                ]
            );
        }
        return true;
    }
    /**
    第一步：先去空号
     */
    static function  delEmptyMobile(){
        $rawDatas = AdminUserBussinessOpportunityUploadRecord::findBySql(
            " WHERE status =  ".AdminUserBussinessOpportunityUploadRecord::$status_init
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                [
                    'delEmptyMobile'=>[
                        'msg' => 'start',
                    ]
                ]
            ])
        );
        foreach ($rawDatas as $rawDataItem){
            // 找到上传的文件路径
            self::setworkPath( $rawDataItem['file_path'] );
            $companyDatas = self::getYieldData($rawDataItem['name']);
            foreach ($companyDatas as $companyDataItem){
                $str = $companyDataItem[0];

                $dataArr = explode('|',$str);
                $name1 = $dataArr[0];
                if(empty($name1)){
                    continue;
                }
                $code1 = $dataArr[1];
                $mobileString = $dataArr[2];
//                CommonService::getInstance()->log4PHP(
//                    json_encode([
//                        __CLASS__.__FUNCTION__ .__LINE__,
//                        [
//                            'delEmptyMobile'=>[
//                                'read_data' => [
//                                    '$name1'=>$name1,
//                                    '$code1'=>$code1,
//                                    '$mobileString'=>$mobileString,
//                                ],
//                            ]
//                        ]
//                    ])
//                );
                //如果是需要去空号
                if($rawDataItem['del_empty']){
                    $mobileStr = str_replace(";", ",", trim($mobileString));
//                    CommonService::getInstance()->log4PHP(
//                        json_encode([
//                            __CLASS__.__FUNCTION__ .__LINE__,
//                            [
//                                'delEmptyMobile'=>[
//                                    'del_empty' => [
//                                        '$mobileStr'=>$mobileStr,
//                                    ],
//                                ]
//                            ]
//                        ])
//                    );
                    $newmobileStr = "";
                    if(!empty($mobileStr)){
                        $res = (new ChuangLanService())->getCheckPhoneStatus([
                            'mobiles' => $mobileStr,
                        ]);
//                        CommonService::getInstance()->log4PHP(
//                            json_encode([
//                                __CLASS__.__FUNCTION__ .__LINE__,
//                                [
//                                    'delEmptyMobile'=>[
//                                        'del_empty' => [
//                                            'mobile_check_res'=>$res,
//                                        ],
//                                    ]
//                                ]
//                            ])
//                        );
                        if (!empty($res['data'])){
                            foreach($res['data'] as $dataItem){
                                if($dataItem['status'] == 1){
                                    $newmobileStr .= $dataItem["mobile"].';';
                                }
                                else{
                                    OperatorLog::addRecord(
                                        [
                                            'user_id' => 0,
                                            'msg' =>json_encode(
                                                [
                                                    'mobile'=>$dataItem["mobile"],
                                                    'res'=>$dataItem,
                                                ]
                                            ),
                                            'details' => json_encode(XinDongService::trace()),
                                            'type_cname' => '无效的手机_'.$dataItem["mobile"],
                                        ]
                                    );
                                }
                            }
                        }
                    }
                    $mobileString = $newmobileStr;
                }

                // 拆分出来
                $mobilesArr = explode(';',trim($mobileString));
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ .__LINE__,
                        [
                            'delEmptyMobile'=>[
                                'del_empty' => [
                                    '$mobileString'=>$mobileString,
                                    '$mobilesArr'=>$mobilesArr,
                                ],
                            ]
                        ]
                    ])
                );
                if($mobileString<=0){
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            __CLASS__.__FUNCTION__ .__LINE__,
                            [
                                'delEmptyMobile'=>[
                                    'empty' => [
                                        '$mobileString'=>$mobileString,
                                        '$mobilesArr'=>$mobilesArr,
                                    ],
                                ]
                            ]
                        ])
                    );
                    BussinessOpportunityDetails::addRecordV2(
                        [
                            'upload_record_id' => $rawDataItem['id'], //
                            'entName' => $name1, //
                            'entCode' => $code1, //
                            'mobile' => 0,
                            'remark' => '', //
                        ]
                    );
                    continue;
                }

                foreach ($mobilesArr as $mobile){
                    if(
                        empty($mobile) ||
                        $mobile < 0
                    ){
                        continue;
                    }
                    BussinessOpportunityDetails::addRecordV2(
                        [
                            'upload_record_id' => $rawDataItem['id'], //
                            'entName' => $name1, //
                            'entCode' => $code1, //
                            'mobile' => $mobile,
                            'remark' => '', //
                        ]
                    );
                }
            }
            //==============================生成文件end=====================================
            AdminUserBussinessOpportunityUploadRecord::updateById(
                $rawDataItem['id'],
                [
                    'status' => AdminUserBussinessOpportunityUploadRecord::$status_check_mobile_success,
                ]
            );
        }
        return true;
    }
    static function  addWeChatInfo(){
        $rawDatas = AdminUserWechatInfoUploadRecord::findBySql(
            " WHERE status =  ".AdminUserWechatInfoUploadRecord::$status_init
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                [
                    'addWeChatInfo'=>[
                        'msg' => 'start ' ,
                    ]
                ]
            ])
        );
        foreach ($rawDatas as $rawDataItem){
            // 找到上传的文件路径
            self::setworkPath( $rawDataItem['file_path'] );
            $companyDatas = self::getYieldData($rawDataItem['name']);
            foreach ($companyDatas as $companyDataItem){
                //企业 税号 电话 微信名
                $companyName = $companyDataItem[0];
                $companyCode = $companyDataItem[1];
                $phone = $companyDataItem[2];
                $wechat = $companyDataItem[3];

                if(empty($phone)){
                    continue;
                }
                if(empty($wechat)){
                    continue;
                }
                if(strlen($phone) !== 11){
                    continue;
                }

                $created_at = time();
                $phone_aes = \wanghanwanghan\someUtils\control::aesEncode($phone, $created_at . '');
                $phone_md5 = md5($phone);
                $insert = [
                    'code' => $companyCode,
                    'phone' => $phone_aes,
                    'phone_md5' => $phone_md5,
                    'nickname' => $wechat,
                    'created_at' => $created_at,
                    'updated_at' => $created_at,
                ];
                CommonService    ::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ .__LINE__,
                        $insert
                    ])
                );
                WechatInfo::addRecordV2(
                    $insert
                );
            }
            //==============================生成文件end=====================================
            AdminUserWechatInfoUploadRecord::updateById(
                $rawDataItem['id'],
                [
                    'status' => AdminUserWechatInfoUploadRecord::$status_success,
                ]
            );
        }
        return true;
    }

    // id 商机id
    static function getYieldCompanyData($id){
        $datas = [];

        $bussinessOpportunity = AdminUserBussinessOpportunityUploadRecord::findById($id);
        $bussinessOpportunity = $bussinessOpportunity->toArray();

        $allRecords = BussinessOpportunityDetails::findByUploadId($id);
        $newReords = [];
        foreach ($allRecords as $Record){
            $mobile = trim($Record['mobile']);
//            if(empty($mobile)){
//                continue;
//            }
            $newReords[$Record['entName']][$mobile]  = $mobile;
        }

        $title = [
            '企业',
            '税号',
            '手机号',
        ];
        $noNeedFeilds = [
            'ENTNAME',
            'companyid',
        ];
        $allFields = AdminUserSoukeConfig::getAllFieldsV2();
        foreach ($allFields as $field=>$cname){
            if(
                in_array($field,$noNeedFeilds)
            ){
                continue;
            }

            $title[] = $cname ;
        }

        if($bussinessOpportunity['get_all_field']){
            yield $datas[] =  $title;
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    [
                        'generateNewFile'=>[
                            'add field' => '',
                        ]
                    ]
                ])
            );
        }
        else{
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    [
                        'generateNewFile'=>[
                            'add no field' => '',
                        ]
                    ]
                ])
            );
        }
        foreach ($newReords as $entName => $mobilesArr){
            //
            $details =  BussinessOpportunityDetails::findOneByName($entName,$id);
            $details =  $details->toArray();
            $code = trim($details['entCode']);
            $newMobielsArr = [];
            foreach ($mobilesArr as $mobileItem){
                if($mobileItem>0){
                    $newMobielsArr[] = $mobileItem;
                }
            }
            $baseArr = [
                $entName, //企业,
                $code,//税号,
                join(',',$newMobielsArr)//手机号,
            ];

            if(!$bussinessOpportunity['get_all_field']){
                yield $datas[] = $baseArr;
                continue;
            }

            //需要补全字段
            if($code){
                $res = (new XinDongService())->getEsBasicInfoV3($code,'UNISCID');
            }
            else{
                $res = (new XinDongService())->getEsBasicInfoV3($entName,'ENTNAME');
            }
//            CommonService::getInstance()->log4PHP(
//                json_encode([
//                    __CLASS__.__FUNCTION__ .__LINE__,
//                    [
//                        'getYieldCompanyData'=>[
//                            '$res'=>$res,
//                            '$mobilesArr'=>$mobilesArr,
//                            '$code'=>$code,
//                            '$entName'=>$entName,
//                        ]
//                    ]
//                ])
//            );

            foreach ($allFields as $field=>$cname){
                if(
                    in_array($field,$noNeedFeilds)
                ){
                    continue;
                }

                if($field=='ENTTYPE'){
                    $cname =   CodeCa16::findByCode($res['ENTTYPE']);
                    $res['ENTTYPE'] =  $cname?$cname->getAttr('name'):'';
                }
                if($field=='ENTSTATUS'){
                    $cname =   CodeEx02::findByCode($res['ENTSTATUS']);
                    $res['ENTSTATUS'] =  $cname?$cname->getAttr('name'):'';
                }

                //地区
                if(
                    $field=='DOMDISTRICT' &&
                    $res['DOMDISTRICT'] >0
                ){
                    $regionRes = CompanyBasic::findRegion($res['DOMDISTRICT']);
                    $res['DOMDISTRICT'] =  $regionRes['name'];
                }

                //行业分类代码  findNICID
                if(
                    $field=='NIC_ID' &&
                    $res['NIC_ID'] >0
                ){
                    $nicRes = NicCode::findNICID($res['NIC_ID']);
                    $res['NIC_ID'] =  $nicRes['industry'];
                }

                //一般人
                if(
                    $field=='yi_ban_ren'
                ){
                    $res['yi_ban_ren'] =  $res['yi_ban_ren']?'有':'无';
                }

                //战略新兴产业
                if(
                    $field=='zlxxcy'
                ){
                    $res['zlxxcy'] =  $res['zlxxcy']?'有':'无';
                }

                //数字经济产业
                if(
                    $field=='szjjcy'
                ){
                    $res['szjjcy'] =  $res['szjjcy']?'有':'无';
                }


                if(
                    $field=='jin_chu_kou'
                ){
                    $res['jin_chu_kou'] =  $res['jin_chu_kou']?'有':'无';
                }


                if(
                    $field=='iso'
                ){
                    $res['iso'] =  $res['iso']?'有':'无';
                }


                if(
                    is_array($res[$field])
                ){
                    $baseArr[] = empty($res[$field])?'无':'有' ;
                }else{
                    $baseArr[] = $res[$field] ;
                }
            }
            yield $datas[] = $baseArr;
        }
    }
    //
    static function getYieldPublicContactData($id){
        $datas = [];
        $bussinessOpportunity = AdminUserBussinessOpportunityUploadRecord::findById($id);
        $bussinessOpportunity = $bussinessOpportunity->toArray();

        //如果不需要拉取公开的联系人
        if(!$bussinessOpportunity['pull_api']){
            return $datas;
        }

        $allRecords = BussinessOpportunityDetails::findByUploadId($id);
        $newReords = [];
        foreach ($allRecords as $Record){
            $mobile = trim($Record['mobile']);
            $newReords[$Record['entName']][$mobile]  = $mobile;
        }
        foreach ($newReords as $entName => $mobilesArr){
            $details =  BussinessOpportunityDetails::findOneByName($entName,$id);
            $details =  $details->toArray();
            $code = trim($details['entCode']);

            $retData =  (new LongXinService())
                ->setCheckRespFlag(true)
                ->getEntLianXi([
                    'entName' => $entName,
                ])['result'];
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    [
                        'getYieldPublicContactData'=>[
                            '$retData'=>$retData,
                            '$entName'=>$entName
                        ]
                    ]
                ])
            );
            $retData = LongXinService::complementEntLianXiMobileState($retData);
            $retData = LongXinService::complementEntLianXiPosition($retData, $entName);

            yield $datas[] = [
                '企业名',
                '微信名',
                '联系人职位[url]',
                '联系方式来源[url]',
                '联系方式唯一标识[url]',
                'ltype[url]',
                '联系人姓名[url]',
                '联系方式权重[url]',
                '手机归属地/座机区号[url]',
                '联系方式来源网页链接[url]',
                '联系方式[url]',
                '联系方式类型（手机/座机/邮箱）[url]',
                'mobile_check_res[url]',
                '手机号码状态[url]',
                'url联系人姓名匹配到的职位[url]',
                '联系人名称（疑似）[微信匹配]',
                '职位（疑似）[微信匹配]',
                '匹配类型[微信匹配]',
                '匹配子类型[微信匹配]',
                '匹配值[微信匹配]',
            ];
            foreach($retData as $datautem){
                /**
                [
                {
                "duty": "公司最高代表",
                "source": "黄页",
                "lid": 199752834,
                "ltype": "1",
                "name": "严庆",
                "idx": "A",
                "quhao": "广东省深圳市",
                "url": "http://www.czvv.com/huangye/1516626.html",
                "lianxi": "13823539096",
                "lianxitype": "手机",
                "mobile_check_res": "1",
                "mobile_check_res_cname": "正常",
                "staff_position": "--"
                }]
                 */
                 if($datautem['lianxitype'] != '手机'){
                     yield $datas[] = array_values(
                         array_merge(
                             [
                                 'comname' =>$entName, //企业名
                                 'weixin_name'=>'', //微信名
                             ],
                             $datautem
                         )
                     );
                     continue;
                 }

                //匹配微信名字
                $matchedWeiXinName = WechatInfo::findByPhoneV2($datautem['lianxi']);
                if(empty($matchedWeiXinName)){
                    yield $datas[] = array_values(
                        array_merge(
                            [
                                'comname' =>$entName,
                                'weixin_name'=>$matchedWeiXinName['nickname'],
                            ],
                            $datautem
                        )
                    );
                    continue;
                }
                 //用微信匹配
                $tmpRes = (new XinDongService())->matchContactNameByWeiXinNameV2($entName,$matchedWeiXinName['nickname']);
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ .__LINE__,
                        [
                            'getYieldPublicContactData'=>[
                                'match_by_weixin_res1'=> $tmpRes,
                                'nickname2'=> $matchedWeiXinName['nickname'],
                                '$entName'=> $entName,
                            ]
                        ]
                    ])
                );
                yield $datas[] = array_values(
                    array_merge(
                        [
                            'comname' =>$entName,
                            'weixin_name'=>$matchedWeiXinName['nickname'],
                        ],
                        $datautem,

                        [
                            'matched_stff_name' => $tmpRes['data']['stff_name'], // '联系人名称（疑似）[微信匹配]',
                            'matched_staff_type_name' => $tmpRes['data']['staff_type_name'],// '职位（疑似）[微信匹配]',
                            'match_type' => $tmpRes['match_res']['type'],
                            'match_typedetails' => $tmpRes['match_res']['details'],
                            'match_percentage' => $tmpRes['match_res']['percentage'],
                        ]
                    )
                );

            }
        }
    }

    // NonPublicise
    static function getYieldNonPubliciseContactData($id){
        $datas = [];

        $bussinessOpportunity = AdminUserBussinessOpportunityUploadRecord::findById($id);
        $bussinessOpportunity = $bussinessOpportunity->toArray();

        //如果不需要拉取公开的联系人
//        if(!$bussinessOpportunity['pull_api']){
//            return $datas;
//        }

        $allRecords = BussinessOpportunityDetails::findByUploadId($id);
        $newReords = [];
        foreach ($allRecords as $Record){
            $mobile = trim($Record['mobile']);
            $newReords[$Record['entName']][$mobile]  = $mobile;
        }

        yield $datas[] = [
            '企业名',
            '手机号',
            '微信',
            '联系人名称（疑似）[微信匹配]',
            '职位（疑似）[微信匹配]',
            '匹配类型[微信匹配]',
            '匹配子类型[微信匹配]',
            '匹配值[微信匹配]',
        ];

        foreach ($allRecords as $recordItem){
            $details =  BussinessOpportunityDetails::findOneByName($recordItem['entName'],$id);
            $details =  $details->toArray();
            $code = trim($details['entCode']);
            if($recordItem['mobile']<=0 ){
                yield $datas[] =  [
                    'entName' =>$recordItem['entName'],
                    'mobile'=>$recordItem['mobile'],
                    'weixin'=>'',
                ];
                continue;
            }
            //匹配微信名字
            $matchedWeiXinName = WechatInfo::findByPhoneV2(($recordItem['mobile']));
            if(empty($matchedWeiXinName)){
                yield $datas[] =  [
                    'entName' =>$recordItem['entName'],
                    'mobile'=>$recordItem['mobile'],
                    'weixin'=>$matchedWeiXinName['nickname'],
                ];
                continue;
            }
            //用微信匹配
            $tmpRes = (new XinDongService())->matchContactNameByWeiXinNameV2($recordItem['entName'],$matchedWeiXinName['nickname']);
            yield $datas[] = array_values(
                array_merge(
                    [
                        'entName' =>$recordItem['entName'],
                        'mobile'=>$recordItem['mobile'],
                        'weixin'=>$matchedWeiXinName['nickname'],
                    ],
                    [
                        'matched_stff_name' => $tmpRes['data']['stff_name'],
                        'matched_staff_type_name' => $tmpRes['data']['staff_type_name'],
                        'match_type' => $tmpRes['match_res']['type'],
                        'match_typedetails' => $tmpRes['match_res']['details'],
                        'match_percentage' => $tmpRes['match_res']['percentage'],
                    ]
                )
            );
        }
    }

    /**
        生成新的文件
        传文件 然后 导出
        微信补充后
        重新导出

        库里是非公开的联系人  很多么有姓名 所以一般需要微信名匹配一下
        url公开的 要看情况 如果很全了  就直接出  如果不全  就再用微信匹配一遍

       默认出库里的微信名，
       默认出微信名+手机号

       Chcek:
        1:导出的时候：初步设定为导出三个sheet：sheet1：企业基本信息（全字段的话，所有字段全在这个sheet）
        2：建议勾选项：是否全字段，是否取公开联系人信息，是否匹配非公开联系人
        3：企业全字段问题：建议多选解决：勾选的，sheet1出现全字段，如果没勾选，只出现企业名/税号/手机号（有时候并没有|取公开信息的时候就没有）
        4：如果只勾选了公开联系人：sheet3里出现公开联系人，通过公开联系人姓名，匹配库里数据，补全职位等信息，没有联系人姓名的通过库里微信名匹配详细信息，
        5：如果只勾选了非公开联系人：sheet2里出现非公开联系人，通过库里微信名匹配详细信息，如果一旦发现需要再去匹配微信，自己去匹配，然后告知系统，重新下载
     */
    static function  generateNewFile(){
        $rawDatas = AdminUserBussinessOpportunityUploadRecord::findBySql(
            " WHERE status =  ".AdminUserBussinessOpportunityUploadRecord::$status_check_mobile_success
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                [
                    'generateNewFile'=>[
                        'msg' => 'start',
                    ]
                ]
            ])
        );
        foreach ($rawDatas as $rawDataItem){
            $startMemory = memory_get_usage();
            //第一部分 sheet1 企业部分数据
            $sheet1Datas = self::getYieldCompanyData($rawDataItem['id']);
            //第二部分 sheet2
            $sheet2Datas = self::getYieldPublicContactData($rawDataItem['id']);
            //第三部分 sheet3
            $sheet3Datas = self::getYieldNonPubliciseContactData($rawDataItem['id']);

            //continue;

            // 找到上传的文件路径
            self::setworkPath( $rawDataItem['file_path'] );

            $filename = date('YmdHis').'_'.$rawDataItem['name'];
            //==============================生成文件start===================================
            $config=  [
                'path' => TEMP_FILE_PATH // xlsx文件保存路径
            ];

            $excel = new \Vtiful\Kernel\Excel($config);
            $fileObject = $excel->fileName($filename, '企业基本信息');
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
//                ->header(
//                    [
//                        '标题' , //
//                        '项目名称' , //
//                    ]
//                )
                 ->defaultFormat($alignStyle) ;

            foreach ($sheet1Datas as $dataItem){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ .__LINE__,
                        '$dataItem1' => $dataItem
                    ])
                );
                $fileObject ->data([$dataItem]);
            }
            //==============================================
            //p2
            $file->addSheet('公开数据')
                ->defaultFormat($colorStyle)
//                ->header([
//                    '标题' , //
//                    '项目名称' , //
//                ])
                ->defaultFormat($alignStyle)   ;

            foreach ($sheet2Datas as $dataItem){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ .__LINE__,
                        '$dataItem2' => $dataItem
                    ])
                );
                $file->data([$dataItem]);
                $p2Nums ++;
            }
            //==============================================
            //p3
            $file->addSheet('非公开')
                ->defaultFormat($colorStyle)
//                ->header([
//                    '标题' , //
//                    '项目名称' , //
//                ])
                ->defaultFormat($alignStyle)   ;

            foreach ($sheet3Datas as $dataItem){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ .__LINE__,
                        '$dataItem2' => $dataItem
                    ])
                );
                $file->data([$dataItem]);
                $p2Nums ++;
            }
            //==============================================
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'generate data done . memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M'
                ])
            );

            $format = new Format($fileHandle);
            //单元格有\n解析成换行
            $wrapStyle = $format
                ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
                ->wrap()
                ->toResource();

            $fileObject->output();

            //==============================生成文件end=====================================
            AdminUserBussinessOpportunityUploadRecord::updateById(
                $rawDataItem['id'],
                [
                    'status' => AdminUserBussinessOpportunityUploadRecord::$status_split_success,
                    'new_name' => $filename,
                ]
            );

            return true;
        }
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString());
    }

}
