<?php

namespace App\HttpController\Service\Queue;

use wanghanwanghan\someUtils\control;

class QueueConf
{
    private $jobId;
    private $jobData;
    private $queueListKey;
    private $execTime;//执行时间

    function __construct()
    {
        $this->jobId = time() . control::getUuid(10);
        $this->jobData = [];
        $this->queueListKey = 'defaultQueueListKey';
        $this->execTime = time();
    }

    function setJobId(string $jobId): QueueConf
    {
        $this->jobId = $jobId;
        return $this;
    }

    function getJobId(): string
    {
        return $this->jobId;
    }

    function setJobData(array $data): QueueConf
    {
        $this->jobData = $data;
        return $this;
    }

    function getJobData(): array
    {
        return $this->jobData;
    }

    function setQueueListKey(string $queueListKey): QueueConf
    {
        $this->queueListKey = $queueListKey;
        return $this;
    }

    function getQueueListKey(): string
    {
        return $this->queueListKey;
    }

    function setExecTime(int $time): QueueConf
    {
        $this->execTime = $time;
        return $this;
    }

    function getExecTime(): int
    {
        return $this->execTime;
    }


}
