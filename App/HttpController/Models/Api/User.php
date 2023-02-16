<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\BusinessBase\WechatInfo;
use App\HttpController\Models\ModelBase;
use App\HttpController\Models\MRXD\InformationDanceRequestRecode;

class User extends ModelBase
{
    protected $tableName = 'information_dance_user';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    public static function findById($id){
        $res =  User::create()
            ->where('id',$id)
            ->get();
        return $res;
    }

    public static function findByConditionWithCountInfo($whereArr,$page=1,$limit=20){
        $model = User::create()
            ->where($whereArr)
            ->page($page,$limit)
            ->order('id', 'DESC')
            ->withTotalCount();

        $res = $model->all();

        $total = $model->lastQueryResult()->getTotalCount();
        return [
            'data' => $res,
            'total' =>$total,
        ];
    }

}
