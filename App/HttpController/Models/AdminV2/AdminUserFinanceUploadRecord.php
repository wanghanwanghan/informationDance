<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;

// use App\HttpController\Models\AdminRole;

class AdminUserFinanceUploadRecord extends ModelBase
{
    protected $tableName = 'admin_user_finance_upload_record';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at'; 
    
    static $stateInit = 1;
    static $stateInitCname =  '初始';
    static $stateParsed = 5;
    static $stateParsedCname =  '已经解析入库';
    static $stateCalCulatedPrice = 10;
    static $stateCalCulatedPriceCname = '已经计算价格'; 

    static $stateHasGetData = 20;
    static $stateHasGetDataCname = '已取完数据';

    static $stateHasCalcluteRealPrice = 25;
    static $stateHasCalcluteRealPriceCname = '已经计算完真实价格';


    public static function addUploadRecord($requestData){ 
        try {
           $res =  AdminUserFinanceUploadRecord::create()->data([
                'user_id' => $requestData['user_id'], 
                'years' => $requestData['years'], 
                'file_path' => $requestData['file_path'],  
                'file_name' => $requestData['file_name'],  
                'title' => $requestData['title']?:'',
                'finance_config' => $requestData['finance_config'],  
                'readable_price_config' => $requestData['readable_price_config'],  
                'reamrk' => $requestData['reamrk']?:'',
                'status' => $requestData['status']?:1,
            ])->save();

        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'AdminUserFinanceUploadRecord sql err',
                    $e->getMessage(),
                ])
            );  
        }  

        return $res;
    }

    public static function findByIdAndFileName($user_id,$file_name){
        $res =  AdminUserFinanceUploadRecord::create()->where([
            'user_id' => $user_id,  
            'file_name' => $file_name,   
            // 'status' => 1,  
        ])->get(); 

        return $res;
    }

    public static function findByCondition($whereArr,$offset, $limit){
        $res =  AdminUserFinanceUploadRecord::create()
            ->where($whereArr)
            ->limit($offset, $limit)
            ->all();  
        return $res;
    }

    public static function changeStatus($id,$status){ 
        $info = AdminUserFinanceUploadRecord::create()->where('id',$id)->get(); 
        return $info->update([
            'id' => $id,
            'status' => $status, 
        ]);
    }


    public static function findById($id){
        $res =  AdminUserFinanceUploadRecord::create()
            ->where('id',$id)
            ->get();
        return $res;
    }

    //获取财务数据 
    public static function getAllFinanceDataByUploadRecordId(
        $userId,$uploadRecordId,$status,$keepPrice = 1
    ){
        $returnDatas  = [];

        //找到初始的配置值
        $selfMode = AdminUserFinanceUploadRecord::findById($uploadRecordId);
        $returnDatas['config_arr'] = json_decode($selfMode->getAttr('finance_config'),true);

        // 取到该记录对应的上传数据
        $uploadDatas = AdminUserFinanceUploadDataRecord::findByUserIdAndRecordId(
            $userId,$uploadRecordId,$status,["user_finance_data_id"]
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                '取到该记录对应的上传数据',
                '$uploadDatas total' => count($uploadDatas),
                '$userId' => $userId,
                '$uploadRecordId' => $uploadRecordId,
                '$status' => $status,
                '$keepPrice' => $keepPrice,
            ])
        );


        foreach($uploadDatas as $uploadData){
            // 财务数据|包含具体价格等
            $financeDatas = self::getFinanceCompleData(
                $uploadData['user_finance_data_id']
            ); 
            // 返回的财务数据里是否加上价格字段
            if($keepPrice){
                $financeDatas['finance_data']['real_price'] = $financeDatas['price' ]; 
                $financeDatas['finance_data']['price_detail'] = $financeDatas['price_detail' ]; 
            } 
            // 财务数据
            $returnDatas['finance_data'][$uploadData['user_finance_data_id']] = $financeDatas['finance_data'];
            // 收费明细
            $returnDatas['chargeDetails'][$uploadData['user_finance_data_id']] = 
            [
                'user_finance_data_id' => $uploadData['user_finance_data_id'],
                'price' =>$financeDatas['price' ],
                'price_detail' => $financeDatas['price_detail' ]
            ];
            // 总条数
            $returnDatas['totalNums'] ++ ;
            // 总计收费
            $returnDatas['totalPrice'] +=  $financeDatas['price' ]; 
        }
 
        return $returnDatas;
    }


    public static function getFinanceCompleData($user_finance_data_id){ 
        //该数据对应的相关价格配置/缓存配置等
         $AdminUserFinanceDataRes = AdminUserFinanceData::findById(
            $user_finance_data_id
        );
        $AdminUserFinanceData = $AdminUserFinanceDataRes->toArray(); 

        //取实际的财务数据
        $NewFinanceDataRes = NewFinanceData::findById($AdminUserFinanceData['finance_data_id']);  
        $NewFinanceData = $NewFinanceDataRes->toArray();
        $NewFinanceData['user_finance_data_id'] = $user_finance_data_id;

        $price = $AdminUserFinanceDataRes['price'];
        $priceDetail = '';

        if(
            $AdminUserFinanceDataRes['cache_end_date'] > 0 &&
            strtotime($AdminUserFinanceDataRes['cache_end_date']) > time()
        ){
            $price = 0;
            $priceDetail = '在缓存期('.$AdminUserFinanceDataRes['cache_end_date'].')，不收费';
        }
        return [
            'finance_data' => $NewFinanceData,
            'price' => $price,
            'price_detail' => $priceDetail,
        ];
    }
 

}
