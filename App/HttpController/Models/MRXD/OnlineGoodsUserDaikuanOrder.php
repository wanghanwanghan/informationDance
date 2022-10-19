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

class OnlineGoodsUserDaikuanOrder extends ModelBase
{

    protected $tableName = 'online_goods_user_daikuan_order';

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
        return OnlineGoodsUserDaikuanOrder::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  OnlineGoodsUserDaikuanOrder::create()->data([
               //产品
               'product_id' => intval($requestData['product_id']),
               //购买人
               'purchaser_id' => intval($requestData['purchaser_id']),
               'amount' => $requestData['amount']?:0,
               'purchaser_name' => $requestData['purchaser_name']?:'',
               'purchaser_phone' => $requestData['purchaser_phone']?:'',
               'zhijin_phone' => $requestData['zhijin_phone']?:'',
               'xindong_commission_rate' => $requestData['xindong_commission_rate']?:'',
               'commission_rate' => $requestData['xindong_commission_rate']?:'',
               'order_date' => $requestData['order_date']?:'',
               'commission_date' => $requestData['commission_date']?:'',
               'remark' => $requestData['remark']?:'',
                'commission_set_state' => intval($requestData['commission_set_state']),
                'commission_state' => intval($requestData['commission_state']),
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

    /**
    点击发放佣金》生成需分佣的记录》已经设置晚比例的 自动发到账上

    根据订单添加分佣信息
    [VIP-A]—邀请—>[B]—[B下单￥]
    分佣：
    信动给VIP分佣：信动—>A
    邀请人给被邀请人分佣：A→B
    邀请人给被邀请人分佣：

    [VIP-A]—邀请—>[B]—邀请—>[C]—邀请—>[D]—[D下单￥]
    分佣：
    信动给VIP分佣：信动—>A
    VIP给邀请人分佣：信动—>A
    邀请人给被邀请人分佣：C→D
     */
    static function addCommissionInfoById($id){

        $orderInfo = self::findById($id);
        $orderInfo = $orderInfo->toArray();
        OnlineGoodsCommissions::addCommissionInfoByOrderInfo($orderInfo);

        return true;
    }

    public static function findAllByCondition($whereArr){
        $res =  OnlineGoodsUserDaikuanOrder::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = OnlineGoodsUserDaikuanOrder::findById($id);

        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }

    //软删除
    public static function delRecord($id){
        $info = OnlineGoodsUserDaikuanOrder::findById($id);
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
        $model = OnlineGoodsUserDaikuanOrder::create()
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
        $model = OnlineGoodsUserDaikuanOrder::create();
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
        $res =  OnlineGoodsUserDaikuanOrder::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findAllByUserId($userId){
        $res =  OnlineGoodsUserDaikuanOrder::create()
            ->where('user_id',$userId)
            ->all();
        return $res;
    }

    public static function findAllByUserIdV2($userId){
        $res =  OnlineGoodsUserDaikuanOrder::create()
            ->where('user_id',$userId)
            ->where('is_del',0)
            ->all();
        return $res;
    }

    public static function findByEntName($user_id,$ent_name){
        $res =  OnlineGoodsUserDaikuanOrder::create()
            ->where('user_id',$user_id)
            ->where('ent_name',$ent_name)
            ->get();
        return $res;
    }

    public static function findByUser($user_id){
        $res =  OnlineGoodsUserDaikuanOrder::create()
            ->where('user_id',$user_id)
            ->where('is_del',0)
            ->get();
        return $res;
    }


    public static function findByEntNameV2($user_id,$ent_name){
        $res =  OnlineGoodsUserDaikuanOrder::create()
            ->where('user_id',$user_id)
            ->where('ent_name',$ent_name)
            ->where('is_del',0)
            ->get();
        return $res;
    }

    public static function findByEntNameV3($user_id,$ent_name){
        $res =  OnlineGoodsUserDaikuanOrder::create()
            ->where('user_id',$user_id)
            ->where('ent_name',$ent_name)
            ->where('is_del',1)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = OnlineGoodsUserDaikuanOrder::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `online_goods_user_daikuan_order` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }
}
