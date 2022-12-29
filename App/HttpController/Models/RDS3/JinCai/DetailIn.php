<?php

namespace App\HttpController\Models\RDS3\JinCai;

use App\HttpController\Models\ModelBase;

class DetailIn extends ModelBase
{
    protected $tableName = 'invoice_detail_input';

    protected $autoTimeStamp = false;

    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = 'jin_cai';
    }

}
