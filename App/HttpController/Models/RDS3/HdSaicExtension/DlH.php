<?php

namespace App\HttpController\Models\RDS3\HdSaicExtension;

use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataQueue;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\CreateConf;

class DlH extends ModelBase
{
    protected $tableName = 'dl_h';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_hd_saic_extension');
    }

    public static function findByCompanyidId($id){
        $res =  DlH::create()
            ->where('companyid',$id)
            ->get();
        return $res;
    }

    public static function findByCode($UNISCID){
        $res =  DlH::create()
            ->where('UNISCID',$UNISCID)
            ->get();
        return $res;
    }

    public static function findCancelDateByCode($UNISCID){
        $res =  self::findByCode($UNISCID);
        return $res?$res->getAttr('CANDATE'):'';
    }

    public static function findByConditionV2($whereArr,$page){
        $model = DlH::create();
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
    public static function findByConditionV3($whereArr){
        $model = DlH::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        $model
            ->withTotalCount();

        $res = $model->all();

        $total = $model->lastQueryResult()->getTotalCount();
        return [
            'data' => $res,
            'total' =>$total,
        ];
    }
}