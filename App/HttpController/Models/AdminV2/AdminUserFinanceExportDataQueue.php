<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class AdminUserFinanceExportDataQueue extends ModelBase
{

    protected $tableName = 'admin_user_finance_export_data_queue';

    static  $state_init = 1;
    static  $state_init_cname =  '内容生成中';

    static  $state_needs_confirm = 10;
    static  $state_needs_confirm_cname =  '用户确认数据中';

    static  $state_confirmed = 20;
    static  $state_confirmed_cname =  '文件生成中';

    static  $state_succeed = 30;
    static  $state_succeed_cname =  '文件生成成功';

    public static function getStatusMap(){

        return [
            self::$state_init => self::$state_init_cname,
            self::$state_needs_confirm => self::$state_needs_confirm_cname,
            self::$state_confirmed => self::$state_confirmed_cname,
            self::$state_succeed => self::$state_succeed_cname,
        ];

    }

    public static function charge($queueId){
        $queueData = self::findById($queueId)->toArray();
        $uploadRes = AdminUserFinanceUploadRecord::findById($queueData['upload_record_id'])->toArray();
        $financeDatas = AdminUserFinanceUploadRecord::getAllFinanceDataByUploadRecordIdV2(
            $uploadRes['user_id'],$uploadRes['id']
        );
        $finance_config = AdminUserFinanceUploadRecord::getFinanceConfigArray($queueData['upload_record_id']);

        //扣费
        $res = \App\HttpController\Models\AdminV2\AdminNewUser::charge(
            $uploadRes['user_id'],
            $uploadRes['money'],
            $queueData['id'],
            [
                'detailId' => $queueData['id'],
                'detail_table' => 'admin_user_finance_export_data_queue',
                'price' => $uploadRes['money'],
                'userId' => $uploadRes['user_id'],
                'type' => FinanceLog::$chargeTytpeFinance,
                'batch' => $queueData['id'],
                'title' => '',
                'detail' => '',
                'reamrk' => '',
                'status' => 1,
            ],
            10
        );
        if(!$res  ){
            return  CommonService::getInstance()->log4PHP(
                json_encode([
                    'AdminNewUser::charge  false ',
                ])
            );
        }
        //设置上传收费时间|本名单的
        $res = AdminUserFinanceUploadRecord::updateLastChargeDate($uploadRes['id'],date('Y-m-d H:i:s'));
        if(!$res  ){
            return  CommonService::getInstance()->log4PHP(
                json_encode([
                    'AdminUserFinanceUploadRecord::updateLastChargeDate  false ',
                ])
            );
        }

        foreach($financeDatas['details'] as $financeData){
            $AdminUserFinanceUploadDataRecord = AdminUserFinanceUploadDataRecord::
            findById($financeData['UploadDataRecordId'])->toArray();
            $priceItem =    intval($AdminUserFinanceUploadDataRecord['real_price']);
            // 收费了
            if($priceItem){
                //设置收费记录
                $AdminUserFinanceChargeInfoId = AdminUserFinanceChargeInfo::addRecordV2(
                    [
                        'user_id' => $AdminUserFinanceUploadDataRecord['user_id'],
                        'batch' => $AdminUserFinanceUploadDataRecord['id'].'_'.$queueData['id'],
                        'entName' => $financeData['entName'],
                        'start_year' => $AdminUserFinanceUploadDataRecord['charge_year_start'],
                        'end_year' => $AdminUserFinanceUploadDataRecord['charge_year_end'],
                        'year' => $AdminUserFinanceUploadDataRecord['charge_year'],
                        'price' => $priceItem,
                        'price_type' => $AdminUserFinanceUploadDataRecord['price_type'],
                        'status' => AdminUserFinanceChargeInfo::$state_init,
                    ]
                );
                if(!$AdminUserFinanceChargeInfoId  ){
                    return  CommonService::getInstance()->log4PHP(
                        json_encode([
                            'AdminUserFinanceChargeInfo::addRecordV2 false ',
                        ])
                    );
                }
                //设置上次计费时间 |具体用户对应的企业数据
                $res = AdminUserFinanceData::updateLastChargeDate(
                    $AdminUserFinanceUploadDataRecord['user_finance_data_id'],
                    date('Y-m-d H:i:s')
                );
                if(!$res  ){
                    return  CommonService::getInstance()->log4PHP(
                        json_encode([
                            'AdminUserFinanceData::updateLastChargeDate  false ',
                        ])
                    );
                }

                //设置缓存过期时间
                $res = AdminUserFinanceData::updateCacheEndDate(
                    $AdminUserFinanceUploadDataRecord['user_finance_data_id'],
                    date('Y-m-d H:i:s'),
                    $finance_config['cache']
                );
                if(!$res  ){
                    return  CommonService::getInstance()->log4PHP(
                        json_encode([
                            'AdminUserFinanceData::updateCacheEndDate  false ',
                        ])
                    );
                }
            }
        }

        return  true;
    }

    public static function setFinanceDataState($queueId){
        $queueData = self::findById($queueId)->toArray();
        $uploadRes = AdminUserFinanceUploadRecord::findById($queueData['upload_record_id'])->toArray();
        $uploadDatas = AdminUserFinanceUploadDataRecord::findByUserIdAndRecordIdV2(
            $uploadRes['user_id'],$uploadRes['id']
        );

        $status = self::$state_confirmed;
        foreach ($uploadDatas as $uploadData){
            if(
                AdminUserFinanceData::checkDataNeedConfirm($uploadData['user_finance_data_id'])
            ){
                $status = self::$state_needs_confirm;
                break;
            };
        }
        CommonService::getInstance()->log4PHP(
            json_encode([
                'setFinanceDataState  ',
                '$queueId'=>$queueId,
                '$status' => $status
            ])
        );

        return self::updateStatusById($queueId,$status);

    }

//    public static function updateMoneyById(
//        $id,$money
//    ){
//        // 吃完饭 走一下啊
//        // banner: 切出图来  设计多少换多少
//        //   二期： 没开发完的
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                'AdminUserFinanceExportDataQueue  updateMoneyById start' ,
//                'params $money ' =>$money,
//                'params $id ' =>$id
//            ])
//        );
//        $info = AdminUserFinanceExportDataQueue::create()->where('id',$id)->get();
//        return $info->update([
//            'id' => $id,
//            'money' => $money,
//            'updated_at'=>time()
//        ]);
//    }

    static  function  addRecordV2($info){

        if(
            self::findByBatch($info['batch'])
        ){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'AdminUserFinanceExportDataQueue  findByBatch ok ' ,
                    'params batch ' =>$info['batch']
                ])
            );
            return  true;
        }

        return AdminUserFinanceExportDataQueue::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){

        try {
           $res =  AdminUserFinanceExportDataQueue::create()->data([
                'upload_record_id' => $requestData['upload_record_id'],
                'touch_time' => $requestData['touch_time'],
                'batch' => $requestData['batch'],
               'user_id' => $requestData['user_id'],
               'path' => $requestData['path']?:'',
               'file_name' => $requestData['file_name']?:'',
                'status' => $requestData['status']?:1,
               'created_at' => time(),
               'updated_at' => time(),
           ])->save();

        } catch (\Throwable $e) {
            return CommonService::getInstance()->log4PHP(
                json_encode([
                    'AdminUserFinanceExportDataQueue  addRecord  failed ' ,
                    'params $requestData ' =>$requestData,
                    'message' => $e->getMessage()
                ])
            );
        }
        return $res;
    }

    public static function findByCondition($whereArr,$limit){
        $res =  AdminUserFinanceExportDataQueue::create()
            ->where($whereArr)
            ->limit($limit)
            ->all();
        return $res;
    }

    public static function findByConditionV2($whereArr,$page){

        $model = AdminUserFinanceExportDataQueue::create()
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

    public static function findByConditionV3($whereArr,$page){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'export data queue  findByConditionV3  ' ,
                'params $whereArr ' =>$whereArr,
                'params $page ' =>$page
            ])
        );
        $model = AdminUserFinanceExportDataQueue::create();
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
        $res =  AdminUserFinanceExportDataQueue::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByBatch($batch){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'findByBatch  start  ' ,
                'params $batch ' =>$batch
            ])
        );
        $res =  AdminUserFinanceExportDataQueue::create()
            ->where('batch',$batch)
            ->get();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = AdminUserFinanceExportDataQueue::findById($id);
        CommonService::getInstance()->log4PHP(
            json_encode([
                ' AdminUserFinanceExportDataQueue  setTouchTime  '=>'start',
                '$id,' =>$id,
                '$touchTime' =>$touchTime,
            ])
        );
        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }



    public static function updateStatusById(
        $id,$status
    ){
        // 吃完饭 走一下啊
        // banner: 切出图来  设计多少换多少
        //   二期： 没开发完的
        CommonService::getInstance()->log4PHP(
            json_encode([
                'AdminUserFinanceExportDataQueue  updateStatusById start' ,
                'params $status ' =>$status,
                'params $id ' =>$id
            ])
        );
        $info = AdminUserFinanceExportDataQueue::create()->where('id',$id)->get();
        return $info->update([
            'id' => $id,
            'status' => $status,
            'updated_at'=>time()
        ]);
    }

    public static function updatePriorityById(
        $id,$priority
    ){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'AdminUserFinanceExportDataQueue  updatePriorityById start' ,
                'params $priority ' =>$priority,
                'params $id ' =>$id
            ])
        );
        $info = self::findById($id);
        return $info->update([
            'id' => $id,
            'status' => $priority,
            'updated_at'=>time()
        ]);
    }


    public static function setFilePath($id,$path,$fileName){

        CommonService::getInstance()->log4PHP(
            json_encode([
                'export data queue setFilePath   '=>$id,$path,$fileName
            ])
        );
        $info = AdminUserFinanceExportDataQueue::findById($id);

        return $info->update([
            'path' => $path,
            'file_name' => $fileName,
        ]);
    }

    // 用完今日余额的
    public static function findBySql($where){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'Export data queue   '=> 'strat',
                '$where' =>$where,

            ])
        );
        $Sql = " select *  
                            from  
                        `admin_user_finance_export_data_queue` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }

}
