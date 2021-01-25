<?php

namespace App\Task;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;

class TaskBase
{
    public $qccUrl;
    public $fahaiList;
    public $fahaiDetail;

    public $pdf_BigTitle = 17;
    public $pdf_LittleTitle = 14;
    public $pdf_Text = 11;

    function __construct()
    {
        $this->qccUrl = CreateConf::getInstance()->getConf('qichacha.baseUrl');
        $this->fahaiList = CreateConf::getInstance()->getConf('fahai.listBaseUrl');
        $this->fahaiDetail = CreateConf::getInstance()->getConf('fahai.detailBaseUrl');

        return true;
    }

    //判断时间用的，时间类的字段，只显示成 Y-m-d
    function formatDate($str)
    {
        return formatDate($str);
    }

    //判断比例用的，只显示成 10%
    function formatPercent($str)
    {
        return formatPercent($str);
    }









}
