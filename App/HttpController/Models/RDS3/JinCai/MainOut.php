<?php

namespace App\HttpController\Models\RDS3\JinCai;

use App\HttpController\Models\ModelBase;

class MainOut extends ModelBase
{
    protected $tableName = 'invoice_main_output';

    protected $autoTimeStamp = false;

    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = 'jin_cai';
    }

}
