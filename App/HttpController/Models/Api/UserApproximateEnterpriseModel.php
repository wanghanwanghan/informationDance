<?php

namespace App\HttpController\Models\Api;

use App\ElasticSearch\Model\Company;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\ModelBase;
use App\HttpController\Models\MRXD\XinDongKeDongAnalyzeList;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\XinDong\XinDongService;

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

    public  function findByConditionV2($uid,$whereArr,$page,$size){
        $model = UserApproximateEnterpriseModel::create()->addSuffix($uid);
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
    public  function findBySqlV2($where,$page,$size){
        $Sql = " select count(1) as total  
                            from  
                        `".$this->tableName."` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));

        $offset = ($page-1)*$size;
        $Sql2 = " select * 
                            from  
                        `".$this->tableName."` 
                            $where 
                            LIMIT  $offset,$size
        " ;
        $data2 = sqlRaw($Sql2, CreateConf::getInstance()->getConf('env.mysqlDatabase'));


        return [
            'total' => $data[0]['total'],
            'data' => $data2
        ];
    }
    public  function deleteByUid($uid){
        $Sql = " Delete QUICK from    `".$this->tableName."`  WHERE userid =   $uid " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));

        return $data;
    }


    public  function searchFromEs($whereArr,$page,$limit){

        //Company::serachFromEs();
        $model = UserApproximateEnterpriseModel::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        $model->page($page,$limit)
            ->order('id', 'DESC')
            ->withTotalCount();

        $res = $model->all();
        foreach ($res as &$data){
            if($data['companyid'] <=0){
                continue;
            }
            $esres = Company::serachFromEs(
                [
                    'companyids' => $data['companyid'],
                    'size' => 1,
                    'page' => 1,
                ]
            );

            //营收规模
            $data['ying_shou_gui_mo'] = $esres['data'][0]['_source']['ying_shou_gui_mo'];
            if($data['ying_shou_gui_mo']){
                $data['ying_shou_gui_mo'] =  XinDongService::mapYingShouGuiMo()[$data['ying_shou_gui_mo']];
            }

            //地域
            $data['DOMDISTRICT'] = $esres['data'][0]['_source']['DOMDISTRICT'];
            if($data['DOMDISTRICT']){
                $data['DOMDISTRICT'] =  CompanyBasic::findRegion($data['DOMDISTRICT'])['fulltitle'];
            }

            //团队规模
            $data['tuan_dui_ren_shu'] = $esres['data'][0]['_source']['tuan_dui_ren_shu'];

            // 营业期限开始日期  OPFROM
            $data['OPFROM'] = $esres['data'][0]['_source']['OPFROM'];

            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    [
                        'serachFromEs' =>  $esres['data'][0]['_source'],
                    ]
                ])
            );

        }

        $total = $model->lastQueryResult()->getTotalCount();
        return [
            'data' => $res,
            'total' =>$total,
        ];
    }


}
