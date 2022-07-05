<?php

namespace App\HttpController\Models\RDS3;

use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataQueue;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;

class CompanyInvestor extends ModelBase
{
    protected $tableName = 'company_investor';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    static  $investor_type_zi_ran_ren = 1;
    static  $investor_type_zi_ran_ren_cname =  '自然人';

    static  $investor_type_fei_zi_ran_ren = 2;
    static  $investor_type_fei_zi_ran_ren_cname =  '非自然人';


    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_prism1');
    }



    public static function findAllByCondition($whereArr){
        $res =  CompanyInvestor::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function getInvestorName($investorId,$type){
        //自然人
        if(CompanyInvestor::$investor_type_zi_ran_ren == $type){
            $tmpRes = Human::findById($investorId)->toArray();
        }

        //非自然人
        if(CompanyInvestor::$investor_type_fei_zi_ran_ren == $type){
            $tmpRes = Company::findById($investorId)->toArray();
        }

        return $tmpRes['name'];
    }


    public static function findByConditionWithCountInfo($whereArr,$page){
        $model = CompanyInvestor::create()
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
        $model = CompanyInvestor::create();
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
        $res =  CompanyInvestor::create()
            ->where('id',$id)
            ->get();
        return $res;
    }

    public static function findByCompanyId($company_id){
        $res =  CompanyInvestor::create()
            ->where('company_id',$company_id)
            ->all();
        return $res;
    }

}
