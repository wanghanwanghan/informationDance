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
        return '00 18 * * *';
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
        $where = " WHERE updated_at >= '$dateStart' AND updated_at <= '$dateEnd' AND source = '$source'  ";
        $datas = \App\HttpController\Models\RDS3\ZhaoTouBiao\ZhaoTouBiaoAll::findBySql(
            $where
        );
        CommonService::getInstance()->log4PHP(json_encode(
            [
                __CLASS__ . ' is already running  ',
                '$where' => $where,
                'data_count' => count($datas)
            ]
        ));
        //上传记录详情
        foreach ($datas as $dataItem){

            yield $returnDatas[] = [
                '标题' => $dataItem['标题'] ?:'' , //
                '项目名称' => $dataItem['项目名称'] ?:'' , //
                '项目编号' => $dataItem['项目编号'] ?:'' , //
                '项目简介'  => $dataItem['项目简介'] ?:'' , //
                '采购方式'   => $dataItem['采购方式'] ?:'' , //
                '公告类型2'  => $dataItem['公告类型2'] ?:'' , //
                '公告日期' => $dataItem['公告日期'] ?:'' , //
                '行政区域_省' => $dataItem['行政区域_省'] ?:'' , //
                '行政区域_市'  => $dataItem['行政区域_市'] ?:'' , //
                '行政区域_县' => $dataItem['行政区域_县'] ?:'' , //
                '采购单位名称' => $dataItem['采购单位名称'] ?:'' , //
                '采购单位地址' => $dataItem['采购单位地址'] ?:'' , //
                '采购单位联系人' => $dataItem['采购单位联系人'] ?:'' , //
                '采购单位联系电话' => $dataItem['采购单位联系电话'] ?:'' , //
                '名次'  => $dataItem['名次'] ?:'' , //
                '中标供应商'  => $dataItem['中标供应商'] ?:'' , //
                '中标金额'  => $dataItem['中标金额'] ?:'' , //
                '代理机构名称' => $dataItem['代理机构名称'] ?:'' , //
                '代理机构地址'  => $dataItem['代理机构地址'] ?:'' , //
                '代理机构联系人'  => $dataItem['代理机构联系人'] ?:'' , //
                '代理机构联系电话' => $dataItem['代理机构联系电话'] ?:'' , //
                '评标专家' => $dataItem['评标专家'] ?:'' , //
                'DLSM_UUID'  => $dataItem['DLSM_UUID'] ?:'' , //
                'url'  => $dataItem['url'] ?:'' , //
                'corexml' => $dataItem['corexml'] ?str_split ( $dataItem['corexml'], 32766 )[0]:'' , //
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
//        if(
//            !ConfigInfo::checkCrontabIfCanRun(__CLASS__)
//        ){
//            return     CommonService::getInstance()->log4PHP(json_encode(
//                [
//                    __CLASS__ . ' is already running  ',
//                ]
//            ));
//        }

        //设置为正在执行中
        ConfigInfo::setIsRunning(__CLASS__);

        $day = date('Y-m-d');
        //$day = '2022-06-20';
        //$day = '2022-07-14';

        //生成文件 发邮件
        $res = self::sendEmail($day);
        $res = self::sendEmailV2($day);
        return true ;
    }

    static function sendEmail($day)
    {
        //$day = date('Y-m-d');
        //$day = '2022-06-20';
        //$day = '2022-07-14';
        $dateStart = $day.' 00:00:00';
        $dateEnd = $day.' 23:59:59';

        $res = self::exportDataV4($dateStart,$dateEnd);

        if(
            $res['p2Nums'] == 0 &&
            $res['p1Nums'] == 0
        ){
            //continue;
            OperatorLog::addRecord(
                [
                    'user_id' => 0,
                    'msg' => '没有数据，不发送邮件 日期：'.$day." 查询结果:".json_encode($res) ,
                    'details' =>json_encode( XinDongService::trace()),
                    'type_cname' => '招投标邮件',
                ]
            );
            return  true;
        }

        //
        $res1 = CommonService::getInstance()->sendEmailV2(
             'tianyongshan@meirixindong.com',
           // 'minglongoc@me.com',
            '招投标数据('.$day.')',
            '',
            [TEMP_FILE_PATH . $res['filename']]
        );
        $res2 = CommonService::getInstance()->sendEmailV2(
            // 'tianyongshan@meirixindong.com',
            'minglongoc@me.com',
            '招投标数据('.$day.')',
            '',
            [TEMP_FILE_PATH . $res['filename']]
        );
        //
        $res3 = CommonService::getInstance()->sendEmailV2(
            'guoxinxia@meirixindong.com',
            // 'minglongoc@me.com',
            '招投标数据('.$day.')',
            '',
            [TEMP_FILE_PATH . $res['filename']]
        );

        //
//        $res4 = CommonService::getInstance()->sendEmailV2(
//            'zhengmeng@meirixindong.com',
//            // 'minglongoc@me.com',
//            '招投标数据('.$day.')',
//            '',
//            [TEMP_FILE_PATH . $res['filename']]
//        );

        $res5 = CommonService::getInstance()->sendEmailV2(
            'hujiehuan@huoyan.cn',
            // 'minglongoc@me.com',
            '招投标数据('.$day.')',
            '',
            [TEMP_FILE_PATH . $res['filename']]
        );

        OperatorLog::addRecord(
            [
                'user_id' => 0,
                'msg' =>  " 附件:".TEMP_FILE_PATH . $res['filename'] .' 邮件结果:'.$res1.$res2.$res3.$res5,
                'details' =>json_encode( XinDongService::trace()),
                'type_cname' => '招投标邮件',
            ]
        );

        return true ;
    }
    static function sendEmailV2($day)
    {
        $week =  date('w',strtotime($day));
        if($week != 5 ){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    '$day' => $day,
                   '不是星期五',
                ], JSON_UNESCAPED_UNICODE)
            );
            return  true;
        }
        $startday = date('Y-m-d',strtotime('today -' . ($week - 1) . 'day'));
        $dateStart = $startday.' 00:00:00';
        $dateEnd = $day.' 23:59:59';

        $res = self::exportDataV5($dateStart,$dateEnd);

        if(
            $res['Nums'] <= 0
        ){
            //continue;
            OperatorLog::addRecord(
                [
                    'user_id' => 0,
                    'msg' => '没有数据，不发送邮件 日期：'.$day." 查询结果:".json_encode($res) ,
                    'details' =>json_encode( XinDongService::trace()),
                    'type_cname' => '招投标邮件(新)',
                ]
            );
            return  true;
        }


        $res1 = CommonService::getInstance()->sendEmailV2(
             'tianyongshan@meirixindong.com',
           // 'minglongoc@me.com',
            '招投标数据('.$day.')',
            '',
            [TEMP_FILE_PATH . $res['filename']]
        );

        //
        $res3 = CommonService::getInstance()->sendEmailV2(
            'guoxinxia@meirixindong.com',
            // 'minglongoc@me.com',
            '招投标数据('.$day.')',
            '',
            [TEMP_FILE_PATH . $res['filename']]
        );


        OperatorLog::addRecord(
            [
                'user_id' => 0,
                'msg' =>  " 附件:".TEMP_FILE_PATH . $res['filename'] .' 邮件结果:'.$res1.$res2.$res3.$res5,
                'details' =>json_encode( XinDongService::trace()),
                'type_cname' => '招投标邮件',
            ]
        );

        return true ;
    }
    //TODO  改成按行的 防止内存溢出
    static function  exportDataV4($dateStart,$dateEnd){
        $startMemory = memory_get_usage();
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
        $fileObject = $excel->fileName($filename, 'p1');
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

        $headerTitle= [
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
        ];
        $file = $fileObject
            //->defaultFormat($colorStyle)
            ->header(
                $headerTitle
            )
           // ->defaultFormat($alignStyle)
        ;
        $p1Nums = 0;
        foreach ($financeDatas as $dataItem){
//            CommonService::getInstance()->log4PHP(
//                json_encode([
//                    __CLASS__.__FUNCTION__ .__LINE__,
//                    '$dataItem1' => $dataItem
//                ])
//            );
            $fileObject ->data([$dataItem]);
            $p1Nums ++ ;
        }
        //==============================================
        //p2

        $financeDatas2 = self::getZhaoTouBiaoData(
            $dateStart,$dateEnd,'p2'
        );
        $file->addSheet('p2')
            //->defaultFormat($colorStyle)
            ->header($headerTitle)
            //->defaultFormat($alignStyle)
           ;
        $p2Nums = 0;
        foreach ($financeDatas2 as $dataItem){
//            CommonService::getInstance()->log4PHP(
//                json_encode([
//                    __CLASS__.__FUNCTION__ .__LINE__,
//                    '$dataItem2' => $dataItem
//                ])
//            );
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
        //===============================

        return  [
            'dateStart' => $dateStart  ,
            'dateEnd' => $dateEnd ,
            'filename'=>$filename,
            'p2Nums' => $p2Nums,
            'p1Nums' => $p1Nums,
        ];
    }


    static function  exportDataV5($dateStart,$dateEnd){
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'exportDataV5' => [
                    'start'=>true,
                    '$dateStart'=>$dateStart,
                    '$dateEnd'=>$dateEnd,
                ]
            ])
        );

        $startMemory = memory_get_usage();

        $filename = 'zhao_tou_biao(新)_'.date('YmdHis').'.xlsx';
        $headerTitle= [
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
        ];

        $config=  [
            'path' => TEMP_FILE_PATH // xlsx文件保存路径
        ];

        $excel = new \Vtiful\Kernel\Excel($config);
        $fileObject = $excel->fileName($filename, 'key01');
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

        //zhao_tou_biao_key01
        $sql = " SELECT * FROM zhao_tou_biao_key01 
                    WHERE updated_at >= '$dateStart' AND updated_at <= '$dateEnd'    ";
        $datas01 =  \App\HttpController\Models\RDS3\ZhaoTouBiao\ZhaoTouBiaoAll::findBySqlV2(  $sql );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'exportDataV5' => [
                    '$datas01'=>count($datas01),
                    '$sql'=>$sql,
                    '$dateStart'=>$dateStart,
                    '$dateEnd'=>$dateEnd,
                ]
            ])
        );
        //===============================

        $file = $fileObject
            //->defaultFormat($colorStyle)
            ->header(
                $headerTitle
            )
           // ->defaultFormat($alignStyle)
        ;

        foreach ($datas01 as $dataItem){
            $fileObject ->data(
                [
                    '标题' => $dataItem['标题'] ?:'' , //
                    '项目名称' => $dataItem['项目名称'] ?:'' , //
                    '项目编号' => $dataItem['项目编号'] ?:'' , //
                    '项目简介'  => $dataItem['项目简介'] ?:'' , //
                    '采购方式'   => $dataItem['采购方式'] ?:'' , //
                    '公告类型2'  => $dataItem['公告类型2'] ?:'' , //
                    '公告日期' => $dataItem['公告日期'] ?:'' , //
                    '行政区域_省' => $dataItem['行政区域_省'] ?:'' , //
                    '行政区域_市'  => $dataItem['行政区域_市'] ?:'' , //
                    '行政区域_县' => $dataItem['行政区域_县'] ?:'' , //
                    '采购单位名称' => $dataItem['采购单位名称'] ?:'' , //
                    '采购单位地址' => $dataItem['采购单位地址'] ?:'' , //
                    '采购单位联系人' => $dataItem['采购单位联系人'] ?:'' , //
                    '采购单位联系电话' => $dataItem['采购单位联系电话'] ?:'' , //
                    '名次'  => $dataItem['名次'] ?:'' , //
                    '中标供应商'  => $dataItem['中标供应商'] ?:'' , //
                    '中标金额'  => $dataItem['中标金额'] ?:'' , //
                    '代理机构名称' => $dataItem['代理机构名称'] ?:'' , //
                    '代理机构地址'  => $dataItem['代理机构地址'] ?:'' , //
                    '代理机构联系人'  => $dataItem['代理机构联系人'] ?:'' , //
                    '代理机构联系电话' => $dataItem['代理机构联系电话'] ?:'' , //
                    '评标专家' => $dataItem['评标专家'] ?:'' , //
                    'DLSM_UUID'  => $dataItem['DLSM_UUID'] ?:'' , //
                    'url'  => $dataItem['url'] ?:'' , //
                    'corexml' => $dataItem['corexml'] ?str_split ( $dataItem['corexml'], 32766 )[0]:'' , //
                ]
            );
        }
        //==============================================
        $sql =" SELECT * FROM zhao_tou_biao_key02 
                    WHERE updated_at >= '$dateStart' AND updated_at <= '$dateEnd'  
            " ;
        $datas02 =  \App\HttpController\Models\RDS3\ZhaoTouBiao\ZhaoTouBiaoAll::findBySqlV2( $sql);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'exportDataV5' => [
                    '$datas02'=>count($datas02),
                    '$sql'=>$sql,
                    '$dateStart'=>$dateStart,
                    '$dateEnd'=>$dateEnd,
                ]
            ])
        );

        $file->addSheet('key02')
            //->defaultFormat($colorStyle)
            ->header($headerTitle)
            //->defaultFormat($alignStyle)
           ;

        foreach ($datas02 as $dataItem){
            $fileObject ->data(
                [
                    '标题' => $dataItem['标题'] ?:'' , //
                    '项目名称' => $dataItem['项目名称'] ?:'' , //
                    '项目编号' => $dataItem['项目编号'] ?:'' , //
                    '项目简介'  => $dataItem['项目简介'] ?:'' , //
                    '采购方式'   => $dataItem['采购方式'] ?:'' , //
                    '公告类型2'  => $dataItem['公告类型2'] ?:'' , //
                    '公告日期' => $dataItem['公告日期'] ?:'' , //
                    '行政区域_省' => $dataItem['行政区域_省'] ?:'' , //
                    '行政区域_市'  => $dataItem['行政区域_市'] ?:'' , //
                    '行政区域_县' => $dataItem['行政区域_县'] ?:'' , //
                    '采购单位名称' => $dataItem['采购单位名称'] ?:'' , //
                    '采购单位地址' => $dataItem['采购单位地址'] ?:'' , //
                    '采购单位联系人' => $dataItem['采购单位联系人'] ?:'' , //
                    '采购单位联系电话' => $dataItem['采购单位联系电话'] ?:'' , //
                    '名次'  => $dataItem['名次'] ?:'' , //
                    '中标供应商'  => $dataItem['中标供应商'] ?:'' , //
                    '中标金额'  => $dataItem['中标金额'] ?:'' , //
                    '代理机构名称' => $dataItem['代理机构名称'] ?:'' , //
                    '代理机构地址'  => $dataItem['代理机构地址'] ?:'' , //
                    '代理机构联系人'  => $dataItem['代理机构联系人'] ?:'' , //
                    '代理机构联系电话' => $dataItem['代理机构联系电话'] ?:'' , //
                    '评标专家' => $dataItem['评标专家'] ?:'' , //
                    'DLSM_UUID'  => $dataItem['DLSM_UUID'] ?:'' , //
                    'url'  => $dataItem['url'] ?:'' , //
                    'corexml' => $dataItem['corexml'] ?str_split ( $dataItem['corexml'], 32766 )[0]:'' , //
                ]
            );
        }
        //==============================================

        //==============================================
        $sql = " SELECT * FROM zhao_tou_biao_key03 
                    WHERE updated_at >= '$dateStart' AND updated_at <= '$dateEnd'  
            ";
        $datas03 =  \App\HttpController\Models\RDS3\ZhaoTouBiao\ZhaoTouBiaoAll::findBySqlV2(
            $sql
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'exportDataV5' => [
                    '$datas03'=>count($datas03),
                    '$sql'=>$sql,
                    '$dateStart'=>$dateStart,
                    '$dateEnd'=>$dateEnd,
                ]
            ])
        );

        $file->addSheet('key03')
            //->defaultFormat($colorStyle)
            ->header($headerTitle)
            //->defaultFormat($alignStyle)
        ;

        foreach ($datas03 as $dataItem){
            $fileObject ->data(
                [
                    '标题' => $dataItem['标题'] ?:'' , //
                    '项目名称' => $dataItem['项目名称'] ?:'' , //
                    '项目编号' => $dataItem['项目编号'] ?:'' , //
                    '项目简介'  => $dataItem['项目简介'] ?:'' , //
                    '采购方式'   => $dataItem['采购方式'] ?:'' , //
                    '公告类型2'  => $dataItem['公告类型2'] ?:'' , //
                    '公告日期' => $dataItem['公告日期'] ?:'' , //
                    '行政区域_省' => $dataItem['行政区域_省'] ?:'' , //
                    '行政区域_市'  => $dataItem['行政区域_市'] ?:'' , //
                    '行政区域_县' => $dataItem['行政区域_县'] ?:'' , //
                    '采购单位名称' => $dataItem['采购单位名称'] ?:'' , //
                    '采购单位地址' => $dataItem['采购单位地址'] ?:'' , //
                    '采购单位联系人' => $dataItem['采购单位联系人'] ?:'' , //
                    '采购单位联系电话' => $dataItem['采购单位联系电话'] ?:'' , //
                    '名次'  => $dataItem['名次'] ?:'' , //
                    '中标供应商'  => $dataItem['中标供应商'] ?:'' , //
                    '中标金额'  => $dataItem['中标金额'] ?:'' , //
                    '代理机构名称' => $dataItem['代理机构名称'] ?:'' , //
                    '代理机构地址'  => $dataItem['代理机构地址'] ?:'' , //
                    '代理机构联系人'  => $dataItem['代理机构联系人'] ?:'' , //
                    '代理机构联系电话' => $dataItem['代理机构联系电话'] ?:'' , //
                    '评标专家' => $dataItem['评标专家'] ?:'' , //
                    'DLSM_UUID'  => $dataItem['DLSM_UUID'] ?:'' , //
                    'url'  => $dataItem['url'] ?:'' , //
                    'corexml' => $dataItem['corexml'] ?str_split ( $dataItem['corexml'], 32766 )[0]:'' , //
                ]
            );
        }
        //==============================================

        //==============================================
        $sql =  " SELECT * FROM zhao_tou_biao_key04 
                    WHERE updated_at >= '$dateStart' AND updated_at <= '$dateEnd'  ";
        $datas04 =  \App\HttpController\Models\RDS3\ZhaoTouBiao\ZhaoTouBiaoAll::findBySqlV2(
            $sql
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'exportDataV5' => [
                    '$datas04'=>count($datas04),
                    '$sql'=>$sql,
                    '$dateStart'=>$dateStart,
                    '$dateEnd'=>$dateEnd,
                ]
            ])
        );

        $file->addSheet('key04')
            //->defaultFormat($colorStyle)
            ->header($headerTitle)
            //->defaultFormat($alignStyle)
        ;

        foreach ($datas04 as $dataItem){
            $fileObject ->data(
                [
                    '标题' => $dataItem['标题'] ?:'' , //
                    '项目名称' => $dataItem['项目名称'] ?:'' , //
                    '项目编号' => $dataItem['项目编号'] ?:'' , //
                    '项目简介'  => $dataItem['项目简介'] ?:'' , //
                    '采购方式'   => $dataItem['采购方式'] ?:'' , //
                    '公告类型2'  => $dataItem['公告类型2'] ?:'' , //
                    '公告日期' => $dataItem['公告日期'] ?:'' , //
                    '行政区域_省' => $dataItem['行政区域_省'] ?:'' , //
                    '行政区域_市'  => $dataItem['行政区域_市'] ?:'' , //
                    '行政区域_县' => $dataItem['行政区域_县'] ?:'' , //
                    '采购单位名称' => $dataItem['采购单位名称'] ?:'' , //
                    '采购单位地址' => $dataItem['采购单位地址'] ?:'' , //
                    '采购单位联系人' => $dataItem['采购单位联系人'] ?:'' , //
                    '采购单位联系电话' => $dataItem['采购单位联系电话'] ?:'' , //
                    '名次'  => $dataItem['名次'] ?:'' , //
                    '中标供应商'  => $dataItem['中标供应商'] ?:'' , //
                    '中标金额'  => $dataItem['中标金额'] ?:'' , //
                    '代理机构名称' => $dataItem['代理机构名称'] ?:'' , //
                    '代理机构地址'  => $dataItem['代理机构地址'] ?:'' , //
                    '代理机构联系人'  => $dataItem['代理机构联系人'] ?:'' , //
                    '代理机构联系电话' => $dataItem['代理机构联系电话'] ?:'' , //
                    '评标专家' => $dataItem['评标专家'] ?:'' , //
                    'DLSM_UUID'  => $dataItem['DLSM_UUID'] ?:'' , //
                    'url'  => $dataItem['url'] ?:'' , //
                    'corexml' => $dataItem['corexml'] ?str_split ( $dataItem['corexml'], 32766 )[0]:'' , //
                ]
            );
        }
        //==============================================

        //==============================================
        $sql = " SELECT * FROM zhao_tou_biao_key05 
                    WHERE updated_at >= '$dateStart' AND updated_at <= '$dateEnd'  
            ";
        $datas05 =  \App\HttpController\Models\RDS3\ZhaoTouBiao\ZhaoTouBiaoAll::findBySqlV2(
            $sql
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'exportDataV5' => [
                    '$datas05'=>count($datas05),
                    '$sql'=>$sql,
                    '$dateStart'=>$dateStart,
                    '$dateEnd'=>$dateEnd,
                ]
            ])
        );

        $file->addSheet('key05')
            //->defaultFormat($colorStyle)
            ->header($headerTitle)
            //->defaultFormat($alignStyle)
        ;

        foreach ($datas05 as $dataItem){
            $fileObject ->data(
                [
                    '标题' => $dataItem['标题'] ?:'' , //
                    '项目名称' => $dataItem['项目名称'] ?:'' , //
                    '项目编号' => $dataItem['项目编号'] ?:'' , //
                    '项目简介'  => $dataItem['项目简介'] ?:'' , //
                    '采购方式'   => $dataItem['采购方式'] ?:'' , //
                    '公告类型2'  => $dataItem['公告类型2'] ?:'' , //
                    '公告日期' => $dataItem['公告日期'] ?:'' , //
                    '行政区域_省' => $dataItem['行政区域_省'] ?:'' , //
                    '行政区域_市'  => $dataItem['行政区域_市'] ?:'' , //
                    '行政区域_县' => $dataItem['行政区域_县'] ?:'' , //
                    '采购单位名称' => $dataItem['采购单位名称'] ?:'' , //
                    '采购单位地址' => $dataItem['采购单位地址'] ?:'' , //
                    '采购单位联系人' => $dataItem['采购单位联系人'] ?:'' , //
                    '采购单位联系电话' => $dataItem['采购单位联系电话'] ?:'' , //
                    '名次'  => $dataItem['名次'] ?:'' , //
                    '中标供应商'  => $dataItem['中标供应商'] ?:'' , //
                    '中标金额'  => $dataItem['中标金额'] ?:'' , //
                    '代理机构名称' => $dataItem['代理机构名称'] ?:'' , //
                    '代理机构地址'  => $dataItem['代理机构地址'] ?:'' , //
                    '代理机构联系人'  => $dataItem['代理机构联系人'] ?:'' , //
                    '代理机构联系电话' => $dataItem['代理机构联系电话'] ?:'' , //
                    '评标专家' => $dataItem['评标专家'] ?:'' , //
                    'DLSM_UUID'  => $dataItem['DLSM_UUID'] ?:'' , //
                    'url'  => $dataItem['url'] ?:'' , //
                    'corexml' => $dataItem['corexml'] ?str_split ( $dataItem['corexml'], 32766 )[0]:'' , //
                ]
            );
        }
        //==============================================

        //==============================================
        $datas06 =  \App\HttpController\Models\RDS3\ZhaoTouBiao\ZhaoTouBiaoAll::findBySqlV2(
            " SELECT * FROM zhao_tou_biao_key06 
                    WHERE updated_at >= '$dateStart' AND updated_at <= '$dateEnd'  
            "
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'exportDataV5' => [
                    '$datas05'=>count($datas05),
                    '$sql'=>$sql,
                    '$dateStart'=>$dateStart,
                    '$dateEnd'=>$dateEnd,
                ]
            ])
        );
        $file->addSheet('key06')
            //->defaultFormat($colorStyle)
            ->header($headerTitle)
            //->defaultFormat($alignStyle)
        ;

        foreach ($datas06 as $dataItem){
            $fileObject ->data(
                [
                    '标题' => $dataItem['标题'] ?:'' , //
                    '项目名称' => $dataItem['项目名称'] ?:'' , //
                    '项目编号' => $dataItem['项目编号'] ?:'' , //
                    '项目简介'  => $dataItem['项目简介'] ?:'' , //
                    '采购方式'   => $dataItem['采购方式'] ?:'' , //
                    '公告类型2'  => $dataItem['公告类型2'] ?:'' , //
                    '公告日期' => $dataItem['公告日期'] ?:'' , //
                    '行政区域_省' => $dataItem['行政区域_省'] ?:'' , //
                    '行政区域_市'  => $dataItem['行政区域_市'] ?:'' , //
                    '行政区域_县' => $dataItem['行政区域_县'] ?:'' , //
                    '采购单位名称' => $dataItem['采购单位名称'] ?:'' , //
                    '采购单位地址' => $dataItem['采购单位地址'] ?:'' , //
                    '采购单位联系人' => $dataItem['采购单位联系人'] ?:'' , //
                    '采购单位联系电话' => $dataItem['采购单位联系电话'] ?:'' , //
                    '名次'  => $dataItem['名次'] ?:'' , //
                    '中标供应商'  => $dataItem['中标供应商'] ?:'' , //
                    '中标金额'  => $dataItem['中标金额'] ?:'' , //
                    '代理机构名称' => $dataItem['代理机构名称'] ?:'' , //
                    '代理机构地址'  => $dataItem['代理机构地址'] ?:'' , //
                    '代理机构联系人'  => $dataItem['代理机构联系人'] ?:'' , //
                    '代理机构联系电话' => $dataItem['代理机构联系电话'] ?:'' , //
                    '评标专家' => $dataItem['评标专家'] ?:'' , //
                    'DLSM_UUID'  => $dataItem['DLSM_UUID'] ?:'' , //
                    'url'  => $dataItem['url'] ?:'' , //
                    'corexml' => $dataItem['corexml'] ?str_split ( $dataItem['corexml'], 32766 )[0]:'' , //
                ]
            );
        }
        //==============================================

        //==============================================
        $sql = " SELECT * FROM zhao_tou_biao_key07 
                    WHERE updated_at >= '$dateStart' AND updated_at <= '$dateEnd'  
            ";
        $datas07 =  \App\HttpController\Models\RDS3\ZhaoTouBiao\ZhaoTouBiaoAll::findBySqlV2(
            $sql
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'exportDataV5' => [
                    '$datas07'=>count($datas07),
                    '$sql'=>$sql,
                    '$dateStart'=>$dateStart,
                    '$dateEnd'=>$dateEnd,
                ]
            ])
        );
        $file->addSheet('key07')
            //->defaultFormat($colorStyle)
            ->header($headerTitle)
            //->defaultFormat($alignStyle)
        ;

        foreach ($datas07 as $dataItem){
            $fileObject ->data(
                [
                    '标题' => $dataItem['标题'] ?:'' , //
                    '项目名称' => $dataItem['项目名称'] ?:'' , //
                    '项目编号' => $dataItem['项目编号'] ?:'' , //
                    '项目简介'  => $dataItem['项目简介'] ?:'' , //
                    '采购方式'   => $dataItem['采购方式'] ?:'' , //
                    '公告类型2'  => $dataItem['公告类型2'] ?:'' , //
                    '公告日期' => $dataItem['公告日期'] ?:'' , //
                    '行政区域_省' => $dataItem['行政区域_省'] ?:'' , //
                    '行政区域_市'  => $dataItem['行政区域_市'] ?:'' , //
                    '行政区域_县' => $dataItem['行政区域_县'] ?:'' , //
                    '采购单位名称' => $dataItem['采购单位名称'] ?:'' , //
                    '采购单位地址' => $dataItem['采购单位地址'] ?:'' , //
                    '采购单位联系人' => $dataItem['采购单位联系人'] ?:'' , //
                    '采购单位联系电话' => $dataItem['采购单位联系电话'] ?:'' , //
                    '名次'  => $dataItem['名次'] ?:'' , //
                    '中标供应商'  => $dataItem['中标供应商'] ?:'' , //
                    '中标金额'  => $dataItem['中标金额'] ?:'' , //
                    '代理机构名称' => $dataItem['代理机构名称'] ?:'' , //
                    '代理机构地址'  => $dataItem['代理机构地址'] ?:'' , //
                    '代理机构联系人'  => $dataItem['代理机构联系人'] ?:'' , //
                    '代理机构联系电话' => $dataItem['代理机构联系电话'] ?:'' , //
                    '评标专家' => $dataItem['评标专家'] ?:'' , //
                    'DLSM_UUID'  => $dataItem['DLSM_UUID'] ?:'' , //
                    'url'  => $dataItem['url'] ?:'' , //
                    'corexml' => $dataItem['corexml'] ?str_split ( $dataItem['corexml'], 32766 )[0]:'' , //
                ]
            );
        }
        //==============================================

        //==============================================
        $sql = " SELECT * FROM zhao_tou_biao_key08 
                    WHERE updated_at >= '$dateStart' AND updated_at <= '$dateEnd'  
            ";
        $datas08 =  \App\HttpController\Models\RDS3\ZhaoTouBiao\ZhaoTouBiaoAll::findBySqlV2(
            $sql
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'exportDataV5' => [
                    '$datas08'=>count($datas08),
                    '$sql'=>$sql,
                    '$dateStart'=>$dateStart,
                    '$dateEnd'=>$dateEnd,
                ]
            ])
        );

        $file->addSheet('key08')
            //->defaultFormat($colorStyle)
            ->header($headerTitle)
            //->defaultFormat($alignStyle)
        ;

        foreach ($datas08 as $dataItem){
            $fileObject ->data(
                [
                    '标题' => $dataItem['标题'] ?:'' , //
                    '项目名称' => $dataItem['项目名称'] ?:'' , //
                    '项目编号' => $dataItem['项目编号'] ?:'' , //
                    '项目简介'  => $dataItem['项目简介'] ?:'' , //
                    '采购方式'   => $dataItem['采购方式'] ?:'' , //
                    '公告类型2'  => $dataItem['公告类型2'] ?:'' , //
                    '公告日期' => $dataItem['公告日期'] ?:'' , //
                    '行政区域_省' => $dataItem['行政区域_省'] ?:'' , //
                    '行政区域_市'  => $dataItem['行政区域_市'] ?:'' , //
                    '行政区域_县' => $dataItem['行政区域_县'] ?:'' , //
                    '采购单位名称' => $dataItem['采购单位名称'] ?:'' , //
                    '采购单位地址' => $dataItem['采购单位地址'] ?:'' , //
                    '采购单位联系人' => $dataItem['采购单位联系人'] ?:'' , //
                    '采购单位联系电话' => $dataItem['采购单位联系电话'] ?:'' , //
                    '名次'  => $dataItem['名次'] ?:'' , //
                    '中标供应商'  => $dataItem['中标供应商'] ?:'' , //
                    '中标金额'  => $dataItem['中标金额'] ?:'' , //
                    '代理机构名称' => $dataItem['代理机构名称'] ?:'' , //
                    '代理机构地址'  => $dataItem['代理机构地址'] ?:'' , //
                    '代理机构联系人'  => $dataItem['代理机构联系人'] ?:'' , //
                    '代理机构联系电话' => $dataItem['代理机构联系电话'] ?:'' , //
                    '评标专家' => $dataItem['评标专家'] ?:'' , //
                    'DLSM_UUID'  => $dataItem['DLSM_UUID'] ?:'' , //
                    'url'  => $dataItem['url'] ?:'' , //
                    'corexml' => $dataItem['corexml'] ?str_split ( $dataItem['corexml'], 32766 )[0]:'' , //
                ]
            );
        }
        //==============================================
        //==============================================
        $sql = " SELECT * FROM zhao_tou_biao_key09 
                    WHERE updated_at >= '$dateStart' AND updated_at <= '$dateEnd'  
            ";
        $datas09 =  \App\HttpController\Models\RDS3\ZhaoTouBiao\ZhaoTouBiaoAll::findBySqlV2(
            $sql
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'exportDataV5' => [
                    '$datas09'=>count($datas09),
                    '$sql'=>$sql,
                    '$dateStart'=>$dateStart,
                    '$dateEnd'=>$dateEnd,
                ]
            ])
        );

        $file->addSheet('key09')
            //->defaultFormat($colorStyle)
            ->header($headerTitle)
            //->defaultFormat($alignStyle)
        ;

        foreach ($datas09 as $dataItem){
            $fileObject ->data(
                [
                    '标题' => $dataItem['标题'] ?:'' , //
                    '项目名称' => $dataItem['项目名称'] ?:'' , //
                    '项目编号' => $dataItem['项目编号'] ?:'' , //
                    '项目简介'  => $dataItem['项目简介'] ?:'' , //
                    '采购方式'   => $dataItem['采购方式'] ?:'' , //
                    '公告类型2'  => $dataItem['公告类型2'] ?:'' , //
                    '公告日期' => $dataItem['公告日期'] ?:'' , //
                    '行政区域_省' => $dataItem['行政区域_省'] ?:'' , //
                    '行政区域_市'  => $dataItem['行政区域_市'] ?:'' , //
                    '行政区域_县' => $dataItem['行政区域_县'] ?:'' , //
                    '采购单位名称' => $dataItem['采购单位名称'] ?:'' , //
                    '采购单位地址' => $dataItem['采购单位地址'] ?:'' , //
                    '采购单位联系人' => $dataItem['采购单位联系人'] ?:'' , //
                    '采购单位联系电话' => $dataItem['采购单位联系电话'] ?:'' , //
                    '名次'  => $dataItem['名次'] ?:'' , //
                    '中标供应商'  => $dataItem['中标供应商'] ?:'' , //
                    '中标金额'  => $dataItem['中标金额'] ?:'' , //
                    '代理机构名称' => $dataItem['代理机构名称'] ?:'' , //
                    '代理机构地址'  => $dataItem['代理机构地址'] ?:'' , //
                    '代理机构联系人'  => $dataItem['代理机构联系人'] ?:'' , //
                    '代理机构联系电话' => $dataItem['代理机构联系电话'] ?:'' , //
                    '评标专家' => $dataItem['评标专家'] ?:'' , //
                    'DLSM_UUID'  => $dataItem['DLSM_UUID'] ?:'' , //
                    'url'  => $dataItem['url'] ?:'' , //
                    'corexml' => $dataItem['corexml'] ?str_split ( $dataItem['corexml'], 32766 )[0]:'' , //
                ]
            );
        }
        //==============================================


        //==============================================
        $sql = " SELECT * FROM zhao_tou_biao_key10 
                    WHERE updated_at >= '$dateStart' AND updated_at <= '$dateEnd'  
            ";
        $datas10 =  \App\HttpController\Models\RDS3\ZhaoTouBiao\ZhaoTouBiaoAll::findBySqlV2(
            $sql
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'exportDataV5' => [
                    '$datas10'=>count($datas10),
                    '$sql'=>$sql,
                    '$dateStart'=>$dateStart,
                    '$dateEnd'=>$dateEnd,
                ]
            ])
        );

        $file->addSheet('key10')
            //->defaultFormat($colorStyle)
            ->header($headerTitle)
            //->defaultFormat($alignStyle)
        ;

        foreach ($datas10 as $dataItem){
            $fileObject ->data(
                [
                    '标题' => $dataItem['标题'] ?:'' , //
                    '项目名称' => $dataItem['项目名称'] ?:'' , //
                    '项目编号' => $dataItem['项目编号'] ?:'' , //
                    '项目简介'  => $dataItem['项目简介'] ?:'' , //
                    '采购方式'   => $dataItem['采购方式'] ?:'' , //
                    '公告类型2'  => $dataItem['公告类型2'] ?:'' , //
                    '公告日期' => $dataItem['公告日期'] ?:'' , //
                    '行政区域_省' => $dataItem['行政区域_省'] ?:'' , //
                    '行政区域_市'  => $dataItem['行政区域_市'] ?:'' , //
                    '行政区域_县' => $dataItem['行政区域_县'] ?:'' , //
                    '采购单位名称' => $dataItem['采购单位名称'] ?:'' , //
                    '采购单位地址' => $dataItem['采购单位地址'] ?:'' , //
                    '采购单位联系人' => $dataItem['采购单位联系人'] ?:'' , //
                    '采购单位联系电话' => $dataItem['采购单位联系电话'] ?:'' , //
                    '名次'  => $dataItem['名次'] ?:'' , //
                    '中标供应商'  => $dataItem['中标供应商'] ?:'' , //
                    '中标金额'  => $dataItem['中标金额'] ?:'' , //
                    '代理机构名称' => $dataItem['代理机构名称'] ?:'' , //
                    '代理机构地址'  => $dataItem['代理机构地址'] ?:'' , //
                    '代理机构联系人'  => $dataItem['代理机构联系人'] ?:'' , //
                    '代理机构联系电话' => $dataItem['代理机构联系电话'] ?:'' , //
                    '评标专家' => $dataItem['评标专家'] ?:'' , //
                    'DLSM_UUID'  => $dataItem['DLSM_UUID'] ?:'' , //
                    'url'  => $dataItem['url'] ?:'' , //
                    'corexml' => $dataItem['corexml'] ?str_split ( $dataItem['corexml'], 32766 )[0]:'' , //
                ]
            );
        }
        //==============================================


        //==============================================
        $sql = " SELECT * FROM zhao_tou_biao_key11 
                    WHERE updated_at >= '$dateStart' AND updated_at <= '$dateEnd'  
            ";
        $datas11 =  \App\HttpController\Models\RDS3\ZhaoTouBiao\ZhaoTouBiaoAll::findBySqlV2(
            $sql
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'exportDataV5' => [
                    '$datas11'=>count($datas11),
                    '$sql'=>$sql,
                    '$dateStart'=>$dateStart,
                    '$dateEnd'=>$dateEnd,
                ]
            ])
        );
        $file->addSheet('key11')
            //->defaultFormat($colorStyle)
            ->header($headerTitle)
            //->defaultFormat($alignStyle)
        ;

        foreach ($datas11 as $dataItem){
            $fileObject ->data(
                [
                    '标题' => $dataItem['标题'] ?:'' , //
                    '项目名称' => $dataItem['项目名称'] ?:'' , //
                    '项目编号' => $dataItem['项目编号'] ?:'' , //
                    '项目简介'  => $dataItem['项目简介'] ?:'' , //
                    '采购方式'   => $dataItem['采购方式'] ?:'' , //
                    '公告类型2'  => $dataItem['公告类型2'] ?:'' , //
                    '公告日期' => $dataItem['公告日期'] ?:'' , //
                    '行政区域_省' => $dataItem['行政区域_省'] ?:'' , //
                    '行政区域_市'  => $dataItem['行政区域_市'] ?:'' , //
                    '行政区域_县' => $dataItem['行政区域_县'] ?:'' , //
                    '采购单位名称' => $dataItem['采购单位名称'] ?:'' , //
                    '采购单位地址' => $dataItem['采购单位地址'] ?:'' , //
                    '采购单位联系人' => $dataItem['采购单位联系人'] ?:'' , //
                    '采购单位联系电话' => $dataItem['采购单位联系电话'] ?:'' , //
                    '名次'  => $dataItem['名次'] ?:'' , //
                    '中标供应商'  => $dataItem['中标供应商'] ?:'' , //
                    '中标金额'  => $dataItem['中标金额'] ?:'' , //
                    '代理机构名称' => $dataItem['代理机构名称'] ?:'' , //
                    '代理机构地址'  => $dataItem['代理机构地址'] ?:'' , //
                    '代理机构联系人'  => $dataItem['代理机构联系人'] ?:'' , //
                    '代理机构联系电话' => $dataItem['代理机构联系电话'] ?:'' , //
                    '评标专家' => $dataItem['评标专家'] ?:'' , //
                    'DLSM_UUID'  => $dataItem['DLSM_UUID'] ?:'' , //
                    'url'  => $dataItem['url'] ?:'' , //
                    'corexml' => $dataItem['corexml'] ?str_split ( $dataItem['corexml'], 32766 )[0]:'' , //
                ]
            );
        }
        //==============================================

        //==============================================
        $sql = " SELECT * FROM zhao_tou_biao_key12 
                    WHERE updated_at >= '$dateStart' AND updated_at <= '$dateEnd'  
            ";
        $datas12 =  \App\HttpController\Models\RDS3\ZhaoTouBiao\ZhaoTouBiaoAll::findBySqlV2(
            $sql
        );

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'exportDataV5' => [
                    '$datas12'=>count($datas12),
                    '$sql'=>$sql,
                    '$dateStart'=>$dateStart,
                    '$dateEnd'=>$dateEnd,
                ]
            ])
        );
        $file->addSheet('key12')
            //->defaultFormat($colorStyle)
            ->header($headerTitle)
            //->defaultFormat($alignStyle)
        ;

        foreach ($datas12 as $dataItem){
            $fileObject ->data(
                [
                    '标题' => $dataItem['标题'] ?:'' , //
                    '项目名称' => $dataItem['项目名称'] ?:'' , //
                    '项目编号' => $dataItem['项目编号'] ?:'' , //
                    '项目简介'  => $dataItem['项目简介'] ?:'' , //
                    '采购方式'   => $dataItem['采购方式'] ?:'' , //
                    '公告类型2'  => $dataItem['公告类型2'] ?:'' , //
                    '公告日期' => $dataItem['公告日期'] ?:'' , //
                    '行政区域_省' => $dataItem['行政区域_省'] ?:'' , //
                    '行政区域_市'  => $dataItem['行政区域_市'] ?:'' , //
                    '行政区域_县' => $dataItem['行政区域_县'] ?:'' , //
                    '采购单位名称' => $dataItem['采购单位名称'] ?:'' , //
                    '采购单位地址' => $dataItem['采购单位地址'] ?:'' , //
                    '采购单位联系人' => $dataItem['采购单位联系人'] ?:'' , //
                    '采购单位联系电话' => $dataItem['采购单位联系电话'] ?:'' , //
                    '名次'  => $dataItem['名次'] ?:'' , //
                    '中标供应商'  => $dataItem['中标供应商'] ?:'' , //
                    '中标金额'  => $dataItem['中标金额'] ?:'' , //
                    '代理机构名称' => $dataItem['代理机构名称'] ?:'' , //
                    '代理机构地址'  => $dataItem['代理机构地址'] ?:'' , //
                    '代理机构联系人'  => $dataItem['代理机构联系人'] ?:'' , //
                    '代理机构联系电话' => $dataItem['代理机构联系电话'] ?:'' , //
                    '评标专家' => $dataItem['评标专家'] ?:'' , //
                    'DLSM_UUID'  => $dataItem['DLSM_UUID'] ?:'' , //
                    'url'  => $dataItem['url'] ?:'' , //
                    'corexml' => $dataItem['corexml'] ?str_split ( $dataItem['corexml'], 32766 )[0]:'' , //
                ]
            );
        }
        //==============================================

        //==============================================
        $sql = " SELECT * FROM zhao_tou_biao_key13 
                    WHERE updated_at >= '$dateStart' AND updated_at <= '$dateEnd'  
            ";
        $datas13 =  \App\HttpController\Models\RDS3\ZhaoTouBiao\ZhaoTouBiaoAll::findBySqlV2(
            $sql
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'exportDataV5' => [
                    '$datas13'=>count($datas13),
                    '$sql'=>$sql,
                    '$dateStart'=>$dateStart,
                    '$dateEnd'=>$dateEnd,
                ]
            ])
        );

        $file->addSheet('key13')
            //->defaultFormat($colorStyle)
            ->header($headerTitle)
            //->defaultFormat($alignStyle)
        ;

        foreach ($datas13 as $dataItem){
            $fileObject ->data(
                [
                    '标题' => $dataItem['标题'] ?:'' , //
                    '项目名称' => $dataItem['项目名称'] ?:'' , //
                    '项目编号' => $dataItem['项目编号'] ?:'' , //
                    '项目简介'  => $dataItem['项目简介'] ?:'' , //
                    '采购方式'   => $dataItem['采购方式'] ?:'' , //
                    '公告类型2'  => $dataItem['公告类型2'] ?:'' , //
                    '公告日期' => $dataItem['公告日期'] ?:'' , //
                    '行政区域_省' => $dataItem['行政区域_省'] ?:'' , //
                    '行政区域_市'  => $dataItem['行政区域_市'] ?:'' , //
                    '行政区域_县' => $dataItem['行政区域_县'] ?:'' , //
                    '采购单位名称' => $dataItem['采购单位名称'] ?:'' , //
                    '采购单位地址' => $dataItem['采购单位地址'] ?:'' , //
                    '采购单位联系人' => $dataItem['采购单位联系人'] ?:'' , //
                    '采购单位联系电话' => $dataItem['采购单位联系电话'] ?:'' , //
                    '名次'  => $dataItem['名次'] ?:'' , //
                    '中标供应商'  => $dataItem['中标供应商'] ?:'' , //
                    '中标金额'  => $dataItem['中标金额'] ?:'' , //
                    '代理机构名称' => $dataItem['代理机构名称'] ?:'' , //
                    '代理机构地址'  => $dataItem['代理机构地址'] ?:'' , //
                    '代理机构联系人'  => $dataItem['代理机构联系人'] ?:'' , //
                    '代理机构联系电话' => $dataItem['代理机构联系电话'] ?:'' , //
                    '评标专家' => $dataItem['评标专家'] ?:'' , //
                    'DLSM_UUID'  => $dataItem['DLSM_UUID'] ?:'' , //
                    'url'  => $dataItem['url'] ?:'' , //
                    'corexml' => $dataItem['corexml'] ?str_split ( $dataItem['corexml'], 32766 )[0]:'' , //
                ]
            );
        }
        //==============================================

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'exportDataV5' => [
                    'generate data done . memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M',
                    '$dateStart'=>$dateStart,
                    '$dateEnd'=>$dateEnd,
                ]
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

        return  [
            'dateStart' => $dateStart  ,
            'dateEnd' => $dateEnd ,
            'filename'=>$filename,
            'Nums' => count($datas01) +  count($datas02) +  count($datas03) + count($datas04) + count($datas05) + count($datas06)
                + count($datas06) +  count($datas07) + count($datas07) + count($datas08)+ count($datas09) +  count($datas10)
                +  count($datas11) +  count($datas12) +  count($datas13)
        ];
    }



    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString());
    }

}
