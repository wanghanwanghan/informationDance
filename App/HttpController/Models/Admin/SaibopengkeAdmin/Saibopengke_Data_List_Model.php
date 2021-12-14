<?php

namespace App\HttpController\Models\Admin\SaibopengkeAdmin;

use App\HttpController\Models\ModelBase;

class Saibopengke_Data_List_Model extends ModelBase
{
    protected $tableName = 'saibopengke_data_list';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    function table_sch()
    {

    }
}
