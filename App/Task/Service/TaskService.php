<?php

namespace App\Task\Service;

use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;
use EasySwoole\EasySwoole\Task\TaskManager;

class TaskService extends ServiceBase
{
    use Singleton;

    function create($class, $type = 'async')
    {
        $task = TaskManager::getInstance();

        //异步返回taskId，同步返回运行结果，sync是同步
        //class可以是类可以是闭包
        return $type === 'async' ? $task->$type($class) : $task->$type($class, 86400);
    }
}
