<?php

namespace App\HttpController\Models\MRXD;

use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\MobileCheckInfo;
use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\BusinessBase\CompanyClue;
use App\HttpController\Models\BusinessBase\WechatInfo;
use App\HttpController\Models\ModelBase;
use App\HttpController\Models\RDS3\HdSaic\CodeCa16;
use App\HttpController\Models\RDS3\HdSaic\CodeEx02;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Service\ChuangLan\ChuangLanService;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Sms\AliSms;
use App\HttpController\Service\XinDong\XinDongService;
use EasySwoole\RedisPool\Redis;

// use App\HttpController\Models\AdminRole;

class TmpInfo extends ModelBase
{

    protected $tableName = 'tmp_info';


    static  function  addRecordV2($info){
//        $res = self::findByName($info['name']);
//        if($res){
//            return  $res->id;
//        }
        return TmpInfo::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  TmpInfo::create()->data([
                'biao_ti' => $requestData['biao_ti']?:'',
                'xiang_mu_ming_cheng' => $requestData['xiang_mu_ming_cheng']?:'',
                'xiang_mu_bian_hao' => $requestData['xiang_mu_bian_hao']?:'',
                'xiang_mu_jian_jie' => $requestData['xiang_mu_jian_jie']?:'',
                'cai_gou_fang_shi' => $requestData['cai_gou_fang_shi']?:'',
                'gong_gao_lei_xing2' => $requestData['gong_gao_lei_xing2']?:'',
                'gong_gao_ri_qi' => $requestData['gong_gao_ri_qi']?:'',
                'xing_zheng_qv_yu_sheng' => $requestData['xing_zheng_qv_yu_sheng']?:'',
                'xing_zheng_qv_yu_shi' => $requestData['xing_zheng_qv_yu_shi']?:'',
                'xing_zheng_qv_yu_xian' => $requestData['xing_zheng_qv_yu_xian']?:'',
                'cai_gou_dan_wei_ming_cheng' => $requestData['cai_gou_dan_wei_ming_cheng']?:'',
                'cai_gou_dan_wei_di_zhi' => $requestData['cai_gou_dan_wei_di_zhi']?:'',
                'cai_gou_dan_wei_lian_xi_ren' => $requestData['cai_gou_dan_wei_lian_xi_ren']?:'',
                'cai_gou_dan_wei_lian_xi__dian_hua' => $requestData['cai_gou_dan_wei_lian_xi__dian_hua']?:'',
                'ming_ci' => $requestData['ming_ci']?:'',
                'zhong_biao_gong_ying_shang' => $requestData['zhong_biao_gong_ying_shang']?:'',
                'zhong_biao_jin_e' => $requestData['zhong_biao_jin_e']?:'',
                'dai_li_ji_gou_ming_cheng' => $requestData['dai_li_ji_gou_ming_cheng']?:'',
                'dai_li_ji_gou_di_zhi' => $requestData['dai_li_ji_gou_di_zhi']?:'',
                'dai_li_ji_gou_lian_xi_ren' => $requestData['dai_li_ji_gou_lian_xi_ren']?:'',
                'dai_li_ji_gou_lian_xi_dian_hua' => $requestData['dai_li_ji_gou_lian_xi_dian_hua']?:'',
                'ping_gu_zhuan_jia' => $requestData['ping_gu_zhuan_jia']?:'',
                'DLSM_UUID' => $requestData['DLSM_UUID']?:'',
                'url' => $requestData['url']?:'',
                'corexml' => $requestData['corexml']?:'',
                'updated_at' => $requestData['updated_at']?:'',
                'source' => $requestData['source']?:'',
           ])->save();

        } catch (\Throwable $e) {
            return CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'failed',
                    '$requestData' => $requestData,
                    'msg' => $e->getMessage(),
                ])
            );
        }
        return $res;
    }


    public static function findAllByCondition($whereArr){
        $res =  TmpInfo::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = TmpInfo::findById($id);

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
        $model = TmpInfo::create()
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
        $model = TmpInfo::create();
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
        $res =  TmpInfo::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByName($name){
        $res =  TmpInfo::create()
            ->where('name',$name)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = TmpInfo::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `tmp_info` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }


}
