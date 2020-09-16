<?php

namespace App\Csp\Service;

use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Csp;
use EasySwoole\Component\Singleton;

class CspService extends ServiceBase
{
    use Singleton;

    //创建csp对象
    function create($size = 8): Csp
    {
        return new Csp($size);
    }

    //执行csp里的内容
    function exec(Csp $csp, $timeout = 5)
    {
        return $csp->exec($timeout);
    }

}
