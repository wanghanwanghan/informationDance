<?php

namespace App\Task;

use App\HttpController\Service\CreateConf;

class TaskBase
{
    public $qccUrl;
    public $fahaiList;
    public $fahaiDetail;

    function __construct()
    {
        $this->qccUrl = CreateConf::getInstance()->getConf('qichacha.baseUrl');
        $this->fahaiList = CreateConf::getInstance()->getConf('fahai.listBaseUrl');
        $this->fahaiDetail = CreateConf::getInstance()->getConf('fahai.detailBaseUrl');

        return true;
    }
}
