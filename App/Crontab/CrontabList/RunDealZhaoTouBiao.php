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
use App\HttpController\Models\MRXD\TmpInfo;
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
        //设置为正在执行中
        ConfigInfo::setIsRunning(__CLASS__);

        $day = date('Y-m-d');

        //第一次的招投标
        $res = self::sendEmail($day);

        //第二次的招投标
        $week =  date('w',strtotime($day));
        if($week == 5 ){
            $res = self::sendEmailV4($day,[
                'tianyongshan@meirixindong.com',
                'minglongoc@me.com',
                'zhengmeng@meirixindong.com',
            ]);
        }

        return true ;
    }

    static function sendEmail($day)
    {

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

    static function sendEmailV4($day,$emailsLists)
    {


        $dateStart = '2022-11-07 00:00:00';
        $dateEnd = $day.' 23:59:59';
        $res1 = self::exportDataV4($dateStart,$dateEnd);

        $res = self::exportDataV8($day);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'sendEmailV2' => [
                    '1'=> [TEMP_FILE_PATH . $res1['filename']],
                    '2'=> [TEMP_FILE_PATH . $res['filename']],
                ]
            ])
        );

        CommonService::getInstance()->sendEmailV2(
         'tianyongshan@meirixindong.com',
            '招投标数据('.$day.')',
            '',
            [TEMP_FILE_PATH . $res['filename']]
        );

