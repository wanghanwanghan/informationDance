<?php

namespace App\HttpController\Service\RequestUtils;

use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;

class LimitService extends ServiceBase
{
    use Singleton;

    //令牌桶最大个数
    private $maxNum = 200;

    //每秒加几个进去
    private $addNum = 1;

    function create($max, $add)
    {
        $this->maxNum = $max;
        $this->addNum = $add;
        return true;
    }






}
