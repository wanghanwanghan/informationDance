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
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\DeliverDetailsHistory;
use App\HttpController\Models\AdminV2\DeliverHistory;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\AdminV2\FinanceLog;
use App\HttpController\Models\AdminV2\NewFinanceData;
use App\HttpController\Models\AdminV2\ToolsUploadQueue;
use App\HttpController\Models\BusinessBase\CompanyClueMd5;
use App\HttpController\Models\BusinessBase\ZhifubaoInfo;
use App\HttpController\Models\RDS3\HdSaic\CodeCa16;
use App\HttpController\Models\RDS3\HdSaic\CodeEx02;
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



class RunDealToolsFile extends AbstractCronTask
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


        //设置为正在执行中
        ConfigInfo::setIsRunning(__CLASS__);

        //生成文件
        self::generateFile(3);

        return true ;   
    }

    // 取url补全
    static function  getYieldDataForUrl($xlsx_name){
        $excel_read = new \Vtiful\Kernel\Excel(['path' => self::$workPath]);
        $excel_read->openFile($xlsx_name)->openSheet();

        $datas = [];
        $nums = 1;
        while (true) {
            if($nums%100==0){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        '取url公开数据'=>[
                            '文件名'=>$xlsx_name,
                            '已执行次数'=>$nums
                        ]
                    ],JSON_UNESCAPED_UNICODE)
                );
            }
            $one = $excel_read->nextRow([
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
            ]);

            if (empty($one)) {
                break;
            }
            //第一行是标题  不是数据
            if($nums==1){
                $nums ++;
                yield $datas[] = [
                    '企业',
                    '联系人职位',
                    '联系方式来源',
                    '联系方式唯一标识',
                    'ltype',
                    '联系人姓名',
                    '联系方式权重',
                    '手机归属地/座机区号',
                    '联系方式来源网页链接',
                    '联系方式',
                    '联系方式类型（手机/座机/邮箱）',
                    'mobile_check_res',
                    '手机号码状态',
                    '职位'

                ];
                continue;
            }
            $entname = self::strtr_func($one[0]);

            $retData =  (new LongXinService())
                ->setCheckRespFlag(true)
                ->getEntLianXi([
                    'entName' => $entname,
                ])['result'];
            $retData = LongXinService::complementEntLianXiMobileState($retData);
            $retData = LongXinService::complementEntLianXiPosition($retData, $entname);
            foreach($retData as $datautem){
                yield $datas[] = array_values(array_merge(['comname' =>$entname],$datautem));
            }
            $nums ++;
        }
    }

    // 根据微信名匹配职位数据
    static function  getYieldDataForWeinXin($xlsx_name){
        $excel_read = new \Vtiful\Kernel\Excel(['path' => self::$workPath]);
        $excel_read->openFile($xlsx_name)->openSheet();

        $datas = [];
        $nums = 1;
        while (true) {
            if($nums%100==0){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        '根据微信名匹配职位数据'=>[
                            '文件名'=>$xlsx_name,
                            '已执行'=>$nums,
                        ],
                    ],JSON_UNESCAPED_UNICODE)
                );
            }
            $one = $excel_read->nextRow([
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
            ]);

            if (empty($one)) {
                break;
            }

            //第一行是标题  不是数据
            if($nums==1){
                $nums ++;
                yield $datas[] = [
                    '企业名称',
                    '手机号',
                    '微信名',
                    '联系人名称（疑似）',
                    '职位（疑似）',
                    '真实联系人',
                    '实际职位',
                    '匹配类型',
                    '匹配子类型',
                    '匹配值',
                ];
                continue;
            }
            $nums ++ ;
            //企业名称
            $value0 = self::strtr_func($one[0]);
            //手机号
            $value1 = self::strtr_func($one[1]);
            //微信名
            $value2 = self::strtr_func($one[2]);
            //联系人名称（疑似）
            $value3 = self::strtr_func($one[3]);
            //职位（疑似）
            $value4 = self::strtr_func($one[4]);
            $tmpRes = (new XinDongService())->matchContactNameByWeiXinNameV2($value0,$value2);

            yield $datas[] = [
                $value0,
                $value1,
                $value2,
                $value3,
                $value4,
                $tmpRes['data']['stff_name'],
                $tmpRes['data']['staff_type_name'],
                $tmpRes['match_res']['type'],
                $tmpRes['match_res']['details'],
                $tmpRes['match_res']['percentage'],
            ];
        }
    }

    // 根据企业微信名匹配职位数据
    static function  getYieldDataForQiYeWeinXin($xlsx_name){
        $excel_read = new \Vtiful\Kernel\Excel(['path' => self::$workPath]);
        $excel_read->openFile($xlsx_name)->openSheet();

        $datas = [];
        $nums = 1;
        while (true) {
            if($nums%100==0){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        '根据企业微信名匹配职位数据'=>[
                            '文件名'=>$xlsx_name,
                            '已执行'=>$nums,
                        ],
                    ],JSON_UNESCAPED_UNICODE)
                );
            }
            $one = $excel_read->nextRow([
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
            ]);

            if (empty($one)) {
                break;
            }

            //第一行是标题  不是数据
            if($nums==1){
                $nums ++;
                yield $datas[] = [
                    '企业名称',
                    '手机号',
                    '匹配到的联系人',
                    '匹配到的职位',
                    '匹配类型',
                    '匹配详情',
                    '匹配分值',
                    '微信名',
                ];
                continue;
            }
            $nums ++ ;
            //企业名称
            $companyName = self::strtr_func($one[0]);
            //手机号
            $phones = self::strtr_func($one[1]);

            $weiXinNames = [];
            //微信名1
            $value = self::strtr_func($one[2]);
            $value && $weiXinNames[$value] = $value;

            //微信名2
            $value = self::strtr_func($one[3]);
            $value && $weiXinNames[$value] = $value;

            //微信名3
            $value = self::strtr_func($one[4]);
            $value && $weiXinNames[$value] = $value;

            //微信名4
            $value = self::strtr_func($one[4]);
            $value && $weiXinNames[$value] = $value;

            //微信名5
            $value = self::strtr_func($one[4]);
            $value && $weiXinNames[$value] = $value;

            //微信名6
            $value = self::strtr_func($one[4]);
            $value && $weiXinNames[$value] = $value;

            //微信名7
            $value = self::strtr_func($one[4]);
            $value && $weiXinNames[$value] = $value;

            $showLog = false;
            if($nums%100==0){
                $showLog = true ;
            }
            $tmpRes = (new XinDongService())->matchContactNameByQiYeWeiXinName($companyName, $phones, $weiXinNames,$showLog);

            yield $datas[] = [
                $companyName,
                $phones,
                $tmpRes['data']['NAME'],
                $tmpRes['data']['POSITION'],
                $tmpRes['match_res']['type'],
                $tmpRes['match_res']['details'],
                $tmpRes['match_res']['percentage'],
                json_encode($weiXinNames,JSON_UNESCAPED_UNICODE),
            ];
        }
    }

    // 根据支付宝匹配职位数据
    static function  getYieldDataForZhiFuBao($xlsx_name){
        $excel_read = new \Vtiful\Kernel\Excel(['path' => self::$workPath]);
        $excel_read->openFile($xlsx_name)->openSheet();

        $datas = [];
        $nums = 1;
        while (true) {
            if($nums%100==0){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        '根据支付宝匹配职位数据'=>[
                            '文件名'=>$xlsx_name,
                            '已执行'=>$nums,
                        ],
                    ],JSON_UNESCAPED_UNICODE)
                );
            }
            $one = $excel_read->nextRow([
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
            ]);

            if (empty($one)) {
                break;
            }

            //第一行是标题  不是数据
            if($nums==1){
                $nums ++;
                yield $datas[] = [
                    '企业名称',
                    '手机号',
                    '支付宝名',
                    //'联系人名称（疑似）',
                    //'职位（疑似）',
                    '联系人',
                    '职位',
                    '匹配类型',
                    '匹配子类型',
                    '匹配值',
                ];
                continue;
            }
            $nums ++ ;
            //企业名称
            $value0 = self::strtr_func($one[0]);
            //手机号
            $value1 = self::strtr_func($one[1]);
            //支付宝
            $value2 = self::strtr_func($one[2]);
            //
            $value3 = self::strtr_func($one[3]);
            //
            $value4 = self::strtr_func($one[4]);

            if(trim($value2)){
                $tmpRes = (new XinDongService())->matchContactNameByZhiFuBaoName($value0,trim($value2));
            } else{
                $tmpRes = [];
            }

            yield $datas[] = [
                $value0,
                $value1,
                $value2,
                //$value3,
                //$value4,
                $tmpRes['data']['NAME'],
                $tmpRes['data']['POSITION'],
                $tmpRes['match_res']['type'],
                $tmpRes['match_res']['details'],
                $tmpRes['match_res']['percentage'],
            ];
        }
    }

    //桃树
    static function  getYieldDataForZhiFuBao2($xlsx_name){
        $excel_read = new \Vtiful\Kernel\Excel(['path' => self::$workPath]);
        $excel_read->openFile($xlsx_name)->openSheet();

        $datas = [];
        $nums = 1;
        while (true) {
            if($nums%100==0){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        '根据支付宝匹配桃树职位数据'=>[
                            '文件名'=>$xlsx_name,
                            '已执行'=>$nums,
                        ],
                    ],JSON_UNESCAPED_UNICODE)
                );
            }
            $one = $excel_read->nextRow([
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
            ]);

            if (empty($one)) {
                break;
            }

            //第一行是标题  不是数据
            if($nums==1){
                $nums ++;
                yield $datas[] = [
                    '企业名称',
                    '手机号',
                    '支付宝名',
                    //'联系人名称（疑似）',
                    //'职位（疑似）',
                    '联系人',
                    '职位',
                    '是否法人',
                    '匹配类型',
                    '匹配子类型',
                    '匹配值',
                ];
                continue;
            }
            $nums ++ ;
            //企业名称
            $value0 = self::strtr_func($one[0]);
            //手机号
            $value1 = self::strtr_func($one[1]);
            //支付宝
            $value2 = self::strtr_func($one[2]);
            //
            $value3 = self::strtr_func($one[3]);
            //
            $value4 = self::strtr_func($one[4]);
            $tmpRes = (new XinDongService())->matchContactNameByZhiFuBaoNameV2($value0,$value2);

            yield $datas[] = [
                $value0,
                $value1,
                $value2,
                //$value3,
                //$value4,
                $tmpRes['data']['NAME'],
                $tmpRes['data']['POSITION'],
                $tmpRes['data']['ISFRDB'],
                $tmpRes['match_res']['type'],
                $tmpRes['match_res']['details'],
                $tmpRes['match_res']['percentage'],
            ];
        }
    }

    static function getYieldHeaderDataForFuzzyMatch(){
        return [
            '企业模糊名称',
            '匹配结果',
        ];
    }
    static function  getYieldDataForFuzzyMatch($xlsx_name){
        $excel_read = new \Vtiful\Kernel\Excel(['path' => self::$workPath]);
        $excel_read->openFile($xlsx_name)->openSheet();

        $datas = [];
        $nums = 1;
        while (true) {
            if($nums%100==0){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        '模糊匹配企业'=>[
                            '文件名'=>$xlsx_name,
                            '数量'=>$nums,
                        ],
                    ], JSON_UNESCAPED_UNICODE)
                );
            }
            $one = $excel_read->nextRow([
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
            ]);

            if (empty($one)) {
                break;
            }

            $nums ++;
            //企业名称
            $value0 = self::strtr_func($one[0]);
            $value1 = self::strtr_func($one[1]);
            $value2 = self::strtr_func($one[2]);
            $value3 = self::strtr_func($one[3]);
            $tmpRes = XinDongService::fuzzyMatchEntName($value0,3);
            $name1 = $tmpRes[0]['_source']['ENTNAME'];
            $name2 = $tmpRes[1]['_source']['ENTNAME'];
            $name3 = $tmpRes[2]['_source']['ENTNAME'];
            yield $datas[] = [
                $value0,
                //$value1,
                //$value2,
                $name1,
                $name2,
                $name3,
            ];
        }
    }
    static function  getYieldDataForSplite($xlsx_name){
        $excel_read = new \Vtiful\Kernel\Excel(['path' => self::$workPath]);
        $excel_read->openFile($xlsx_name)->openSheet();

        $datas = [];
        $nums = 1;
        while (true) {
            $nums ++;
            if($nums%100==0){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        '拆分表格'=>[
                            '文件名'=>$xlsx_name,
                            '已执行'=>$nums,
                        ]
                    ],JSON_UNESCAPED_UNICODE)
                );
            }
            $one = $excel_read->nextRow([
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
            ]);

            if (empty($one)) {
                break;
            }

            //第一行是标题  不是数据
