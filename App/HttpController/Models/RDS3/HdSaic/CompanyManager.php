<?php

namespace App\HttpController\Models\RDS3\HdSaic;

use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataQueue;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\CreateConf;

class CompanyManager extends ModelBase
{
    protected $tableName = 'company_liquidation';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_hd_saic');
    }

    public static function findById($id){
        $res =  CompanyManager::create()
            ->where('id',$id)
            ->get();
        return $res;
    }

    public static function findByCompanyId($id){
        $res =  CompanyManager::create()
            ->where('companyid',$id)
            ->get();
        return $res;
    }

    public static function findByName($name){
        $res =  CompanyManager::create()
            ->where('ENTNAME',$name)
            ->all();
        return $res;
    }

    public static function findCancelDateByCode($UNISCID){
        $res =  self::findByCode($UNISCID);
        return $res?$res->getAttr('CANDATE'):'';
    }



    public static function findByConditionV2($whereArr,$page){
        $model = CompanyManager::create();
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
        $model = CompanyManager::create();
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
