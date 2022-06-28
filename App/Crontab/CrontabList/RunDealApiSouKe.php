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
use App\HttpController\Models\AdminV2\DeliverDetailsHistory;
use App\HttpController\Models\AdminV2\DeliverHistory;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\AdminV2\FinanceLog;
use App\HttpController\Models\AdminV2\NewFinanceData;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use App\HttpController\Service\Sms\SmsService;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\Mysqli\QueryBuilder;
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
            ->SetQueryByWeb($requestDataArr['searchOption'])
            //必须存在APP
            ->SetQueryByApp($requestDataArr['searchOption'])
            //必须是物流企业
            ->SetQueryByWuLiuQiYe($requestDataArr['searchOption'])
            // 企业类型 :传过来的是10 20 转换成对应文案 然后再去搜索
            ->SetQueryByCompanyOrgType($requestDataArr['searchOption'])
            // 成立年限  ：传过来的是 10  20 30 转换成最小值最大值范围后 再去搜索
            ->SetQueryByEstiblishTime($requestDataArr['searchOption'])
            // 营业状态   传过来的是 10  20  转换成文案后 去匹配
            ->SetQueryByRegStatus($requestDataArr['searchOption'])
            // 注册资本 传过来的是 10 20 转换成最大最小范围后 再去搜索
            ->SetQueryByRegCaptial($requestDataArr['searchOption'])
            // 团队人数 传过来的是 10 20 转换成最大最小范围后 再去搜索
            ->SetQueryByTuanDuiRenShu($requestDataArr['searchOption'])
            // 营收规模  传过来的是 10 20 转换成对应文案后再去匹配
            ->SetQueryByYingShouGuiMo($requestDataArr['searchOption'])
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

        foreach($companyEsModel->return_data['hits']['hits'] as &$dataItem){
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
        self::generateFile(3);

        //确认交付
        self::deliver(3);
        //设置为已执行完毕
        ConfigInfo::setIsDone(__CLASS__);

        return true ;   
    }

    //生成下载文件
    static function  generateFile($limit){

        $allInitDatas =  DownloadSoukeHistory::findAllByConditionV2(
             [
                 'status' => DownloadSoukeHistory::$state_init
             ],
            1
        );

        foreach($allInitDatas as $InitData){
            DownloadSoukeHistory::setTouchTime(
                $InitData['id'],date('Y-m-d H:i:s')
            );

            $xlsxData = [];
            $featureArr = json_decode($InitData['feature'],true);
            $maxPage = floor($featureArr['total_nums']/1000);
            if($maxPage  > 1 ){
                for ($i=1 ; $i<= $maxPage ;$i++){
                    $page = $i;
                    $size = 1000;
                    $offset = ($page-1)*$size;
                    // 数据
                    $tmpXlsxDatas = self::getYieldData(1000,$offset,$featureArr);
                    foreach ($tmpXlsxDatas as $dataItem){
                        $xlsxData[] = $dataItem;
                    }
                }
            }
            // 数据 1001 1 1000
            $left = $featureArr['total_nums'] - ($maxPage)*1000 ;
            $tmpXlsxDatas = self::getYieldData($left,0,$featureArr);
            foreach ($tmpXlsxDatas as $dataItem){
                $xlsxData[] = $dataItem;
            }

            $filename = date('YmdHis').'.xlsx';
            $header = [];
            NewFinanceData::parseDataToXls(
                [
                    'path' => TEMP_FILE_PATH // xlsx文件保存路径
                ],$filename,$header,$xlsxData,'sheet1'
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
        $allInitDatas =  DeliverHistory::findAllByCondition(
            [
                'status' => DeliverHistory::$state_init
            ]
        );

        foreach($allInitDatas as $InitData){
            DeliverHistory::setTouchTime(
                $InitData['id'],date('Y-m-d H:i:s')
            );

            $xlsxData = [];
            $featureArr = json_decode($InitData['feature'],true);
            for ($i=1 ; $i<= ceil($featureArr['total_nums']/1000);$i++){
                $page = $i;
                $size = 1000;
                $offset = ($page-1)*$size;
                // 数据
                $tmpXlsxDatas = self::getYieldData(1000,$offset,$featureArr);
                foreach ($tmpXlsxDatas as $dataItem){
                    $xlsxData[] = $dataItem;
                }
            }

            foreach ($xlsxData as $date){
                DeliverDetailsHistory::addRecordV2(
                    [
                        'admin_id' => $InitData['admin_id'],
                        'deliver_id' => $InitData['id'],
                        'ent_id' => $date['xd_id'],
                        'entName' => $date['name'],
                        'remark' => '',
                        //'total_nums' => $requestData['total_nums'],
                        'status' => DeliverDetailsHistory::$state_init,
                    ]
                );
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
