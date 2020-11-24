<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class InvoiceIn extends ModelBase
{
    protected $tableName = 'information_dance_invoice_in';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
