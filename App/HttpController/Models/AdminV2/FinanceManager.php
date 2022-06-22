<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class FinanceManager extends ModelBase
{

    protected $tableName = 'data_example';

    static  $state_init = 1;
    static  $state_init_cname =  '内容生成中';



}
