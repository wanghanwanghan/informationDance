<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class AdminUserSoukeConfig extends ModelBase
{
    // 搜客配置
    protected $tableName = 'admin_user_souke_config';

    static  $state_init = 1;
    static  $state_init_cname =  '正常';

    static  $state_del = 5;
    static  $state_del_cname =  '已删除';

    static  $is_destory_no = 0;
    static  $is_destory_no_cname =  '正常';
    static  $is_destory_yes = 1;
    static  $is_destory_yes_cname =  '已删除';

    public static function setStatus($id,$status){
        $info = AdminUserSoukeConfig::findById($id);

        return $info->update([
            'status' => $status,
        ]);
    }

    public static function getStatusMap(){

        return [
            self::$state_init => self::$state_init_cname,
            self::$state_del => self::$state_del_cname
        ];
    }

    static public function getAllFields(){
        $fields = [
            'xd_id' => 'id',
            'base' => 'base',
            'name' => '企业名',
            'legal_person_id'=>'法人id',
            'legal_person_name'=>'法人',
            'legal_person_type' => '法人类型',
            'reg_number' => '注册号',
            'company_org_type' => '公司类型',
            'reg_location' => '注册地',
            'estiblish_time' =>'成立时间',
            'from_time' => '开始日期',
            'to_time' => '截至日期',
            'business_scope' => '经营范围',
            'reg_institute'=>'注册机构',
            'approved_time' => 'approved_time',
            'reg_status'=>'营业状态',
            'reg_capital'=>'注册资本',
            'actual_capital' => 'actual_capital',
            'org_number'=>'org_number',
            'org_approved_institute'=>'org_approved_institute',
            'list_code' => 'list_code',
            'property1' => '社会统一信用代码',
            'property2' => 'property2',
            'property3' => 'property3',
            'property4' => 'property4',
            'ying_shou_gui_mo'=>'营收规模',
            'si_ji_fen_lei_code' => '四级分类',
            'si_ji_fen_lei_full_name'=>'四级分类中文名',
            'gong_si_jian_jie'=>'公司简介',
            'gao_xin_ji_shu' => '高新技术',
            'deng_ling_qi_ye' => '瞪羚企业',
            'tuan_dui_ren_shu' => '团队人数',
            'tong_xun_di_zhi'=>'通讯地址',
            'web' => '官网',
            'yi_ban_ren' => '一般人',
            'shang_shi_xin_xi' => '商品信息',
            'app' => 'APP',
            'manager' => '经理',
            'inv_type' => 'inv_type',
            'inv'=>'inv',
            'en_name' => 'en_name',
            'email' => '邮箱',
            'app_data'=>'主营产品',
            'shang_pin_data'=>'商品',
            'zlxxcy' => 'zlxxcy',
            'szjjcy' => 'szjjcy',
            'report_year'=>'report_year',
            'jin_chu_kou' => '进出口',
            'iso' => '高新技术',

        ];
        return $fields;
    }

    static function  getAllowedFieldsArray($userId){
        $res = self::findByUser($userId);
        if(!$res){
            return ["xd_id","name"];
        }
        $fieldStr = $res->getAttr("allowed_fields");
        return json_decode($fieldStr,true);
    }

    public static function addRecord($requestData){

        try {
           $res =  AdminUserSoukeConfig::create()->data([
                'user_id' => $requestData['user_id'],
               'allowed_fields' => $requestData['allowed_fields'],
               'price' => $requestData['price'],
               'max_daily_nums' => $requestData['max_daily_nums'],
               'remark' => $requestData['remark']?:'',
                'status' => $requestData['status']?:1,
               'type' => $requestData['type']?:1,
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

    public static function addRecordV2($requestData){

        $oldRes = self::findByUser($requestData['user_id']);
        if($oldRes){
            return  $oldRes->getAttr('id');
        }
        return self::addRecord($requestData);
    }

    public static function findAllByCondition($whereArr){
        $res =  AdminUserSoukeConfig::create()
            ->where($whereArr)
            ->all();
        return $res;
    }


    public static function findByUser($userId){
        $res =  AdminUserSoukeConfig::create()
            ->where([
                'user_id' => $userId,
                'status' => self::$state_init
            ])
            ->get();
        return $res;
    }


    public static function findByConditionWithCountInfo($whereArr,$page){
        $model = AdminUserSoukeConfig::create()
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



    public static function setTouchTime($id,$touchTime){
        $info = AdminUserSoukeConfig::findById($id);

        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }

    public static function findByConditionV2($whereArr,$page){
        $model = AdminUserSoukeConfig::create();
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
        $res =  AdminUserSoukeConfig::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findAllByAdminIdAndEntName($admin_id,$entName){
        $res =  AdminUserSoukeConfig::create()
            ->where('admin_id',$admin_id)
            ->where('entName',$entName)
            ->all();
        return $res;
    }

    public static function findByConditionV3($whereArr,$page){
        $model = AdminUserSoukeConfig::create();
        if(
            !empty($whereArr)
        ){
            foreach ($whereArr as $whereItem){
                $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
            }
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


    public static function setData($id,$field,$value){
        $info = AdminUserSoukeConfig::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }
}
