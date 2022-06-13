<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\DbManager;
use wanghanwanghan\someUtils\control;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Models\Api\CompanyName;
use App\HttpController\Service\CreateConf;


class RunFillCompanyName extends AbstractCronTask
{
    public $crontabBase;
    public $filePath = ROOT_PATH . '/Static/Temp/';
    public $workPath;
    public $backPath;
    public $all_right_ent_txt_file_name;
    public $have_null_ent_txt_file_name;
    public $data_desc_txt_file_name;

    function strtr_func($str): string
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
        return '* * * * *';
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
 

    function run(int $taskId, int $workerIndex): bool
    {
        // 同步配置 
        $sql = " SELECT * FROM config_info  WHERE `name` =  'sync_company_name' LIMIT  1  "; 
        $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));  
       
        // 配置
        $configStr = end($list)['value']; 
        $configArr = json_decode($configStr,true);
        if(empty($configArr)){
            $configArr['debug'] && CommonService::getInstance()->log4PHP('empty configArr ');
            return true;
        }
        // 关闭同步
        if($configArr['colse_sync']){
            $configArr['debug'] && CommonService::getInstance()->log4PHP('colse_sync');
            return true;
        }

        $tableName = $configArr['table_name'];
        // for($i=1; $i <= $configArr['sync_size1']; $i++){ 
        $size = $configArr['sync_size2'] ;

        $sql = " select id from  `$tableName`  order by id  desc limit 1 ";
        $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        $minId = 0;

        $configArr['debug'] &&   CommonService::getInstance()->log4PHP('RunFillCompanyName'.
        json_encode(
            [
                
                'list' => $list, 
                'sql' => $sql,  
            ]
        ) ); 
        if(!empty($list)){
            $minId = intval($list[0]['id']); 
        } 

        $from = $minId +1 ;  
        if($from >= $configArr['table_max_id']){
            $configArr['debug'] &&   CommonService::getInstance()->log4PHP(' reach limit'); 
            return true ;
        }  

        $this->addById($from,$size,$configArr,$tableName);
        
        $from = $from + $size +1 ;
        $this->addById($from,$size,$configArr,$tableName);

        $from = $from + $size +1 ;
        $this->addById($from,$size,$configArr,$tableName);

        $from = $from + $size +1 ;
        $this->addById($from,$size,$configArr,$tableName);

        $from = $from + $size +1 ;
        $this->addById($from,$size,$configArr,$tableName);

        $from = $from + $size +1 ;
        $this->addById($from,$size,$configArr,$tableName);

        $from = $from + $size +1 ;
        $this->addById($from,$size,$configArr,$tableName);

        $from = $from + $size +1 ;
        $this->addById($from,$size,$configArr,$tableName);

        $from = $from + $size +1 ;
        $this->addById($from,$size,$configArr,$tableName);

        $from = $from + $size +1 ;
        $this->addById($from,$size,$configArr,$tableName);

        $from = $from + $size +1 ;
        $this->addById($from,$size,$configArr,$tableName);

        $from = $from + $size +1 ;
        $this->addById($from,$size,$configArr,$tableName); 

        $from = $from + $size +1 ;
        $this->addById($from,$size,$configArr,$tableName);

        $from = $from + $size +1 ;
        $this->addById($from,$size,$configArr,$tableName);

        $from = $from + $size +1 ;
        $this->addById($from,$size,$configArr,$tableName);

        $from = $from + $size +1 ;
        $this->addById($from,$size,$configArr,$tableName);

        $from = $from + $size +1 ;
        $this->addById($from,$size,$configArr,$tableName);

        $from = $from + $size +1 ;
        $this->addById($from,$size,$configArr,$tableName);

        $from = $from + $size +1 ;
        $this->addById($from,$size,$configArr,$tableName);

        $from = $from + $size +1 ;
        $this->addById($from,$size,$configArr,$tableName);

        $from = $from + $size +1 ;
        $this->addById($from,$size,$configArr,$tableName);

        $from = $from + $size +1 ;
        $this->addById($from,$size,$configArr,$tableName);

        
        return true ;  
    }

    function addById($from,$size,$configArr,$tableName){
        $companySql = " select id,`name` from  `company` where id >= ".$from.
                                                    " AND id <= ".($from+ $size);
        $Companys = sqlRaw($companySql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_prism1'));
        $configArr['debug'] &&  CommonService::getInstance()->log4PHP( $companySql); 
        if(empty($Companys)){
            return true;
        }  
        
        $str = ""; 
        foreach($Companys as  $CompanyItem){ 
            $str .= "(".$CompanyItem['id'].", '".addslashes($CompanyItem['name'])."'),";
        }
        $str = substr($str, 0, -1);

        $newsql = "INSERT   INTO `$tableName` (`id`, `name`) VALUES $str ";
        $configArr['debug'] &&  CommonService::getInstance()->log4PHP($newsql); 
        // sqlRaw($newsql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));

        $queryBuilder = new QueryBuilder();
        $queryBuilder->raw($newsql);
        $res = DbManager::getInstance()
            ->query(
                $queryBuilder, 
                true, 
                CreateConf::getInstance()->getConf('env.mysqlDatabase')
            );
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString());
    }

}
