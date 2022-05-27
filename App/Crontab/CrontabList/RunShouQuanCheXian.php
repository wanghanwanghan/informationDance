<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\CarInsuranceInfo;
use App\HttpController\Models\Api\CompanyCarInsuranceStatusInfo;
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

    function setCompanyCarInsuranceInfoStatusById($id,$status){
        return CompanyCarInsuranceStatusInfo::create()
            ->where(['id' => $id])
            ->update([
                'status' => $status, 
            ]); 
    }
 

    function CompanyHasAuthAll($entId){
        $todoCars = CarInsuranceInfo::create()->where(
            [
                'status' => 0, 
                'entId' => $entId, 
            ]
        )->all();
        $doneCars = CarInsuranceInfo::create()->where(
            [
                'status' => 5, 
                'entId' => $entId, 
            ]
        )->get();

        if(
            !$todoCars &&
            $doneCars
        ){
            return true;
        }

        return false; 
    }
    function run(int $taskId, int $workerIndex): bool
    {
        // 是否企业全部授权完成了
       $this->setCompanyAuthComplete();

       //授权企业
    //    $this->authCompany(); 
       $this->authCompanyV2(); 
        return true ;  
    }

     //授权企业 单个授权
    function authCompany() 
    { 
        
        // $sql = " select id from  `$tableName`  order by id  desc limit 1 ";
        // $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));

        // 找到没有全部授权完的一个企业
        $companysNeedDone = CompanyCarInsuranceStatusInfo::create()
            ->where(
                        [
                            'status' => CompanyCarInsuranceStatusInfo::$status_init,  
                        ]
            )
            ->limit(1)
            ->all(); 
        
        foreach($companysNeedDone as $companyItem){ 
            //找到该企业所有需要处理的车辆
            $todoCars = CarInsuranceInfo::create()->where(
                [
                    'status' => 0, 
                    'entId' => $companyItem['entId'], 
                ]
            )->all(); 
            if(empty($todoCars)){ 
               continue;
            }

            // 拿着车辆一个个授权
            foreach($todoCars as $vinData){
                if($vinData['entId'] <=0 ){
                    $this->setCarInsuranceInfoStatusById(
                        $vinData['id'],
                        6,
                        '少entId'
                    );
                }
                
                // 找到企业信息
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
                // 去授权
                $res = (new DianZiQianService())->getCarAuthFileV2($postData); 
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            'RunShouQuanCheXian',
                            'postData' => $postData, 
                            'res' => $res, 
                        ]
                    )
                ); 

                // 授权失败
                if($res['code']!= 200 ){
                    $this->setCarInsuranceInfoStatusById(
                        $vinData['id'],
                        6,
                        json_encode($$res)
                    );
                }

                // 授权成功
                $this->setCarInsuranceInfoStatusById(
                    $vinData['id'],
                    5,
                    json_encode($res)
                );

                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            '授权成功', 
                            'res' => $res, 
                        ]
                    )
                ); 
                // 保存授权结果
                $DianZiQianAuthId = DianZiQianAuth::create()
                ->data($res['result'])
                ->save(); 
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            '保存授权结果', 
                            'DianZiQianAuthId' => $DianZiQianAuthId, 
                        ]
                    )
                ); 
                // 将授权结果和车辆信息关联
                CarInsuranceInfo::create()
                    ->where(['id' => $vinData['id']])
                    ->update([
                        'auth_res_id' => $DianZiQianAuthId, 
                    ]); 
            }   
        } 

        return true ;  
    }

    //分组授权
    function authCompanyV2() 
    { 
        
        // $sql = " select id from  `$tableName`  order by id  desc limit 1 ";
        // $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));

        // 找到没有全部授权完的一个企业
        $companysNeedDone = CompanyCarInsuranceStatusInfo::create()
            ->where(
                        [
                            'status' => CompanyCarInsuranceStatusInfo::$status_init,  
                        ]
            )
            ->limit(1)
            ->all(); 
        
        foreach($companysNeedDone as $companyItem){ 
            //找到该企业所有需要处理的车辆
            $todoCars = CarInsuranceInfo::create()->where(
                [
                    'status' => 0, 
                    'entId' => $companyItem['entId'], 
                ]
            )->all(); 
            if(empty($todoCars)){ 
               continue;
            }

            // 找到企业信息
            $entModel = Company::create()->where(
                [
                    'id' => $companyItem['entId'] 
                ])->get();

            // 拿着车辆一组一组的授权   
            $todoCarsDatas = array_chunk($todoCars,5);

            foreach($todoCarsDatas as $todoCarsData){
                $vinArr = array_column($todoCarsData,'vin');
                $vinStr = implode(',',$vinArr);

                $postData = [
                    'entName' => $entModel->getAttr('name'),
                    'socialCredit' => $entModel->getAttr('property1'),
                    'legalPerson' => $todoCarsData[0]['legalPerson'],
                    'idCard' =>$todoCarsData[0]['idCard'],
                    'phone' => '',
                    'city' => '',
                    'vin' => $vinStr,
                ];

                // 去授权
                $res = (new DianZiQianService())->getCarAuthFileV2($postData); 
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            'RunShouQuanCheXian',
                            'postData' => $postData, 
                            'res' => $res, 
                        ]
                    )
                ); 

                // 授权失败
                if($res['code']!= 200 ){
                    foreach($todoCarsData as $todoCarsDataItem){
                        $this->setCarInsuranceInfoStatusById(
                            $todoCarsDataItem['id'],
                            6,
                            json_encode($$res)
                        );
                    } 
                }

                // 授权成功
                foreach($todoCarsData as $todoCarsDataItem){
                    $this->setCarInsuranceInfoStatusById(
                        $todoCarsDataItem['id'],
                        5,
                        json_encode($$res)
                    );
                }  

                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            '授权成功', 
                            'res' => $res, 
                        ]
                    )
                ); 

                // 保存授权结果
                $DianZiQianAuthId = DianZiQianAuth::create()
                ->data($res['result'])
                ->save(); 
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            '保存授权结果', 
                            'DianZiQianAuthId' => $DianZiQianAuthId, 
                        ]
                    )
                ); 

                // 将授权结果和车辆信息关联
                foreach($todoCarsData as $todoCarsDataItem){
                    CarInsuranceInfo::create()
                    ->where(['id' =>  $todoCarsDataItem['id']])
                    ->update([
                        'auth_res_id' => $DianZiQianAuthId, 
                    ]); 
                }   

            } 
        } 

        return true ;  
    }
    

    function setCompanyAuthComplete() 
    {
       $companysNeedDone = CompanyCarInsuranceStatusInfo::create()->where(
        [
            'status' => CompanyCarInsuranceStatusInfo::$status_init,  
        ])->limit(5)->all(); 
        
        foreach($companysNeedDone as $companyItem){
            // 是否该企业全部授权完成了
            if(
                $this->CompanyHasAuthAll($companyItem['entId'])
            ){
                $this->setCompanyCarInsuranceInfoStatusById($companyItem['id'],10);
            }  
        } 

        return true ;  
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString());
    }

}
