<?php

namespace App\HttpController\Models\AdminV2;
use App\HttpController\Service\CreateConf;


use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\LongXin\FinanceRange;
use App\HttpController\Service\LongXin\LongXinService;

class AdminUserFinanceConfig extends ModelBase
{
    protected $tableName = 'admin_user_finance_config';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';


    // 1字典，2区间，3原始
    static $type_zidian = 1;
    static $type_zidian_cname = '字段';

    static $type_qvjian = 2;
    static $type_qvjian_cname =  '区间';

    static $type_yuanshi = 3;
    static $type_yuanshi_cname =  '原始';

    static $state_ok = 1;
    static $state_del = 5;

    public $exportYearsOk = true;

    function setExportYearsOk(){
        $this->exportYearsOk = true;
        return $this;
    }
    function setExportYearsNotOk(){
        $this->exportYearsOk = false;
        return $this;
    }

    static function  formatchYuanZhi($dataItem){
        $newData =[];
        foreach ($dataItem as $key=>$value){
            if(
                in_array($key,['year','entName'])
            ){
                $newData[$key] = $value;
            }
            else{
                $newData[$key] = number_format($value,2);
            }
        }
        return $newData;
    }

    static function  formatchZiDian($dataItem){
        $newData =[];
        $indexTable = [
            '0' => 'O',
            '1' => 'C',
            '2' => 'E',
            '3' => 'I',
            '4' => 'G',
            '5' => 'A',
            '6' => 'H',
            '7' => 'F',
            '8' => 'D',
            '9' => 'B',
            '.' => '*',
            '-' => 'J',
        ];
        foreach ($dataItem as $field => $num) {
            if(
                in_array($field,['year','entName'])
            ){
                $newData[$field] = $num;
            }
            else{
                $newData[$field] = strtr($num, $indexTable);
            }
        }

        return $newData;
    }

    static function  formatchQvJian($dataItem,$type =1 ){
        $range = FinanceRange::getInstance()->getRange('range');
        $ratio = FinanceRange::getInstance()->getRange('rangeRatio');
        $newData =[];
        foreach ($dataItem as $field => $val) {
            if(
                in_array($field,['year','entName'])
            ){
                $newData[$field] = $val;
            }
            else{
                if (in_array($field, $range[0], true) && is_numeric($val)) {
                    !is_numeric($val) ?: $val = $val * 10000;
                    $tmp = (new LongXinService())->binaryFind($val, 0, count($range[1]) - 1, $range[1]);
                    if($type ==1 ){
                        $newData[$field] = $tmp['name'];
                    }
                    if($type ==2){
                        $newData[$field] = $tmp;
                    }

                } elseif (in_array($field, $ratio[0], true) && is_numeric($val)) {
                    $tmp =   (new LongXinService())->binaryFind($val, 0, count($ratio[1]) - 1, $ratio[1]);
                    if($type ==1 ){
                        $newData[$field] = $tmp['name'];
                    }
                    if($type ==2){
                        $newData[$field] = $tmp;
                    }
                } else {
                    $newData[$field] =   $val;
                }
            }
        }
        return $newData;
    }


    static function getConfigByUserId($userId){
        $res =  AdminUserFinanceConfig::create()->where([
            'user_id' => $userId,     
            'status' => 1,
        ])->get();

        return $res;
    }

    static function getDailyMaxNums($userId){
        $res =  self::getConfigByUserId($userId);

        CommonService::getInstance()->log4PHP(
            json_encode([
                'AdminUserFinanceConfig  getDailyMaxNums  start',
                '$userId'=>$userId,
                'max_daily_nums'=>$res->getAttr('max_daily_nums'),
            ])
        );

        return $res->getAttr('max_daily_nums');
    }

    static function getConfigDataByUserId($userId){
        $res =  AdminUserFinanceConfig::create()->where([
            'user_id' => $userId,
            'status' => 1,
        ])->get();
        return $res ? $res->toArray() : [];
    }


    public static function findById($id){
        $res =  AdminUserFinanceConfig::create()
            ->where('id',$id)
            ->get();
        return $res;
    }

    public static function findAllByCondition($whereArr){
        $res =  AdminUserFinanceConfig::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setStatus($id,$status){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'AdminUserFinanceConfig setStatus',
                 '$id'=>$id,
                '$status' =>$status,
            ])
        );
        $info = self::findById($id);

        return $info->update([
            'status' => $status,
        ]);
    }

    static function addRecord($requestData){
        return AdminUserFinanceConfig::create()->data([
            'user_id' => $requestData['user_id'], 
            'annually_price' => $requestData['annually_price'],
             'needs_confirm' => $requestData['needs_confirm'],
            'annually_years' => $requestData['annually_years'],
            'single_year_charge_as_annual' => $requestData['single_year_charge_as_annual'],
            'normal_years_price_json' => $requestData['normal_years_price_json'],
            'allowed_total_years_num' => $requestData['allowed_total_years_num'],
            'cache' => $requestData['cache'],  
            'type' => $requestData['type'],  
            'allowed_fields' => $requestData['allowed_fields'],  
            'status' => $requestData['status'],
            'max_daily_nums' => $requestData['max_daily_nums'],
            'sms_notice_value' => $requestData['sms_notice_value'],
        ])->save();  
    }


    static function addRecordV2($requestData){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'finance config    addRecordV2',
                'params $requestData '=> $requestData,
            ])
        );

        $res = self::getConfigByUserId($requestData['user_id']);
        if($res){
            return  $res->getAttr('id');
        }
        return  self::addRecord($requestData);
    }

    public static function checkExportYearsNums($userid,$yearsNums){

        $info = self::getConfigByUserId($userid);
        $data = $info->toArray();
        if($data['allowed_total_years_num'] < $yearsNums){
            return  CommonService::getInstance()->log4PHP(
                json_encode([
                    'checkExportYearsNums   false',
                    'params $userid '=> $userid,
                    'params $yearsNums '=> $yearsNums,
                    'allowed_total_years_num' =>$data['allowed_total_years_num'],
                ])
            );
        }
        CommonService::getInstance()->log4PHP(
            json_encode([
                'checkExportYearsNums   true',
                'params $userid '=> $userid,
                'params $yearsNums '=> $yearsNums,
                'allowed_total_years_num' =>$data['allowed_total_years_num'],
            ])
        );
        return true ;
    }

    public static function checkIfNeedsConfirm($userid){

        $info = self::getConfigByUserId($userid);
        $data = $info->toArray();
        return $data['needs_confirm'] ;
    }
}
