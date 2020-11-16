<?php

namespace App\HttpController\Service\Queue;

use wanghanwanghan\someUtils\control;

class QueueConf
{
    private $jobId;
    private $jobData;
    private $queueListKey = 'defaultQueueListKey';

    function setJobId($id = ''): QueueConf
    {
        !empty($id) ?: $id = control::getUuid(16);

        !empty($this->jobId) ?: $this->jobId = $id;

        return $this;
    }

    function setJobData(array $data = []): QueueConf
    {
        $this->jobData = $data;

        return $this;
    }

    function setQueueListKey(string $listKey = ''): QueueConf
    {
        empty($listKey) ?: $this->queueListKey = $listKey;

        return $this;
    }

    function getQueueListKey(): string
    {
        return $this->queueListKey;
    }

    function getJobData(): array
    {
        $this->jobData['jobId'] = $this->setJobId()->jobId;

        return $this->jobData;
    }


}
