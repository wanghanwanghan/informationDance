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



class RunDealApiSouKe extends AbstractCronTask
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

    static function getYieldData($size,$offset,$requestDataArr){
        $datas = [];
        $companyEsModel = new \App\ElasticSearch\Model\Company();
        $searchOption = json_decode($requestDataArr['searchOption'],true);
        $companyEsModel
            //经营范围
            ->SetQueryByBusinessScope($requestDataArr['basic_opscope'])
            //数字经济及其核心产业
            ->SetQueryByBasicSzjjid($requestDataArr['basic_szjjid'])
            // 搜索文案 智能搜索
            ->SetQueryBySearchText( $requestDataArr['searchText'])
            // 搜索战略新兴产业
            ->SetQueryByBasicJlxxcyid( $requestDataArr['basic_jlxxcyid']   )
            // 搜索shang_pin_data 商品信息 appStr:五香;农庄
            ->SetQueryByShangPinData( $requestDataArr['appStr']  )
            //必须存在官网
            ->SetQueryByWeb($searchOption)
            //必须存在APP
            ->SetQueryByApp($searchOption)
            //必须是物流企业
            ->SetQueryByWuLiuQiYe($searchOption)
            // 企业类型 :传过来的是10 20 转换成对应文案 然后再去搜索
            ->SetQueryByCompanyOrgType($searchOption)
            // 成立年限  ：传过来的是 10  20 30 转换成最小值最大值范围后 再去搜索
            ->SetQueryByEstiblishTime($searchOption)
            // 营业状态   传过来的是 10  20  转换成文案后 去匹配
            ->SetQueryByRegStatus($searchOption)
            // 注册资本 传过来的是 10 20 转换成最大最小范围后 再去搜索
            ->SetQueryByRegCaptial($searchOption)
            // 团队人数 传过来的是 10 20 转换成最大最小范围后 再去搜索
            ->SetQueryByTuanDuiRenShu($searchOption)
            // 营收规模  传过来的是 10 20 转换成对应文案后再去匹配
            ->SetQueryByYingShouGuiMo($searchOption)
            //四级分类 basic_nicid: A0111,A0112,A0113,
            ->SetQueryBySiJiFenLei(    $requestDataArr['basic_nicid'] )
            // 地区 basic_regionid: 110101,110102,
            ->SetQueryByBasicRegionid(   $requestDataArr['basic_regionid']  )
            ->addSize($size)
            ->addFrom($offset)
            //设置默认值 不传任何条件 搜全部
            ->setDefault()
            ->searchFromEs()
            // 格式化下日期和时间
            ->formatEsDate()
            // 格式化下金额
            ->formatEsMoney()
        ;

        foreach($companyEsModel->return_data['hits']['hits'] as $dataItem){
            $addresAndEmailData = (new XinDongService())->getLastPostalAddressAndEmail($dataItem);
            $dataItem['_source']['last_postal_address'] = $addresAndEmailData['last_postal_address'];
            $dataItem['_source']['last_email'] = $addresAndEmailData['last_email'];

            $dataItem['_source']['logo'] =  (new XinDongService())->getLogoByEntId($dataItem['_source']['xd_id']);

            // 添加tag
            $dataItem['_source']['tags'] = array_values(
                (new XinDongService())::getAllTagesByData(
                    $dataItem['_source']
                )
            );

            // 官网
            $webStr = trim($dataItem['_source']['web']);
            if(!$webStr){
                yield $datas[] = $dataItem['_source'];
                continue;
            }
            $webArr = explode('&&&', $webStr);
            !empty($webArr) && $dataItem['_source']['web'] = end($webArr);

            yield $datas[] = $dataItem['_source'];
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

        //生成文件
        //生成xlsx 当前插件  暂时只能生成一万？ 调整后再改成xlsx的
        //self::generateFileExcel(3);
        self::generateFileExcelV2(3);
        //self::generateFileCsvV2(3);

        //确认交付
        self::deliver(3);
        //设置为已执行完毕
        ConfigInfo::setIsDone(__CLASS__);

        return true ;   
    }

    function getYieldDataForSouKe($totalNums,$requestDataArr,$fieldsArr){

        $startMemory = memory_get_usage();
        $start = microtime(true);
        $searchOption = json_decode($requestDataArr['searchOption'],true);
        $datas = [];

//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                __CLASS__.__FUNCTION__ .__LINE__,
//                '$datas' => $datas
//            ])
//        );

        $size = 5000;
        $offset = 0;
        $nums =1;
        $lastId = 0;
        while ($totalNums > 0) {
            if($totalNums<$size){
                $size = $totalNums;
            }

            $companyEsModel = new \App\ElasticSearch\Model\Company();
            $companyEsModel
                //经营范围
                ->SetQueryByBusinessScope($requestDataArr['basic_opscope'])
                //数字经济及其核心产业
                ->SetQueryByBasicSzjjid($requestDataArr['basic_szjjid'])
                // 搜索文案 智能搜索
                ->SetQueryBySearchText( $requestDataArr['searchText'])
                // 搜索战略新兴产业
                ->SetQueryByBasicJlxxcyid( $requestDataArr['basic_jlxxcyid']   )
                // 搜索shang_pin_data 商品信息 appStr:五香;农庄
                ->SetQueryByShangPinData( $requestDataArr['appStr']  )
                //必须存在官网
                ->SetQueryByWeb($searchOption)
                //必须存在APP
                ->SetQueryByApp($searchOption)
                ->addSort('_id',"asc")
                //必须是物流企业
                ->SetQueryByWuLiuQiYe($searchOption)
                // 企业类型 :传过来的是10 20 转换成对应文案 然后再去搜索
                ->SetQueryByCompanyOrgType($searchOption)
                // 成立年限  ：传过来的是 10  20 30 转换成最小值最大值范围后 再去搜索
                ->SetQueryByEstiblishTime($searchOption)
                // 营业状态   传过来的是 10  20  转换成文案后 去匹配
                ->SetQueryByRegStatus($searchOption)
                // 注册资本 传过来的是 10 20 转换成最大最小范围后 再去搜索
                ->SetQueryByRegCaptial($searchOption)
                // 团队人数 传过来的是 10 20 转换成最大最小范围后 再去搜索
                ->SetQueryByTuanDuiRenShu($searchOption)
                // 营收规模  传过来的是 10 20 转换成对应文案后再去匹配
                ->SetQueryByYingShouGuiMo($searchOption)
                //四级分类 basic_nicid: A0111,A0112,A0113,
                ->SetQueryBySiJiFenLei(    $requestDataArr['basic_nicid'] )
                // 地区 basic_regionid: 110101,110102,
                ->SetQueryByBasicRegionid(   $requestDataArr['basic_regionid']  )
                ->addSize($size)
                ->setSource($fieldsArr)
                //->addFrom($offset)
                //->addSort("_id","desc")
                //设置默认值 不传任何条件 搜全部
                ;
//            CommonService::getInstance()->log4PHP(
//                json_encode([
//                    __CLASS__.__FUNCTION__ .__LINE__,
//                    '$lastId' => $lastId,
//                    '$totalNums' => $totalNums,
//                    '$fieldsArr' => $fieldsArr,
//                    'generate data  . memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M',
//                    ' costs seconds '=>microtime(true) - $start
//                ])
//            );

            if($lastId>0){
                $companyEsModel->addSearchAfterV1($lastId);
            }
            // 格式化下日期和时间
            $companyEsModel
                ->setDefault()
                ->searchFromEs()
                ->formatEsDate()
                // 格式化下金额
                ->formatEsMoney();

            foreach($companyEsModel->return_data['hits']['hits'] as $dataItem){
                $lastId = $dataItem['_id'];
//                CommonService::getInstance()->log4PHP(
//                    json_encode([
//                        __CLASS__.__FUNCTION__ .__LINE__,
//                        '$lastId' => $lastId
//                    ])
//                );
                $addresAndEmailData = (new XinDongService())->getLastPostalAddressAndEmail($dataItem);
                $dataItem['_source']['last_postal_address'] = $addresAndEmailData['last_postal_address'];
                $dataItem['_source']['last_email'] = $addresAndEmailData['last_email'];

               // $dataItem['_source']['logo'] =  (new XinDongService())->getLogoByEntId($dataItem['_source']['xd_id']);

                // 添加tag
//                $dataItem['_source']['tags'] = array_values(
//                    (new XinDongService())::getAllTagesByData(
//                        $dataItem['_source']
//                    )
//                );

//                CommonService::getInstance()->log4PHP(
//                    json_encode([
//                        __CLASS__.__FUNCTION__ .__LINE__,
//                        '$nums' => $nums
//                    ])
//                );
                $nums ++;

                // 官网
                $webStr = trim($dataItem['_source']['web']);
                if(!$webStr){
                    yield $datas[] = $dataItem['_source'];
                    continue;
                }
                $webArr = explode('&&&', $webStr);
                !empty($webArr) && $dataItem['_source']['web'] = end($webArr);

                yield $datas[] = $dataItem['_source'];
            }

            $totalNums -= $size;
            $offset +=$size;
        }
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'generate data  done . memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M',
                'generate data  done . costs seconds '=>microtime(true) - $start
            ])
        );
    }


    static function  getYieldDataBySiJi($tmpSiji,$fieldsArr = ["ying_shou_gui_mo","si_ji_fen_lei_code"]){
        $startMemory = memory_get_usage();
        $start = microtime(true);

        // while循环执行的次数
        $nums = 1;
        //去取上一次es结果的id
        $lastId = 0;
        //每次从es取多少数据
        $size = 1500;
        $loopNums = 0;
        //最多执行次数
        $maxRunNums =  5000;
        while ($nums <= $maxRunNums ) {
             sleep(0.3);
            $companyEsModel = new \App\ElasticSearch\Model\Company();
            $companyEsModel
                //经营范围
                ->SetQueryBySiJiFenLei($tmpSiji)
                ->addSize($size)
                ->addSort('_id',"asc")
                ->setSource($fieldsArr)
            ;
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    '$lastId' => $lastId
                ])
            );
            if($lastId>0){
                $companyEsModel->addSearchAfterV1($lastId);
            }
            $companyEsModel
                ->searchFromEs() ;
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'total value' => $companyEsModel->return_data['hits']['total']['value']
                ])
            );
            if( $companyEsModel->return_data['hits']['total']['value']<= 0){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ .__LINE__,
                        'generate data  done . memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M',
                        'generate data  done . costs seconds '=>microtime(true) - $start,
                        '$nums' => $nums,
                        '$loopNums'=>$loopNums,
                    ])
                );
                return ;
            }
            $nums ++;
            foreach($companyEsModel->return_data['hits']['hits'] as $dataItem){
                $lastId = $dataItem['_id'];
                $loopNums ++;
                yield $datas[] = [
                    $dataItem['_source']['ying_shou_gui_mo']
                ];
            }
        }
    }
    static function  testYield($tmpSiji,$fieldsArr = ["ying_shou_gui_mo","si_ji_fen_lei_code"]){
        $allSijiFenLeis = self::getYieldDataBySiJi(
            $tmpSiji,$fieldsArr
        );

        foreach ($allSijiFenLeis as $datItem){

        }
    }


    static function  generateFileExcelV2($limit){
        $startMemory = memory_get_usage();
        $allInitDatas =  DownloadSoukeHistory::findBySql(
            "
                WHERE     status =  ".DownloadSoukeHistory::$state_init."       
                AND   touch_time IS NULL 
                LIMIT $limit
            "
        );
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                __CLASS__.__FUNCTION__ .__LINE__,
//                'memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M'
//            ])
//        );

        foreach($allInitDatas as $InitData){
            DownloadSoukeHistory::setTouchTime(
                $InitData['id'],date('Y-m-d H:i:s')
            );

            $filename = '搜客导出_'.date('YmdHis').'.xlsx';
            $config=  [
                'path' => TEMP_FILE_PATH // xlsx文件保存路径
            ];


            $fieldsArr = AdminUserSoukeConfig::getAllowedFieldsArray($InitData['admin_id']);
            array_unshift($fieldsArr, 'xd_id');  //在数组开头插入元素


            $filedCname = ['xd_id'];
            $allFields = AdminUserSoukeConfig::getAllFields();
            foreach ($fieldsArr as $field){
                if($allFields[$field]){
                    $filedCname[] = $allFields[$field];
                }
            }

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

            $fileObject
                ->defaultFormat($colorStyle)
                ->header($filedCname)
                ->defaultFormat($alignStyle)
            ;

            $featureArr = json_decode($InitData['feature'],true);
            // get SouKe Config

            $tmpXlsxDatas = self::getYieldDataForSouKe($featureArr['total_nums'],$featureArr,$fieldsArr);
            foreach ($tmpXlsxDatas as $dataItem){
//                CommonService::getInstance()->log4PHP(
//                    json_encode([
//                        __CLASS__.__FUNCTION__ .__LINE__,
//                        '$dataItem' => $dataItem
//                    ])
//                );
                $tmp = [
                    //'xd_id'=>$dataItem['xd_id'],
                ];
                foreach ($fieldsArr as $field){
                    $tmp[$field] = $dataItem[$field];
                }
                //$tmp['xd_id'] = $dataItem['xd_id'];
//                CommonService::getInstance()->log4PHP(
//                    json_encode([
//                        __CLASS__.__FUNCTION__ .__LINE__,
//                        '$dataItem' => $dataItem,
//                        '$featureArr'=>$featureArr,
//                        '$tmp'=>$tmp,
//                    ])
//                );
                $fileObject ->data([$tmp]);
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
            DownloadSoukeHistory::setFilePath($InitData['id'],'/Static/Temp/',$filename);

            //设置状态
            DownloadSoukeHistory::setStatus(
                $InitData['id'],DownloadSoukeHistory::$state_file_succeed
            );
            DownloadSoukeHistory::setTouchTime(
                $InitData['id'],NULL
            );
        }

        return true;
    }

    static function  generateFileCsvV2($limit){
        $startMemory = memory_get_usage();
        $allInitDatas =  DownloadSoukeHistory::findBySql(
            " WHERE status = ".DownloadSoukeHistory::$state_init.
                " AND touch_time IS NULL 
                LIMIT $limit "
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M'
            ])
        );

        foreach($allInitDatas as $InitData){
            DownloadSoukeHistory::setTouchTime(
                $InitData['id'],date('Y-m-d H:i:s')
            );

            $filename = '搜客导出_'.date('YmdHis').'.csv';
            self::setworkPath( TEMP_FILE_PATH );
            $f = fopen(self::$workPath.$filename, "w");
            fwrite($f,chr(0xEF).chr(0xBB).chr(0xBF));

            $featureArr = json_decode($InitData['feature'],true);
            $fieldsArr = AdminUserSoukeConfig::getAllowedFieldsArray($InitData['admin_id']);
            $fieldsArr[] = 'xd_id';
            $tmpXlsxDatas = self::getYieldDataForSouKe($featureArr['total_nums'],$featureArr,$fieldsArr);
            foreach ($tmpXlsxDatas as $dataItem){
                fputcsv($f, $dataItem);
                $nums++;
            }
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    '$nums' =>$nums,
                    'generate data done . memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M'
                ])
            );

            //更新文件地址
            DownloadSoukeHistory::setFilePath($InitData['id'],'/Static/Temp/',$filename);

            //设置状态
            DownloadSoukeHistory::setStatus(
                $InitData['id'],DownloadSoukeHistory::$state_file_succeed
            );
            DownloadSoukeHistory::setTouchTime(
                $InitData['id'],NULL
            );
        }

        return true;
    }

    // 交付客户：生成细的交付记录
    static function  deliver($limit){
        $allInitDatas =  DeliverHistory::findBySql(
            " WHERE status = ".DeliverHistory::$state_init.
            " AND touch_time IS NULL LIMIT $limit "
        );

        foreach($allInitDatas as $InitData){
            DeliverHistory::setTouchTime(
                $InitData['id'],date('Y-m-d H:i:s')
            );

             //各项筛选条件
            $featureArr = json_decode($InitData['feature'],true);
            $fieldsArr = AdminUserSoukeConfig::getAllowedFieldsArray($InitData['admin_id']);
            $fieldsArr[] = 'xd_id';
            $tmpXlsxDatas = self::getYieldDataForSouKe($featureArr['total_nums'],$featureArr,$fieldsArr);
            $nums = 1;
            foreach ($tmpXlsxDatas as $dataItem){
//                CommonService::getInstance()->log4PHP(
//                    json_encode([
//                        __CLASS__.__FUNCTION__ .__LINE__,
//                        'xd_id' => $dataItem['xd_id'],
//                        'name' => $dataItem['name']
//                    ])
//                );
                //第一列是标题
                if($nums == 1 ){
                    $nums ++;
                    continue;
                }
                DeliverDetailsHistory::addRecordV2(
                    [
                        //用户
                        'admin_id' => $InitData['admin_id'],
                        //交付记录id
                        'deliver_id' => $InitData['id'],
                        //企业id
                        'ent_id' => $dataItem['xd_id'],
                        //企业名称
                        'entName' => $dataItem['name'],
                        //备注
                        'remark' => '',
                        'status' => DeliverDetailsHistory::$state_init,
                    ]
                );
                $nums ++;
            }

            //设置状态
            DeliverHistory::setStatus(
                $InitData['id'],DeliverHistory::$state_succeed
            );
            DeliverHistory::setTouchTime(
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
