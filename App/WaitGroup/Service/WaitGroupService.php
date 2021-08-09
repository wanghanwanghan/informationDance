<?php

namespace App\WaitGroup\Service;

use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;
use EasySwoole\Component\WaitGroup;

class WaitGroupService extends ServiceBase
{
    use Singleton;

    function create(): WaitGroup
    {
        return new WaitGroup();
    }

}
