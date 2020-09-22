<?php

namespace App\Task;

class TaskBase
{
    public $qccUrl;

    function __construct()
    {
        $this->qccUrl = \Yaconf::get('qichacha.baseUrl');

        return true;
    }
}
