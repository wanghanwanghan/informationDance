<?php

namespace App\Task;

class TaskBase
{
    public $qccUrl;
    public $fahaiList;

    function __construct()
    {
        $this->qccUrl = \Yaconf::get('qichacha.baseUrl');
        $this->fahaiList = \Yaconf::get('fahai.listBaseUrl');

        return true;
    }
}
