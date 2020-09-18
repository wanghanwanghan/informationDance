<?php

namespace App\Task\TaskList;

use App\Task\TaskBase;
use EasySwoole\Task\AbstractInterface\TaskInterface;

class TestTask extends TaskBase implements TaskInterface
{
    private $data;

    function __construct($data)
    {
        $this->data = $data;

        return parent::__construct();
    }

    function run(int $taskId, int $workerIndex)
    {

    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }
}
