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
use App\HttpController\Models\AdminV2\FinanceLog;
use App\HttpController\Models\AdminV2\NewFinanceData;
use App\HttpController\Models\AdminV2\OperatorLog;
use App\HttpController\Models\RDS3\HdSaic\ZhaoTouBiaoAll;
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



class RunDealZhaoTouBiao extends AbstractCronTask
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
       
        $this->workPath = $this->filePath ;

        return true;
    }  
    
    static function setworkPath($filePath): bool
    {

        self::$workPath = $filePath ;

        return true;
    }

    public static function getZhaoTouBiaoData(
        $dateStart,$dateEnd,$source
    ){

        $returnDatas  = [];
        //所有的数据
        $datas = ZhaoTouBiaoAll::findBySql(" WHERE updated_at >= '$dateStart' AND updated_at <= '$dateEnd' AND source = '$source'  ");

        //上传记录详情
        foreach ($datas as $dataItem){
            yield $returnDatas[] = [
                '标题' => $dataItem['标题']?:'', //
                '项目名称' => $dataItem['项目名称']?:'', //
                '项目编号' => $dataItem['项目编号']?:'', //
                '项目简介' => $dataItem['项目简介']?:'', //
                '采购方式' => $dataItem['采购方式']?:'', //
                '公告类型2' => $dataItem['公告类型2']?:'', //
                '公告日期' => $dataItem['公告日期']?:'', //
                '行政区域_省' => $dataItem['行政区域_省']?:'', //
                '行政区域_市' => $dataItem['行政区域_市']?:'', //
                '行政区域_县' => $dataItem['行政区域_县']?:'', //
                '采购单位名称' => $dataItem['采购单位名称']?:'', //
                '采购单位地址' => $dataItem['采购单位地址']?:'', //
                '采购单位联系人' => $dataItem['采购单位联系人']?:'', //
                '采购单位联系电话' => $dataItem['采购单位联系电话']?:'', //
                '名次' => $dataItem['名次']?:'', //
                '中标供应商' => $dataItem['中标供应商']?:'', //
                '中标金额' => $dataItem['中标金额']?:'', //
                '代理机构名称' => $dataItem['代理机构名称']?:'', //
                '代理机构地址' => $dataItem['代理机构地址']?:'', //
                '代理机构联系人' => $dataItem['代理机构联系人']?:'', //
                '代理机构联系电话' => $dataItem['代理机构联系电话']?:'', //
                '评标专家' => $dataItem['评标专家']?:'', //
                'DLSM_UUID' => $dataItem['DLSM_UUID']?:'', //
                'url' => $dataItem['url']?:'', //
                'corexml' => $dataItem['corexml']?:'', //
            ];

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

        //发生提醒短信
        self::exportFinanceDataV4();

        return true ;   
    }


    //TODO  改成按行的 防止内存溢出
    static function  exportFinanceDataV4(){
        $dateStart = date('Y-m-d 00:00:00');
        $dateEnd = date('Y-m-d 23:59:59');

        $dateStart = '2022-06-20 00:00:00';
        $dateEnd = '2022-06-20 23:59:59';
        //p1
        $financeDatas = self::getZhaoTouBiaoData(
            $dateStart,$dateEnd,'p1'
        );

        $filename = 'zhao_tou_biao_'.date('YmdHis').'.xlsx';

        //===============================
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

        $file = $fileObject
            ->defaultFormat($colorStyle)
            ->header(
                [
                    '标题' , //
                    '项目名称' , //
                    '项目编号' , //
                    '项目简介' , //
                    '采购方式' , //
                    '公告类型2' , //
                    '公告日期' , //
                    '行政区域_省' , //
                    '行政区域_市' , //
                    '行政区域_县' , //
                    '采购单位名称' , //
                    '采购单位地址' , //
                    '采购单位联系人' , //
                    '采购单位联系电话' , //
                    '名次' , //
                    '中标供应商' , //
                    '中标金额' , //
                    '代理机构名称' , //
                    '代理机构地址' , //
                    '代理机构联系人' , //
                    '代理机构联系电话' , //
                    '评标专家' , //
                    'DLSM_UUID' , //
                    'url' , //
                    'corexml' , //
                ]
            )
            ->defaultFormat($alignStyle)
        ;
        foreach ($financeDatas as $dataItem){
            $fileObject ->data([$dataItem]);
        }
        //==============================================
        //p2
        $financeDatas2 = self::getZhaoTouBiaoData(
            $dateStart,$dateEnd,'p2'
        );
        $file->addSheet('sheet_two')
            ->defaultFormat($colorStyle)
            ->header(['name', 'age'])
            ->defaultFormat($alignStyle)
           ;
        foreach ($financeDatas as $dataItem){
            $file->data([$dataItem]);
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
        //===============================



        return true;
    }



    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString());
    }

}