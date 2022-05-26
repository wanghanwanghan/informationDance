<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\CarInsuranceInfo;
use App\HttpController\Models\Api\DianZiQianAuth;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\Mysqli\QueryBuilder;
use wanghanwanghan\someUtils\control;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Service\DianZiqian\DianZiQianService;
use App\HttpController\Service\LongXin\LongXinService;


class RunShouQuanCheXian extends AbstractCronTask
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

    function setCarInsuranceInfoStatusById($id,$status,$msg){
        return CarInsuranceInfo::create()
            ->where(['id' => $id])
            ->update([
                'status' => $status,
                'msg' => $msg,
            ]); 
    }
     
    function run(int $taskId, int $workerIndex): bool
    {
         
       $vinDatas = CarInsuranceInfo::create()->where(
        [
            'status' => 0, 
            'id' => 5, 
        ])->limit(1)->all();
        
        if(empty($vinDatas)){
            return  true;
        }   

        foreach($vinDatas as $vinData){
            if($vinData['entId']<=0){
                $this->setCarInsuranceInfoStatusById(
                    $vinData['id'],
                    6,
                    '少entId'
                );
            }

            $entModel = Company::create()->where(
                [
                    'id' => $vinData['entId'] 
                ])->get();

            $postData = [
                'entName' => $entModel->getAttr('name'),
                'socialCredit' => $entModel->getAttr('property1'),
                'legalPerson' => $vinData['legalPerson'],
                'idCard' =>$vinData['idCard'],
                'phone' => '',
                'city' => '',
                'vin' => $vinData['vin'],
            ];
            
            $res = (new DianZiQianService())->getCarAuthFile($postData);

            CommonService::getInstance()->log4PHP(
                json_encode(
                    [
                        'RunShouQuanCheXian',
                        'postData' => $postData, 
                        'res' => $res, 
                    ]
                )
            );

            $this->setCarInsuranceInfoStatusById(
                $vinData['id'],
                5,
                json_encode($res)
            );
        } 

        return true ;  
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString());
    }

}
