<?php

namespace App\HttpController\Models\MRXD;

use App\ElasticSearch\Model\Company;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Models\RDS3\HdSaic\CodeCa16;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Sms\AliSms;
use App\HttpController\Service\XinDong\XinDongService;
use EasySwoole\RedisPool\Redis;
use Vtiful\Kernel\Format;

// use App\HttpController\Models\AdminRole;

class OnlineGoodsUserBaoXianOrder extends ModelBase
{

    protected $tableName = 'online_goods_user_baoxian_order';

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

    static function getStatusMap(){
        return  [
            'score_range'=>[
                5=>'70-80',
                10=>'80-90',
                15=>'90-100',
            ],
            'establish_yeasr_range'=>[
                2=>'2年以内',
                5=>'2-5年',
                10=>'5-10年',
                15=>'10-15年',
                20=>'15-20年',
                25=>'20年以上',
            ],
            'ying_shou_range'=>[
                5=>'微型',
                10=>'小型C类',
                15=>'小型B类',
                20=>'小型A类',
                25=>'中型C类',
                30=>'中型B类',
                40=>'中型A类',
                45=>'大型C类',
                50=>'大型B类',
                60=>'大型A类',
                65=>'特大型C类',
                70=>'特大型B类',
                80=>'特大型A类',
            ]
        ];
    }

    static  function  addRecordV2($info){
        return OnlineGoodsUserBaoXianOrder::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  OnlineGoodsUserBaoXianOrder::create()->data([
                'product_id' => $requestData['product_id'],
                'purchaser_id' => $requestData['purchaser_id'],
                'price' => $requestData['price'],
                'xindong_commission' => $requestData['xindong_commission'],
                'commission_set_state' => $requestData['commission_set_state'],
                'commission_state' => $requestData['commission_state'],
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
        $res =  OnlineGoodsUserBaoXianOrder::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = OnlineGoodsUserBaoXianOrder::findById($id);

        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }

    //软删除
    public static function delRecord($id){
        $info = OnlineGoodsUserBaoXianOrder::findById($id);
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
        $model = OnlineGoodsUserBaoXianOrder::create()
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
        $model = OnlineGoodsUserBaoXianOrder::create();
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
        $res =  OnlineGoodsUserBaoXianOrder::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findAllByUserId($userId){
        $res =  OnlineGoodsUserBaoXianOrder::create()
            ->where('user_id',$userId)
            ->all();
        return $res;
    }

    public static function findAllByUserIdV2($userId){
        $res =  OnlineGoodsUserBaoXianOrder::create()
            ->where('user_id',$userId)
            ->where('is_del',0)
            ->all();
        return $res;
    }

    public static function findByEntName($user_id,$ent_name){
        $res =  OnlineGoodsUserBaoXianOrder::create()
            ->where('user_id',$user_id)
            ->where('ent_name',$ent_name)
            ->get();
        return $res;
    }

    public static function findByUser($user_id){
        $res =  OnlineGoodsUserBaoXianOrder::create()
            ->where('user_id',$user_id)
            ->where('is_del',0)
            ->get();
        return $res;
    }


    public static function findByEntNameV2($user_id,$ent_name){
        $res =  OnlineGoodsUserBaoXianOrder::create()
            ->where('user_id',$user_id)
            ->where('ent_name',$ent_name)
            ->where('is_del',0)
            ->get();
        return $res;
    }

    public static function findByEntNameV3($user_id,$ent_name){
        $res =  OnlineGoodsUserBaoXianOrder::create()
            ->where('user_id',$user_id)
            ->where('ent_name',$ent_name)
            ->where('is_del',1)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = OnlineGoodsUserBaoXianOrder::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `online_goods_user_baoxian_order` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }
}