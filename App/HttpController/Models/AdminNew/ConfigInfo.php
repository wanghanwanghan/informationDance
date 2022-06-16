<?php

namespace App\HttpController\Models\AdminNew;

use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataQueue;
use App\HttpController\Models\ModelBase;

class ConfigInfo extends ModelBase
{
    protected $tableName = 'config_info';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';


    public static function findByName($name){
        $res =  ConfigInfo::create()
            ->where('name',$name)
            ->get();
        return $res;
    }

    public static function setValue($id,$value){
        $info = ConfigInfo::findById($id);

        return $info->update([
            'value' => $value,
        ]);
    }

    public static function checkCrontabIfCanRun($crontabName){
        $info = ConfigInfo::findByName('crontab');
        $config = $info->getAttr("value");
        $configArr = json_decode($config,true);

        return  $configArr[$crontabName]['is_running']?false:true;
    }

    public static function setIsRunning($crontabName){
        $info = ConfigInfo::findByName('crontab');
        $config = $info->getAttr("value");
        $configArr = json_decode($config,true);

        if(empty($configArr[$crontabName])){
            $configArr[$crontabName] = [
                'start_time' => 0,
                'end_time' => 0,
                'is_running' => 0,
            ];
        }

        $configArr[$crontabName]['start_time'] = date('Y-m-d H:i:s');
        $configArr[$crontabName]['is_running'] = 1;
        return $info->update([
            'value' => json_encode($configArr),
        ]);
    }

    public static function setIsDone($crontabName){
        $info = ConfigInfo::findByName('crontab');
        $config = $info->getAttr("value");
        $configArr = json_decode($config,true);

        if(empty($configArr[$crontabName])){
            $configArr[$crontabName] = [
                'start_time' => 0,
                'end_time' => 0,
                'is_running' => 0,
            ];
        }

        $configArr[$crontabName]['is_running'] = 0;
        return $info->update([
            'value' => json_encode($configArr),
        ]);
    }
}
