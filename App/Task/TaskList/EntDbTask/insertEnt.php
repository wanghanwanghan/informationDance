<?php

namespace App\Task\TaskList\EntDbTask;

use App\HttpController\Models\EntDb\EntDbEnt;
use App\Task\TaskBase;
use EasySwoole\Task\AbstractInterface\TaskInterface;

class insertEnt extends TaskBase implements TaskInterface
{
    public $entName;

    function __construct($entName = '')
    {
        parent::__construct();

        $this->entName = trim($entName);

        return true;
    }

    function run(int $taskId, int $workerIndex)
    {
        if (empty($this->entName)) return true;

        //插入公司名称
        try {
            $info = EntDbEnt::create()->where('name', $this->entName)->get();
            if (!empty($info)) return true;
            EntDbEnt::create()->data([
                'name' => $this->entName,
            ])->save();
        } catch (\Throwable $e) {

        }

        return true;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }
}
