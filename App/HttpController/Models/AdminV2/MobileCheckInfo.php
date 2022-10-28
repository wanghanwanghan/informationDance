<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\ChuangLan\ChuangLanService;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class MobileCheckInfo extends ModelBase
{

    //
    protected $tableName = 'mobile_check_info_chuang_lan';



    public static function getStatusMap(){
        return ChuangLanService::getStatusCnameMap();
    }

    static  function  addRecordV2($info){
        $oldRes =  self::findByMobile(
            $info['mobile']
        );
        if( $oldRes  ){
            return  self::updateById(
                $oldRes->getAttr('id'),
                $info
            );
        }

        return MobileCheckInfo::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  MobileCheckInfo::create()->data([
                'mobile' => $requestData['mobile'],
                'status' => $requestData['status'],
                'area' => $requestData['area']?:'',
                'numberType' => $requestData['numberType']?:'',
                'chargesStatus' => $requestData['chargesStatus'],
                'lastTime' => $requestData['lastTime'],
                'raw_return' => $requestData['raw_return']?:'',
                'remark' => $requestData['remark']?:'',
               'created_at' => time(),
               'updated_at' => time(),
           ])->save();

        } catch (\Throwable $e) {
            return CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'failed',
                    '$requestData' => $requestData
                ])
            );
        }
        return $res;
    }

    static function  checkIfIsMobile($mobileStr){
        if(preg_match("/^1[34578]\d{9}$/", $mobileStr)){
           return true;
        }
        return false;
    }

    static function  findResByMobile($mobileStr){

        $dbInfo = self::findByMobileV2($mobileStr);
        return  !empty($dbInfo)?[
            "mobile"=>$dbInfo['mobile'],
            "lastTime"=>$dbInfo['lastTime'],
            "area"=>$dbInfo['area'],
            "numberType"=>$dbInfo['numberType'],
            "chargesStatus"=>$dbInfo['chargesStatus'],
            "status"=>$dbInfo['status'],
        ]:[];

    }

    //简版  不加redis
    static function  checkMobilesByChuangLan($mobileStr){
        $mobilesArr = explode(',',$mobileStr);
        $needsCheckMobiles =  [];
        $invalidMobiles =  [];
        $newCheckRes = [];

        foreach ($mobilesArr as $mobile){
            //校验号码有效性
            if( !self::checkIfIsMobile($mobile) ){
                 $invalidMobiles[] = $mobile;
                $newCheckRes[] = [
                    'mobile'=>$mobile,
                    'status'=> '',
                    'area'=> '',
                    'numberType'=> '',
                    'chargesStatus'=> 1,
                    'lastTime'=> '',
                    'remark'=> '号码无效',
                ];
                 continue;
             }

            //取旧的结果
            $tmpRes = self::findResByMobile($mobile);
            if(!empty($tmpRes)){
                $newCheckRes[] = $tmpRes;
                continue;
            }

            //没有旧的结果
            $needsCheckMobiles[$mobile] = $mobile;
        }

        //需要查询的
        if(
            !empty($needsCheckMobiles)
        ){
            $newMobileStr = join(',',$needsCheckMobiles);
            $newCheckRes = (new ChuangLanService())->getCheckPhoneStatus([
                'mobiles' => $newMobileStr,
            ]);

            //全部都是无效的
            if (empty($newCheckRes['data'])){
                foreach ($needsCheckMobiles as $needsCheckMobile){
                    $tmpRes = [
                        'mobile'=>$needsCheckMobile,
                        'status'=> 999,
                        'area'=> '',
                        'numberType'=> '',
                        'chargesStatus'=> 1,
                        'lastTime'=> '',
                        'raw_return'=> json_encode($newCheckRes) ,
                    ];
                    self::addRecordV2($tmpRes);
                    $newCheckRes[] = $tmpRes;
                }
            }
            else{
                foreach($newCheckRes['data'] as $dataItem){
                    $tmpRes = [
                        'mobile'=>$dataItem['mobile'],
                        'status'=> $dataItem['status'],
                        'area'=> $dataItem['area'],
                        'numberType'=> $dataItem['numberType'],
                        'chargesStatus'=> $dataItem['chargesStatus'] ,
                        'lastTime'=> $dataItem['lastTime'],
                        'raw_return'=> json_encode($newCheckRes) ,
                    ];
                    self::addRecordV2($tmpRes);
                    $newCheckRes[] = $tmpRes;
                }
            }
        }

        return self::formatReturnData($newCheckRes);
    }

    static function formatReturnData($datasArr){
        return [
            'code'=> 200000,
            'chargeStatus'=>1,
            'chargeCount'=>1,
            'message'=>"成功",
            "data" => $datasArr
        ];
    }



    public static function findAllByCondition($whereArr){
        $res =  MobileCheckInfo::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = MobileCheckInfo::findById($id);
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
        $model = MobileCheckInfo::create()
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
        $model = MobileCheckInfo::create();
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
        $res =  MobileCheckInfo::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByMobile($mobile){
        $res =  MobileCheckInfo::create()
            ->where('mobile',$mobile)
            ->get();
        return $res;
    }

    public static function findByMobileV2($mobile){
        $res =  MobileCheckInfo::create()
            ->where('mobile',$mobile)
            ->get();
        return $res ?$res->toArray():[];
    }


    public static function setData($id,$field,$value){
        $info = MobileCheckInfo::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    // 用完今日余额的
    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `mobile_check_info_chuang_lan` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }


}
