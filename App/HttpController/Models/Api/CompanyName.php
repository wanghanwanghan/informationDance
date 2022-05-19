<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class CompanyName extends ModelBase
{
    protected $tableName = 'company_name_0';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    static $tablesNums = 7;
    static function getAllTables()
    {
        $tables = [];
        for($i=0; $i <= (self::$tablesNums -1); $i++){
            $tables[] = 'company_name_'.$i;
        }
        return $tables;
    } 

}
