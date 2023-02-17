<?php

namespace App\HttpController\Models\Provide;

use App\HttpController\Models\Api\User;
use App\HttpController\Models\ModelBase;

class RequestUserInfo extends ModelBase
{
    protected $tableName = 'information_dance_request_user_info';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    public static function getListByIds($ids){
        if(empty($ids)) return [];
        return self::create()->where('id in ('.implode(',',$ids).')')->all();
    }

    public static function findById($id){
        $res =  RequestUserInfo::create()
            ->where('id',$id)
            ->get();
        return $res;
    }

    public static function findByConditionWithCountInfo($whereArr,$page=1,$limit=20){
        $model = RequestUserInfo::create()
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
