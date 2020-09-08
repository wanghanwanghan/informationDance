<?php

namespace App\Event;

use EasySwoole\Component\Container;

class EventBase extends Container
{
    //自定义的
    function initSet(...$args)
    {
        return true;
    }

    function initHook(...$args)
    {
        return true;
    }
}