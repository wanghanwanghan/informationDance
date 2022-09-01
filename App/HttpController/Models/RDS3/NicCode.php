<?php

namespace App\HttpController\Models\RDS3;

use App\HttpController\Models\ModelBase;
use App\HttpController\Models\RDS3\HdSaic\CodeEx02;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;

class NicCode extends ModelBase
{
    protected $tableName = 'nic_code';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_nic_code');
    }


    public static function findById($id){
        $res =  NicCode::create()
            ->where('id',$id)
            ->get();
        return $res;
    }

    public static function findByNicId($id){
        $res =  NicCode::create()
            ->where('nic_id',$id)
            ->get();
        return $res;
    }


    public static function findByNssc($nssc){
        $res =  NicCode::create()
            ->where('nssc',$nssc)
            ->get();
        return $res;
    }

    //直接查  如果查不到  就去掉个零 查父级
    public static function findNICID($code){
        $first = substr( $code, 0, 1 );
        CommonService::getInstance()->log4PHP(json_encode([
            '$first'=>$first,
        ]));
        if(
            is_numeric($first) &&
            $first >0
        ){
            $res = self::findByNicId($code);
            if($res){
                return $res->toArray();
            }
            return [];
        }

       $res = self::findByNssc($code);
        if($res){
            return $res->toArray();
        }

        if(
            substr($code,-1) === '0'
        ){
            $code = substr_replace($code ,"",-1);
        }
        $res = self::findByNssc($code);
        if($res){
            return $res->toArray();
        }
        return [];
    }

}
