<?php

namespace App\HttpController\Models\Provide;

use App\HttpController\Models\ModelBase;

class BarchTypeApiRelation extends ModelBase
{
    protected $tableName = 'information_dance_barch_type_api_relation';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    const TRIPARTITE_MAP = [
        '1' => '企查查',
        '2' => '淘数',
        '3' => '西南',
        '4' => '法海',
    ];

    const TYPE_BASE_MAP = [
        '1' => '工商信息',
        '2' => '财务',
        '3' => '司法',
        '4' => '涉税',
        '5' => '知识产权',
        '6' => '金融监管'
    ];

    const KiD_TYPE = [
            ''=>''
    ];
}