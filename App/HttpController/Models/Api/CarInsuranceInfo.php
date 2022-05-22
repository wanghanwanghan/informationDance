<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class CarInsuranceInfo extends ModelBase
{
    protected $tableName = 'car_insurance_info';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}