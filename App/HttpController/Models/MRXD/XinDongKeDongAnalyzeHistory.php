<?php

namespace App\HttpController\Models\MRXD;

use App\ElasticSearch\Model\Company;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Sms\AliSms;
use App\HttpController\Service\XinDong\XinDongService;
use EasySwoole\RedisPool\Redis;
use Vtiful\Kernel\Format;

// use App\HttpController\Models\AdminRole;

class XinDongKeDongAnalyzeHistory extends ModelBase
{
    protected $tableName = 'xin_dong_ke_dong_analyze_history';

    static  $state_del = 1;
    static  $state_del_cname =  '已删除';

    static  $state_ok = 0;
    static  $state_ok_cname =  '正常';

    static $status_init = 0;
    static $status_init_cname =  '初始';

    static function getDelStateMap(){
        return [
            self::$state_del =>self::$state_del_cname,
            self::$state_ok =>self::$state_ok_cname,
        ];
    }

    static  function  addRecordV2($info){
        $oldRes = self::findByEntName($info['user_id'],$info['ent_name']);
        //如果被删除了 重新找回来
        $oldRes2 = self::findByEntNameV3($info['user_id'],$info['ent_name']);
        if($oldRes2){
            return self::updateById(
                $oldRes2->getAttr('id'),
                [
                    'is_del' => self::$state_ok_cname
                ]
            );
        }
        if( $oldRes ){
            return  $oldRes->getAttr('id');
        }

        return XinDongKeDongAnalyzeHistory::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  XinDongKeDongAnalyzeHistory::create()->data([
                'user_id' => $requestData['user_id'],
                'companyid' => $requestData['companyid'],
                'company_nums' => $requestData['company_nums'],
                'company_ids' => $requestData['company_ids'],
                'feature_json' => $requestData['feature_json'],
                'status' => intval($requestData['status']),
                'name' => $requestData['name']?:'',
                'remark' => $requestData['remark']?:'',
               'created_at' => time(),
               'updated_at' => time(),
           ])->save();

        } catch (\Throwable $e) {
            return CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'failed',
                    '$requestData' => $requestData,
                    'err_msg' => $e->getMessage(),
                ])
            );
        }
        return $res;
    }

    public static function findAllByCondition($whereArr){
        $res =  XinDongKeDongAnalyzeHistory::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = XinDongKeDongAnalyzeHistory::findById($id);

        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }

    //软删除
    public static function delRecord($id){
        $info = XinDongKeDongAnalyzeHistory::findById($id);
        return $info->update([
            'is_del' => self::$state_del,
        ]);
    }


    public static function updateById(
        $id,$data
    ){
        $info = self::findById($id);
        return $info->update($data);
    }

    public static function findByConditionWithCountInfo($whereArr,$page){
        $model = XinDongKeDongAnalyzeHistory::create()
                ->where($whereArr)
                ->page($page)
                ->order('id', 'DESC')
                ->withTotalCount();

        $res = $model->all();

        $total = $model->lastQueryResult()->getTotalCount();
        return [
            'data' => $res,
            'total' =>$total,
        ];
    }

    public static function findByConditionV2($whereArr,$page, $size){
        $model = XinDongKeDongAnalyzeHistory::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        $model->page($page,$size)
            ->order('id', 'DESC')
            ->withTotalCount();

        $res = $model->all();

        $total = $model->lastQueryResult()->getTotalCount();
        return [
            'data' => $res,
            'total' =>$total,
        ];
    }

    public static function findById($id){
        $res =  XinDongKeDongAnalyzeHistory::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findAllByUserId($userId){
        $res =  XinDongKeDongAnalyzeHistory::create()
            ->where('user_id',$userId)
            ->all();
        return $res;
    }

    public static function findByEntName($user_id,$ent_name){
        $res =  XinDongKeDongAnalyzeHistory::create()
            ->where('user_id',$user_id)
            ->where('ent_name',$ent_name)
            ->get();
        return $res;
    }

    public static function findByUser($user_id){
        $res =  XinDongKeDongAnalyzeHistory::create()
            ->where('user_id',$user_id)
            ->where('is_del',0)
            ->get();
        return $res;
    }


    public static function findByEntNameV2($user_id,$ent_name){
        $res =  XinDongKeDongAnalyzeHistory::create()
            ->where('user_id',$user_id)
            ->where('ent_name',$ent_name)
            ->where('is_del',0)
            ->get();
        return $res;
    }

    public static function findByEntNameV3($user_id,$ent_name){
        $res =  XinDongKeDongAnalyzeHistory::create()
            ->where('user_id',$user_id)
            ->where('ent_name',$ent_name)
            ->where('is_del',1)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = XinDongKeDongAnalyzeHistory::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `xin_dong_ke_dong_analyze_history` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }






}
