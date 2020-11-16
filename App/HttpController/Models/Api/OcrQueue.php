<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class OcrQueue extends ModelBase
{
    protected $tableName = 'information_dance_ocr_queue';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
