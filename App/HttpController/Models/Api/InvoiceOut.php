<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class InvoiceOut extends ModelBase
{
    protected $tableName = 'information_dance_invoice_out';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
