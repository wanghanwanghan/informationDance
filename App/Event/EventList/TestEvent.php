<?php

namespace App\Event\EventList;

use App\Event\EventBase;
use EasySwoole\Component\Singleton;

class TestEvent extends EventBase
{
    use Singleton;

    private $eventList = [];

    //在框架的initialize事件中进行注册事件
    function set($key, $item)
    {
        parent::initSet();

        if (is_callable($item)) {
            $this->eventList[] = $key;
            return parent::set($key, $item);
        } else {
            return false;
        }
    }

    //获取已经注册的事件列表
    function getEventList(): array
    {
        return $this->eventList;
    }

    //全局调用，执行事件
    function hook($event, ...$arg)
    {
        parent::initHook();

        $call = $this->get($event);

        if (is_callable($call)) {
            return call_user_func($call, ...$arg);
        } else {
            return null;
        }
    }
}