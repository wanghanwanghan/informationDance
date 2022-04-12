<?php

namespace App\Task\TaskList\EntDbTask;

use App\HttpController\Models\EntDb\EntDbEnt;
use App\Task\TaskBase;
use EasySwoole\Task\AbstractInterface\TaskInterface;

class insertEnt extends TaskBase implements TaskInterface
{
    public $entName;
    public $code;

    function __construct($entName = '', $code = '')
    {
        parent::__construct();

        $this->entName = trim($entName);
        $this->code = trim($code);

        return true;
    }

    function run(int $taskId, int $workerIndex): bool
    {
        if (empty($this->entName)) return true;

        //插入公司名称
        try {
            $info = EntDbEnt::create()->where('name', $this->entName)->get();
            if (!empty($info)) return true;
            EntDbEnt::create()->data([
                'name' => $this->entName,
                'code' => $this->code,
            ])->save();
        } catch (\Throwable $e) {

        }

        return true;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }
}