//        $sendResArr = [];
//        foreach ($emailsLists as $emailsAddress){
//            $sendRes = CommonService::getInstance()->sendEmailV2(
//                $emailsAddress,//'',
//                '招投标数据-新('.$day.')',
//                '',
//                [TEMP_FILE_PATH . $res['filename']]
//            );
//            $sendResArr[$emailsAddress] = $sendRes;
//        }



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
    static function  exportDataV5($day){
        $startMemory = memory_get_usage();

        $the_date = $day;
        $the_day_of_week = date("w",strtotime($the_date)); //sunday is 0

        $first_day_of_week = date("Y-m-d",strtotime( $the_date )-60*60*24*($the_day_of_week)+60*60*24*1 );
        $last_day_of_week = date("Y-m-d",strtotime($first_day_of_week)+60*60*24*4 );

        $dateStart = $first_day_of_week.' 00:00:00';
        $dateEnd = $last_day_of_week.' 23:59:59';


        $datas =  \App\HttpController\Models\RDS3\ZhaoTouBiao\ZhaoTouBiaoAll::findBySqlV2(
            " SELECT * FROM zhao_tou_biao_key03 WHERE updated_at >= '$dateStart' AND  updated_at <= '$dateEnd'  "
        );
        foreach ($datas as $data){
            TmpInfo::addRecord([
                'biao_ti' => $data['标题']?:'',
                'xiang_mu_ming_cheng' => $data['项目名称']?:'',
                'xiang_mu_bian_hao' => $data['项目编号']?:'',
                'xiang_mu_jian_jie' => $data['项目简介']?:'',
                'cai_gou_fang_shi' => $data['采购方式']?:'',
                'gong_gao_lei_xing2' => $data['公告类型2']?:'',
                'gong_gao_ri_qi' => $data['公告日期']?:'',
                'xing_zheng_qv_yu_sheng' => $data['行政区域_省']?:'',
                'xing_zheng_qv_yu_shi' => $data['行政区域_市']?:'',
                'xing_zheng_qv_yu_xian' => $data['行政区域_县']?:'',
                'cai_gou_dan_wei_ming_cheng' => $data['采购单位名称']?:'',
                'cai_gou_dan_wei_di_zhi' => $data['采购单位地址']?:'',
                'cai_gou_dan_wei_lian_xi_ren' => $data['采购单位联系人']?:'',
                'cai_gou_dan_wei_lian_xi__dian_hua' => $data['采购单位联系电话']?:'',
                'ming_ci' => $data['名次']?:'',
                'zhong_biao_gong_ying_shang' => $data['中标供应商']?:'',
                'zhong_biao_jin_e' => $data['中标金额']?:'',
                'dai_li_ji_gou_ming_cheng' => $data['代理机构名称']?:'',
                'dai_li_ji_gou_di_zhi' => $data['代理机构地址']?:'',
                'dai_li_ji_gou_lian_xi_ren' => $data['代理机构联系人']?:'',
                'dai_li_ji_gou_lian_xi_dian_hua' => $data['代理机构联系电话']?:'',
                'ping_gu_zhuan_jia' => $data['评标专家']?:'',
                'DLSM_UUID' => $data['DLSM_UUID']?:'',
                'url' => $data['url']?:'',
                'corexml' => $data['corexml']?:'',
                'updated_at' => $data['updated_at']?:'',
                'source' => $data['source']?:'',

            ]);
        }



        $filename = 'zhao_tou_biao_new_'.date('YmdHis').'.xlsx';

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
            '来源' , //
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
        $p1Nums = 1;
        $datas =  TmpInfo::findBySql(
            "  "
        );

        foreach ($datas as $dataItem){
            $tmpDataItem = [
                'source' => $dataItem['source'] , //
                'biao_ti' => $dataItem['biao_ti'] , //
                'xiang_mu_ming_cheng' => $dataItem['xiang_mu_ming_cheng'] , //
                'xiang_mu_bian_hao' => $dataItem['xiang_mu_bian_hao'] , //
                'xiang_mu_jian_jie'  => $dataItem['xiang_mu_jian_jie'] ?:'' , //
                'cai_gou_fang_shi'   => $dataItem['cai_gou_fang_shi'] ?:'' , //
                'gong_gao_lei_xing2'  => $dataItem['gong_gao_lei_xing2'] ?:'' , //
                'gong_gao_ri_qi' => $dataItem['gong_gao_ri_qi'] ?:'' , //
                'xing_zheng_qv_yu_sheng' => $dataItem['xing_zheng_qv_yu_sheng'] ?:'' , //
                'xing_zheng_qv_yu_shi'  => $dataItem['xing_zheng_qv_yu_shi'] ?:'' , //
                'xing_zheng_qv_yu_xian' => $dataItem['xing_zheng_qv_yu_xian'] ?:'' , //
                'cai_gou_dan_wei_ming_cheng' => $dataItem['cai_gou_dan_wei_ming_cheng'] ?:'' , //
                'cai_gou_dan_wei_di_zhi' => $dataItem['cai_gou_dan_wei_di_zhi'] ?:'' , //
                'cai_gou_dan_wei_lian_xi_ren' => $dataItem['cai_gou_dan_wei_lian_xi_ren'] ?:'' , //
                'cai_gou_dan_wei_lian_xi__dian_hua' => $dataItem['cai_gou_dan_wei_lian_xi__dian_hua'] ?:'' , //
                'ming_ci'  => $dataItem['ming_ci'] ?:'' , //
                'zhong_biao_gong_ying_shang'  => $dataItem['zhong_biao_gong_ying_shang'] ?:'' , //
                'zhong_biao_jin_e'  => $dataItem['zhong_biao_jin_e'] ?:'' , //
                'dai_li_ji_gou_ming_cheng' => $dataItem['dai_li_ji_gou_ming_cheng'] ?:'' , //
                'dai_li_ji_gou_di_zhi'  => $dataItem['dai_li_ji_gou_di_zhi'] ?:'' , //
                'dai_li_ji_gou_lian_xi_ren'  => $dataItem['dai_li_ji_gou_lian_xi_ren'] ?:'' , //
                'dai_li_ji_gou_lian_xi_dian_hua' => $dataItem['dai_li_ji_gou_lian_xi_dian_hua'] ?:'' , //
                'ping_gu_zhuan_jia' => $dataItem['ping_gu_zhuan_jia'] ?:'' , //
                'DLSM_UUID'  => $dataItem['DLSM_UUID'] ?:'' , //
                'url'  => $dataItem['url'] ?:'' , //
                //'corexml' => $comment_content  , //
                'corexml' => $dataItem['corexml'] ?str_split ( $dataItem['corexml'], 32766 )[0]:'' , //
            ];
            $fileObject ->data([$tmpDataItem]);
            $p1Nums ++ ;
        }



        //==============================================
        //p2


        $file->addSheet('p2')
            //->defaultFormat($colorStyle)
            ->header($headerTitle)
            //->defaultFormat($alignStyle)
           ;
        $p2Nums = 0;
        foreach ($datas as $dataItem){
            $comment_content =  $dataItem['corexml'];
            $tmpDataItem = [
                '来源' => '' , //
                '标题' => $dataItem['标题'] , //
                '项目名称' => $dataItem['项目名称'] , //
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
                //'corexml' => $comment_content  , //
                'corexml' => $comment_content ?str_split ( $comment_content, 32766 )[0]:'' , //
            ];
            $fileObject ->data([$tmpDataItem]);
            $p2Nums ++ ;
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
            'filename_url'=>'http://api.test.meirixindong.com/Static/Temp/'.$filename,
            'p2Nums' => $p2Nums,
            'p1Nums' => $p1Nums,
        ];
    }



    static function  exportDataV8($day){
        $startMemory = memory_get_usage();

        $the_date = $day;
        $the_day_of_week = date("w",strtotime($the_date)); //sunday is 0

        $first_day_of_week = date("Y-m-d",strtotime( $the_date )-60*60*24*($the_day_of_week)+60*60*24*1 );
        $last_day_of_week = date("Y-m-d",strtotime($first_day_of_week)+60*60*24*4 );

        $dateStart = $first_day_of_week.' 00:00:00';
        $dateEnd = $last_day_of_week.' 23:59:59';


        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'exportDataV8' => [
                    '$day'=>$day,
                    '$dateStart'=>$dateStart,
                    '$dateEnd'=>$dateEnd,
                ]
            ])
        );


        //写到csv里
        $fileName = 'zhao_tou_biao_new_'.date('YmdHis').".csv";
        $f = fopen(TEMP_FILE_PATH.$fileName, "w");
        //fwrite($f,chr(0xEF).chr(0xBB).chr(0xBF));
        $headerTitle= [
            '来源' , //
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
        //插入表头
        fputcsv($f, $headerTitle);

        $tables = [
            'zhao_tou_biao_key01',
            'zhao_tou_biao_key02',
            'zhao_tou_biao_key03',
            'zhao_tou_biao_key04',
            'zhao_tou_biao_key05',
            'zhao_tou_biao_key06',
            'zhao_tou_biao_key07',
            'zhao_tou_biao_key08',
            'zhao_tou_biao_key09',
            'zhao_tou_biao_key10',
            'zhao_tou_biao_key11',
            'zhao_tou_biao_key12',
            'zhao_tou_biao_key13',
        ];

        $totalNums = 0;
        foreach ($tables as $table){
            $datas =  \App\HttpController\Models\RDS3\ZhaoTouBiao\ZhaoTouBiaoAll::findBySqlV2(
                " SELECT * FROM $table WHERE updated_at >= '$dateStart' AND  updated_at <= '$dateEnd'  "
            );
            $totalNums +=count($datas);
            foreach ($datas as $dataItem){
                $comment_content =  str_replace(",","，",$dataItem['corexml']);
                $comment_content =  str_replace("\r\n","",$comment_content);
                $comment_content =  str_replace("\r","",$comment_content);
                $comment_content =  str_replace("\n","",$comment_content);
                $comment_content =  str_replace("\"","",$comment_content);

                $tmpDataItem = [
                    '来源' => $table , //
                    '标题' => $dataItem['标题'] , //
                    '项目名称' => $dataItem['项目名称'] , //
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
                    //'corexml' => $comment_content  , //
                    'corexml' => $comment_content ?str_split ( $comment_content, 32766 )[0]:'' , //
                ];
                fputcsv($f, $tmpDataItem);
            }
        }

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'exportDataV8' => [
                    '$day'=>$day,
                    '$fileName'=>$fileName,
                    'generate data done . memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M',
                ]
            ])
        );

        return  [
            'dateStart' => $dateStart  ,
            'dateEnd' => $dateEnd ,
            'filename'=>$fileName,
            'filename_url'=>'http://api.test.meirixindong.com/Static/Temp/'.$fileName,
            'Nums' => $totalNums
        ];
    }



    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString());
    }

}
