<?php

namespace App\HttpController\Models\RDS3\BusinessBase;

use App\HttpController\Models\ModelBase;

class CompanySearchGuest202303 extends ModelBase
{
    protected $tableName = 'company_search_guest_h_202303';

    protected $autoTimeStamp = false;

    function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->connectionName = 'business_base';
    }

}
