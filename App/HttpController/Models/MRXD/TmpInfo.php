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
        $res = self::findByName($info['articleId']);
        if($res){
            return  $res->id;
        }
        return TmpInfo::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  TmpInfo::create()->data([
                'page' => $requestData['page']?:'',
                'pathName' => $requestData['pathName']?:'',
                'districtName' => $requestData['districtName']?:'',
                'gpCatalogName' => $requestData['gpCatalogName']?:'',
                'publishDate' => $requestData['publishDate']?:'',
                'procurementMethod' => $requestData['procurementMethod']?:'',
                'articleId' => $requestData['articleId']?:'',
                'siteId' => $requestData['siteId']?:'',
                'gpCatalogType' => $requestData['gpCatalogType']?:'',
                'title' => $requestData['title']?:'',
                'url' => $requestData['url']?:'',
                'real_url' => $requestData['real_url']?:'',
                'jia_fang' => $requestData['jia_fang']?:'',
                'jia_fang_contacts' => $requestData['jia_fang_contacts']?:'',
                'yi_fang' => $requestData['yi_fang']?:'',
                'yi_fang_contacts' => $requestData['yi_fang_contacts']?:'',
                'contact_money' => $requestData['contact_money']?:'',
                'content' => $requestData['content']?:'',
                'remark' => $requestData['remark']?:'',
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

    public static function findByName($articleId){
        $res =  TmpInfo::create()
            ->where('articleId',$articleId)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = TmpInfo::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    public static function findBySql($Sql){

        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }


}
