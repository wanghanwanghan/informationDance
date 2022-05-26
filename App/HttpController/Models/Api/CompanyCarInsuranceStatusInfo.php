<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class CompanyCarInsuranceStatusInfo extends ModelBase
{
    protected $tableName = 'company_car_insurance_status_info';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}