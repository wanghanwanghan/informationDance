<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\ModelBase;
use App\HttpController\Models\MRXD\XinDongKeDongAnalyzeList;
use App\HttpController\Service\CreateConf;

class UserApproximateEnterpriseModel extends ModelBase
{
    protected $tableName = 'approximateenterprise_';
    protected $autoTimeStamp = false;

    function addSuffix(int $uid): UserApproximateEnterpriseModel
    {
        $suffix = $uid % 3;
        $this->tableName($this->tableName . $suffix);
        return $this;
    }

    public  function findAllByCondition($whereArr){
        $res =  UserApproximateEnterpriseModel::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public  function updateById(
        $id,$data
    ){
        $info = $this->findById($id);
        return $info->update($data);
    }

    public  function findByConditionWithCountInfo($whereArr,$page){
        $model = UserApproximateEnterpriseModel::create()
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

    public  function findByConditionV2($whereArr,$page){
        $model = UserApproximateEnterpriseModel::create();
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

    public  function findById($id){
        $res =  UserApproximateEnterpriseModel::create()
            ->where('id',$id)
            ->get();
        return $res;
    }

    public  function findAllByUserId($userId){
        $res =  UserApproximateEnterpriseModel::create()
            ->where('userid',$userId)
            ->all();
        return $res;
    }



    public  function setData($id,$field,$value){
        $info = UserApproximateEnterpriseModel::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    public  function findBySql($where){
        $Sql = " select *  
                            from  
                        `".$this->tableName."` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }




}
