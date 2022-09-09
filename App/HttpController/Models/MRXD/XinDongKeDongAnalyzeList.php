<?php

namespace App\HttpController\Models\MRXD;

use App\ElasticSearch\Model\Company;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Sms\AliSms;
use EasySwoole\RedisPool\Redis;

// use App\HttpController\Models\AdminRole;

class XinDongKeDongAnalyzeList extends ModelBase
{

    protected $tableName = 'xin_dong_ke_dong_analyze_list';

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
        $oldRes2 = self::findByEntNameV2($info['user_id'],$info['ent_name']);
        if($oldRes2){
            self::updateById(
                $oldRes2->getAttr('id'),
                [
                    'is_del' => self::$state_ok_cname
                ]
            );
        }
        if( $oldRes ){
            return  $oldRes->getAttr('id');
        }

        return XinDongKeDongAnalyzeList::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  XinDongKeDongAnalyzeList::create()->data([
                'user_id' => intval($requestData['user_id']),
                'is_del' => intval($requestData['is_del']),
                'status' => intval($requestData['status']),
                'name' => $requestData['name']?:'',
                'ent_name' => $requestData['ent_name']?:'',
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
        $res =  XinDongKeDongAnalyzeList::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = XinDongKeDongAnalyzeList::findById($id);

        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }

    public static function updateById(
        $id,$data
    ){
        $info = self::findById($id);
        return $info->update($data);
    }

    public static function findByConditionWithCountInfo($whereArr,$page){
        $model = XinDongKeDongAnalyzeList::create()
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

    public static function findByConditionV2($whereArr,$page){
        $model = XinDongKeDongAnalyzeList::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        $model->page($page)
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
        $res =  XinDongKeDongAnalyzeList::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findAllByUserId($userId){
        $res =  XinDongKeDongAnalyzeList::create()
            ->where('user_id',$userId)
            ->all();
        return $res;
    }

    public static function findByEntName($user_id,$ent_name){
        $res =  XinDongKeDongAnalyzeList::create()
            ->where('user_id',$user_id)
            ->where('ent_name',$ent_name)
            ->get();
        return $res;
    }

    public static function findByUser($user_id){
        $res =  XinDongKeDongAnalyzeList::create()
            ->where('user_id',$user_id)
            ->where('is_del',0)
            ->get();
        return $res;
    }


    public static function findByEntNameV2($user_id,$ent_name){
        $res =  XinDongKeDongAnalyzeList::create()
            ->where('user_id',$user_id)
            ->where('ent_name',$ent_name)
            ->where('is_del',0)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = XinDongKeDongAnalyzeList::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `xin_dong_ke_dong_analyze_list` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }

    //注意  这里是异步调用的  是mysql 队列调用的
    static function addRecordByFile($paramsArr){
        $records = DataModelExample::getYieldData(
            $paramsArr['file'],
            TEMP_FILE_PATH
        );
        $return = [];
        foreach ($records as $record){
            $res = self::addRecordV2(
                [
                    'user_id' => $paramsArr['user_id'],
                    'is_del' => XinDongKeDongAnalyzeList::$state_ok,
                    'status' => XinDongKeDongAnalyzeList::$status_init,
                    'name' => $paramsArr['name']?:'',
                    'ent_name' => $paramsArr['ent_name']?:'',
                    'remark' => $paramsArr['remark']?:'',
                ]
            );
            $return[] = $res;
        }

        return $return;
    }
    static function calYearsNums($date){
        $yearsNums =  date('Y') - date('Y',strtotime($date));
        if($yearsNums<=2){
            return '0-2';
        }
        if(
            $yearsNums >= 2 &&
            $yearsNums <= 5
        ){
            return '2-5';
        }
        if(
            $yearsNums >= 5 &&
            $yearsNums <= 10
        ){
            return '5-10';
        }
        if(
            $yearsNums >= 10 &&
            $yearsNums <= 15
        ){
            return '10-15';
        }
        if(
            $yearsNums >= 15 &&
            $yearsNums <= 20
        ){
            return '10-15';
        }
        if(
            $yearsNums >= 20
        ){
            return '20年以上';
        }
    }
    //提取用户传的目标客户的特征
    static function extractFeature($userId,$returnRaw =false){
        //找到所有的目标客户群体
        $fields = [
            '营收规模'=>'ying_shou_gui_mo',
            '国标行业'=>'NIC_ID',
            '所在行政区划'=>'DOMDISTRICT',
        ];
        $fields2 = [
            'OPFROM'=>[
                'des'=>'营业期限开始日期',
                'filed'=>'OPFROM',
                'static_func'=>'calYearsNums',
            ],
        ];
        $res = [];
        $lists = XinDongKeDongAnalyzeList::findAllByUserId($userId);
        foreach ($lists as $list){
            $tmpEsData = \App\ElasticSearch\Model\Company::getNamesByText(
                1,
                1,
                $list['ent_name'],
                true
            );
            foreach ($tmpEsData['hits']['hits'] as $esData){
                //直接比较的字段
                foreach ($fields as $field){
                    if(empty($esData['_source'][$field])){
                        continue;
                    }
                    $res[$field][$esData['_source'][$field]] += 1 ;
                }
                //需要计算的字段
                foreach ($fields2 as $field){
                    if(empty($esData['_source'][$field]['filed'])){
                        continue;
                    }
                    if($returnRaw){
                        $newRes = $esData['_source'][$field['filed']];
                    }
                    else{
                        $newRes = self::$field['static_func']($esData['_source'][$field['filed']]);
                    }
                    $res[$field][$newRes] += 1 ;
                }
            }
        }

        $returnData = [];
        foreach ($res as $field=>$fieldValue){
            asort($fieldValue) ;
            $tmp = array_keys($fieldValue);
            $returnData[$field] =  end($tmp);
        }

        //开始分析
        return $returnData;
    }

}