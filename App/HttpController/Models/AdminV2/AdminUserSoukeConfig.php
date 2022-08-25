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
        /*

  `base` varchar(31) DEFAULT NULL COMMENT '归属省份的首字母小写',
  `name` varchar(255) DEFAULT NULL COMMENT '公司名称',
  `legal_person_id` bigint(20) DEFAULT NULL COMMENT '法人ID',
  `legal_person_name` varchar(120) DEFAULT NULL COMMENT '法人姓名',
  `legal_person_type` int(4) DEFAULT '1' COMMENT '法人类型，1 人 2 公司',
  `reg_number` varchar(31) DEFAULT NULL COMMENT '注册号',
  `company_org_type` varchar(127) DEFAULT NULL COMMENT '公司类型',
  `reg_location` varchar(255) DEFAULT NULL COMMENT '注册地址',
  `estiblish_time` datetime DEFAULT NULL COMMENT '成立日期',
  `from_time` datetime DEFAULT NULL COMMENT '营业期限开始日期',
  `to_time` datetime DEFAULT NULL COMMENT '营业期限终止日期',
  `business_scope` varchar(4091) DEFAULT NULL COMMENT '经营范围',
  `reg_institute` varchar(255) DEFAULT NULL COMMENT '登记机关',
  `approved_time` datetime DEFAULT NULL COMMENT '核准日期',
  `reg_status` varchar(31) DEFAULT NULL COMMENT '企业状态',
  `reg_capital` varchar(50) DEFAULT NULL COMMENT '注册资金',
  `actual_capital` varchar(31) DEFAULT NULL COMMENT '实收注册资金',
  `org_number` varchar(31) DEFAULT NULL COMMENT '组织机构代码',
  `org_approved_institute` varchar(127) DEFAULT NULL,
  `parent_id` bigint(20) DEFAULT NULL COMMENT '上级机构ID',
  `list_code` varchar(20) DEFAULT NULL COMMENT '上市代码',
  `property1` varchar(255) DEFAULT NULL COMMENT '统一社会信用代码',
  `property2` varchar(255) DEFAULT '' COMMENT '新公司名id',
  `property3` varchar(255) DEFAULT NULL COMMENT '英文名',
  `property4` varchar(255) DEFAULT NULL COMMENT '纳税人识别号',
  `up_state` int(11) unsigned DEFAULT NULL,
         * */
        $fields = [
            //'xd_id' => 'id',
            //'base' => 'base',
            'name' => '企业名',
            //'legal_person_id'=>'法人id',
            'legal_person_name'=>'法人',
            //'legal_person_type' => '法人类型',
            //'reg_number' => '注册号',
            'company_org_type' => '公司类型',
            'reg_location' => '注册地址',
            'estiblish_time' =>'成立日期',
            'from_time' => '营业期限开始日期',
            'to_time' => '营业期限终止日期',
            'business_scope' => '经营范围',
            'reg_institute'=>'登记机关',
            'approved_time' => '核准日期',
            'reg_status'=>'营业状态',
            'reg_capital'=>'注册资本',
            'actual_capital' => '实收注册资金',
            'org_number'=>'组织机构代码',
           // 'org_approved_institute'=>'org_approved_institute',
            'list_code' => '上市代码',
            'property1' => '统一社会信用代码',
            //'property2' => 'property2',
            'property3' => '英文名',
            'property4' => '纳税人识别号',
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
            'manager' => '企业主要人员',
            //'inv_type' => 'inv_type',
            'inv'=>'股东',
            'en_name' => 'en_name',
            'email' => '邮箱',
            'app_data'=>'主营产品',
            'shang_pin_data'=>'商品',
            'zlxxcy' => '战略新兴产业',
            'szjjcy' => '数字经济产业',
            //'report_year'=>'report_year',
            'jin_chu_kou' => '进出口',
            'iso' => '高新技术',
        ];
        return $fields;
    }
    static public function getAllFieldsV2(){
        $fields = [
            'ENTNAME' => '企业名',
            'PRIPID' => '公示系统ID',
            'UNISCID' => '统一社会信用代码',
            'REGNO' => '注册号',
            'NACAOID' => '组织机构代码',
            'NAME' => '法人姓名',
            'NAMETITLE' => '法人称谓',
            'ENTTYPE' => '公司类型编码',
            'ESDATE' => '成立日期',
            'APPRDATE' => '核准日期',
            'ENTSTATUS' => '企业状态编码',
            'REGCAP' => '注册资金(万元)',
            'REGCAP_NAME' => '注册资金原始',
            'REGCAPCUR' => '注册资金币种',
            'RECCAP' => '实收注册资金',
            'REGORG' => '登记机关',
            'OPFROM' => '营业期限开始日期',
            'OPTO' => '营业期限终止日期',
            'OPSCOPE' => '经营范围',
            'DOM' => '注册地址',
            'DOMDISTRICT' => '所在行政区划',
            'NIC_ID' => '行业分类代码',
            'CANDATE' => '注销日期',
            'REVDATE' => '吊销日期',
            'ying_shou_gui_mo'=>'营收规模',
            'gong_si_jian_jie'=>'公司简介',
            'gao_xin_ji_shu' => '高新技术',
            'deng_ling_qi_ye' => '瞪羚企业',
            'tuan_dui_ren_shu' => '团队人数',
            'tong_xun_di_zhi'=>'通讯地址',
            'web' => '官网',
            'yi_ban_ren' => '一般人',
            'shang_shi_xin_xi' => '商品信息',
            'app' => 'APP',
            'manager' => '企业主要人员',
            'inv'=>'股东',
            'en_name' => 'en_name',
            'email' => '邮箱',
            'app_data'=>'主营产品',
            'shang_pin_data'=>'商品',
            'zlxxcy' => '战略新兴产业',
            'szjjcy' => '数字经济产业',
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
