<?php

namespace App\HttpController\Models\RDS3\HdSaic;

use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataQueue;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\CreateConf;

class ZhaoTouBiaoAll extends ModelBase
{
    protected $tableName = 'zhao_tou_biao_all';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_zhao_tou_biao');
    }

    public static function findById($id){
        $res =  ZhaoTouBiaoAll::create()
            ->where('id',$id)
            ->get();
        return $res;
    }



    public static function findByConditionV2($whereArr,$page){
        $model = ZhaoTouBiaoAll::create();
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
        $model = ZhaoTouBiaoAll::create();
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

    // 用完今日余额的
    public static function findBySql($where){
        $Sql = " select *   from   `zhao_tou_biao_all` 
                $where " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_zhao_tou_biao'));
        return $data;
    }

}