//            if($nums==1){
//
//                yield $datas[] = [
//                    '企业模糊名称',
//                    '匹配结果1',
//                    '匹配结果2',
//                    '匹配结果3',
//                ];
//                continue;
//            }

            //字段1
            $value0 = self::strtr_func($one[0]);
            $value1 = self::strtr_func($one[1]);
            //手机号
            $value2 = self::strtr_func($one[2]);
            $value3 = self::strtr_func($one[3]);
            $value4 = self::strtr_func($one[4]);
            $value5 = self::strtr_func($one[5]);
            $value6 = self::strtr_func($one[6]);
            $value7 = self::strtr_func($one[7]);
            $value8 = self::strtr_func($one[8]);
            $value9 = self::strtr_func($one[9]);
            $phonesArr = explode(';',$value2);
            foreach ($phonesArr as $phone){
                if(empty($phone)){
                    continue;
                }
                yield $datas[] = [
                    $value0,
                    $value1,
                    $phone,
                    $value3,
                    $value4,
                    $value5,
                    $value6,
                    $value7,
                    $value8,
                    $value9,
                ];
            }

        }
    }

    //getYieldDataForGetWeiXinFromDB
    static function  getYieldDataForGetWeiXinFromDB($xlsx_name){
        $excel_read = new \Vtiful\Kernel\Excel(['path' => self::$workPath]);
        $excel_read->openFile($xlsx_name)->openSheet();

        $datas = [];
        $nums = 1;

        while (true) {
            if($nums%300==0){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        '根据手机号查询支付宝' => $xlsx_name,
                        '已生成' => $nums,
                    ],JSON_UNESCAPED_UNICODE)
                );
            }
            $one = $excel_read->nextRow([
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
            ]);

            if (empty($one)) {
                break;
            }

            //手机号
            $value0 = self::strtr_func($one[0]);
            $searchRes = ZhifubaoInfo::findByPhoneV2(
                $value0
            );
            //nickname
            yield $datas[] = [
                //手机号
                $value0,
                //支付宝
                $searchRes ?$searchRes['nickname'] :'',
            ];
            $nums ++;
        }
    }
    static function  getYieldDataForGetZhiFuBaoFromDB($xlsx_name){
        $excel_read = new \Vtiful\Kernel\Excel(['path' => self::$workPath]);
        $excel_read->openFile($xlsx_name)->openSheet();

        $datas = [];
        $nums = 1;

        while (true) {
            if($nums%300==0){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        '根据手机号查询支付宝' => $xlsx_name,
                        '已生成' => $nums,
                    ],JSON_UNESCAPED_UNICODE)
                );
            }
            $one = $excel_read->nextRow([
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
            ]);

            if (empty($one)) {
                break;
            }

            //手机号
            $value0 = self::strtr_func($one[0]);
            $searchRes = ZhifubaoInfo::findByPhoneV2(
                $value0
            );

            //nickname
            yield $datas[] = [
                //手机号
                $value0,
                //支付宝
                $searchRes ?$searchRes['nickname'] :'',
            ];
            $nums ++;
        }
    }

    static function  getYieldDataForTiChuDaiLiJiZhangAndKonghao($xlsx_name){
        $excel_read = new \Vtiful\Kernel\Excel(['path' => self::$workPath]);
        $excel_read->openFile($xlsx_name)->openSheet();

        $datas = [];
        $nums = 1;

        while (true) {
            if($nums%300==0){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        '剔除代理记账'=>$xlsx_name,
                        '已生成'=>$nums,
                    ],JSON_UNESCAPED_UNICODE)
                );
            }
            $one = $excel_read->nextRow([
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
            ]);

            if (empty($one)) {
                break;
            }

            //企业名
            $value0 = self::strtr_func($one[0]);
            //分号分隔的手机号
            $value1 = self::strtr_func($one[1]);
            $value1 = str_replace("；",";",$value1);
            $phoneArr = explode(';',$value1);
            $newPhonesArr = [];
            foreach ($phoneArr as $phone){
                if(!empty($phone)){
                    $newPhonesArr[] = $phone;
                }
            }
            // 调用接口查询手机号状态
            $needsCheckMobilesStr =  join(',',$newPhonesArr);
            $postData = [
                'mobiles' => $needsCheckMobilesStr,
            ];
            $res = (new ChuangLanService())->getCheckPhoneStatus($postData);
            //没有数据的话
            if(empty($res['data'])){
                foreach ($newPhonesArr as $newPhone) {
                    //代理记账
                    $daiLiJiZhang = CompanyClueMd5::daiLiJiZhang($newPhone);
                    yield $datas[] = [
                        $value0,
                        $newPhone,
                        $daiLiJiZhang,
                        //手机号码检测结果
                        '',
                        '',
                    ];
                }
            }
            else{
                /**
                "mobile": "13269706193",
                "lastTime": "1666262535000",
                "area": "北京-北京",
                "numberType": "中国联通",
                "chargesStatus": 1,
                "status": 1
                 */
                foreach ($res['data'] as $subItem) {
                    //代理记账
                    $daiLiJiZhang = CompanyClueMd5::daiLiJiZhang($subItem['mobile']);
                    yield $datas[] = [
                        //企业
                        $value0,
                        //手机号
                        $subItem['mobile'],
                        //代理记账
                        $daiLiJiZhang,
                        //手机号码检测结果
                        ChuangLanService::getStatusCnameMap()[$subItem['status']],
                    ];
                }
            }

            $nums ++;
        }
    }



    static function  getYieldDataHeaderForTiChuDaiLiJiZhangAndKonghao($xlsx_name){
        return [
            '企业',
            '手机号',
            '代理记账',
            '手机号码检测结果',
        ];
    }
    static function  getYieldDataHeaderForGetZhiFuBaoByPhone($xlsx_name){
        return [
            '手机号',
            '支付宝',
        ];
    }

    static function  getYieldDataHeaderForGetWeiXinByPhone($xlsx_name){
        return [
            '手机号',
            '微信',
        ];
    }

    static function  getYieldDataForCompleteCompanyInfo($xlsx_name){
        $excel_read = new \Vtiful\Kernel\Excel(['path' => self::$workPath]);
        $excel_read->openFile($xlsx_name)->openSheet();

        $datas = [];
        $nums = 1;

        $allFields = AdminUserSoukeConfig::getAllFieldsV2();

        while (true) {
            if($nums%100==0){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        'getYieldDataForCompleteCompanyInfo $xlsx_name'=>$xlsx_name,
                        'getYieldDataForCompleteCompanyInfo $nums'=>$nums,
                    ])
                );
            }
            $one = $excel_read->nextRow([
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
            ]);

            if (empty($one)) {
                break;
            }

            //字段Name
            $value0 = self::strtr_func($one[0]);
            //Code
            $value1 = self::strtr_func($one[1]);
            //需要补全字段
            if($value1){
                $res = (new XinDongService())->getEsBasicInfoV3($value1,'UNISCID');
            }
            else{
                $res = (new XinDongService())->getEsBasicInfoV3($value0,'ENTNAME');
            }

            foreach ($allFields as $field=>$cname){

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
//                    $regionRes = CompanyBasic::findRegion($res['DOMDISTRICT']);
//                    $res['DOMDISTRICT'] =  $regionRes['name'];
                    $res['DOMDISTRICT'] =  $res['DOM'];
                }

                //行业分类代码  findNICID
                if(
                    $field=='NIC_ID' &&
                    !empty( $res['NIC_ID'])
                ){
//                    $nicRes = NicCode::findNICID($res['NIC_ID']);
//                    CommonService::getInstance()->log4PHP(json_encode([
//                        'NIC_ID'=>$res['NIC_ID'],
//                        '$nicRes'=>$nicRes,
//                    ]));nic_full_name
//                    $res['NIC_ID'] =  $nicRes['industry'];
                    $res['NIC_ID'] =  $res['nic_full_name'];
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


                //iso_tags
                if(
                    $field=='iso_tags'
                ){
                    $str = "";
                    foreach ($res['iso_tags'] as $subItem){
                        $str.= $subItem['cert_project'];
                    }
                    $res['iso_tags'] =  $str;
                }

                if(
                    $field=='jin_chu_kou'
                ){
                    $res['jin_chu_kou'] =  $res['jin_chu_kou']?'有':'无';
                }

                // 高新技术
                if(
                    $field=='gao_xin_ji_shu'
                ){
                    $res['gao_xin_ji_shu'] =  $res['gao_xin_ji_shu']?'有':'无';
                }

                if(
                    is_array($res[$field])
                ){
                    $baseArr[] = empty($res[$field])?'无':'有' ;
                }else{

                    $baseArr[] = str_split ( $res[$field], 32766 )[0] ;
                }
            }

            $nums ++;
            yield $datas[] = $baseArr;
        }
    }
    static function  getYieldHeaderForCompleteCompanyInfo($xlsx_name){
        $excel_read = new \Vtiful\Kernel\Excel(['path' => self::$workPath]);
        $excel_read->openFile($xlsx_name)->openSheet();

        $datas = [];
        $nums = 1;

        $allFields = AdminUserSoukeConfig::getAllFieldsV2();
        foreach ($allFields as $field=>$cname){
            $title[] = $cname ;
        }

        return $title;

    }

    //生成下载文件
    static function  generateFile($limit){
        $startMemory = memory_get_usage();
        $allInitDatas =  ToolsUploadQueue::findBySql(
            " WHERE status = ".ToolsUploadQueue::$state_init.
                    " AND touch_time  is NULL 
                     LIMIT 1 
                     "
        );

        foreach($allInitDatas as $InitData){
            ToolsUploadQueue::setTouchTime(
                $InitData['id'],date('Y-m-d H:i:s')
            );

            $tmpXlsxDatas = [];
            $pathinfo = pathinfo($InitData['upload_file_name']);
            $filename = $pathinfo['filename'].'_'.date('YmdHis').'.xlsx';
            $dirPath =  dirname($InitData['upload_file_path']).DIRECTORY_SEPARATOR;
            self::setworkPath( $dirPath );

            // 取数据  5：url补全  10：微信匹配 15：模糊匹配
            if(
                $InitData['type'] == 5
            ){
                $tmpXlsxDatas = self::getYieldDataForUrl($InitData['upload_file_name']);
                $tmpXlsxHeaders = [];
            }
            if(
                $InitData['type'] == 6
            ){
                $tmpXlsxDatas = self::getYieldDataForQiYeWeinXin($InitData['upload_file_name']);
                $tmpXlsxHeaders = [];
            }

            if(
                $InitData['type'] == 10
            ){
                $tmpXlsxDatas = self::getYieldDataForWeinXin($InitData['upload_file_name']);
                $tmpXlsxHeaders = [];
            }

            if(
                $InitData['type'] == 12
            ){
                $tmpXlsxDatas = self::getYieldDataForZhiFuBao($InitData['upload_file_name']);
                $tmpXlsxHeaders = [];
            }

            if(
                $InitData['type'] == 13
            ){
                $tmpXlsxDatas = self::getYieldDataForZhiFuBao2($InitData['upload_file_name']);
                $tmpXlsxHeaders = [];
            }


            if(
                $InitData['type'] == 15
            ){
                $tmpXlsxDatas = self::getYieldDataForFuzzyMatch($InitData['upload_file_name']);
                $tmpXlsxHeaders = self::getYieldHeaderDataForFuzzyMatch();
            }

            if(
                $InitData['type'] == 20
            ){
                $tmpXlsxDatas = self::getYieldDataForSplite($InitData['upload_file_name']);
                $tmpXlsxHeaders = [];
            }

            if(
                $InitData['type'] == 25
            ){
                $tmpXlsxDatas = self::getYieldDataForCompleteCompanyInfo($InitData['upload_file_name']);
                $tmpXlsxHeaders = self::getYieldHeaderForCompleteCompanyInfo($InitData['upload_file_name']);
            }

            if(
                $InitData['type'] == 30
            ){
                //getYieldDataForTiChuDaiLiJiZhangAndKonghao
                $tmpXlsxDatas = self::getYieldDataForTiChuDaiLiJiZhangAndKonghao($InitData['upload_file_name']);
                $tmpXlsxHeaders = self::getYieldDataHeaderForTiChuDaiLiJiZhangAndKonghao($InitData['upload_file_name']);
            }

            if(
                $InitData['type'] == 35
            ){
                $tmpXlsxDatas = self::getYieldDataForGetZhiFuBaoFromDB($InitData['upload_file_name']);
                $tmpXlsxHeaders = self::getYieldDataHeaderForGetZhiFuBaoByPhone($InitData['upload_file_name']);
            }

            if(
                $InitData['type'] == 40
            ){
                $tmpXlsxDatas = self::getYieldDataForGetWeiXinFromDB($InitData['upload_file_name']);
                $tmpXlsxHeaders = self::getYieldDataHeaderForGetWeiXinByPhone($InitData['upload_file_name']);
            }

            $config=  [
                'path' => TEMP_FILE_PATH // xlsx文件保存路径
            ];
            $excel = new \Vtiful\Kernel\Excel($config);
            $fileObject = $excel->fileName($filename, 'sheet');
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

            $header = [];
            $fileObject
                ->defaultFormat($colorStyle)
                ->defaultFormat($alignStyle)
            ;
            if(!empty($tmpXlsxHeaders)){
                $fileObject ->header($tmpXlsxHeaders)
                ;
            }
            foreach ($tmpXlsxDatas as $dataItem){
                $fileObject ->data([$dataItem]);
            }

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

            //更新文件地址
            ToolsUploadQueue::setDownloadFilePath($InitData['id'],$filename,'/Static/Temp/');

            //设置状态
            ToolsUploadQueue::setStatus(
                $InitData['id'],ToolsUploadQueue::$state_file_succeed
            );
            ToolsUploadQueue::setTouchTime(
                $InitData['id'],NULL
            );
        }

        return true;
    }


    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString());
    }

}
