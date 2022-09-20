<?php

namespace App\HttpController\Models\RDS3\HdSaic;

use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataQueue;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\CreateConf;

class CodeEx02 extends ModelBase
{
    protected $tableName = 'code_ex02';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_hd_saic');
    }

    public static function findById($id){
        $res =  CodeEx02::create()
            ->where('id',$id)
            ->get();
        return $res;
    }

    static function invalidCodeMap(){

        return [
            '2'=>'吊销',
            '21'=>'吊销，未注销',
            '22'=>'吊销，已注销',
            '3'=>'注销',
            '4'=>'迁出',
            '5'=>'撤销',
            '8'=>'停业',
            '9_01'=>'撤销',
            '9_04'=>'清算中',
            '9_06'=>'拟注销',
            '30'=>'正在注销',
        ];

    }

    public static function findByCode($UNISCID){
        $res =  CodeEx02::create()
            ->where('code',$UNISCID)
            ->get();
        return $res;
    }

    public static function findCancelDateByCode($UNISCID){
        $res =  self::findByCode($UNISCID);
        return $res?$res->getAttr('CANDATE'):'';
    }



    public static function findByConditionV2($whereArr,$page){
        $model = CodeEx02::create();
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
        $model = CodeEx02::create();
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
