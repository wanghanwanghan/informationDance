<?php

namespace App\Task;

class TaskBase
{
    public $qccUrl;
    public $fahaiList;
    public $fahaiDetail;

    function __construct()
    {
        $this->qccUrl = \Yaconf::get('qichacha.baseUrl');
        $this->fahaiList = \Yaconf::get('fahai.listBaseUrl');
        $this->fahaiDetail = \Yaconf::get('fahai.detailBaseUrl');

        return true;
    }
}
