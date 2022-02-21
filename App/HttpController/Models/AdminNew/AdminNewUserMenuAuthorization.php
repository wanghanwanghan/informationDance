<?php

namespace App\HttpController\Models\AdminNew;

use App\HttpController\Models\ModelBase;

class AdminNewUserMenuAuthorization extends ModelBase
{
    protected $tableName = 'admin_new_user_menu_authorization';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}
