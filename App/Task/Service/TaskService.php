<?php

namespace App\Task\Service;

use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;
use EasySwoole\EasySwoole\Task\TaskManager;

class TaskService extends ServiceBase
{
    use Singleton;

    function create($class, $async = 'async')
    {
        $task = TaskManager::getInstance();

        return $task->$async($class);
    }
}
