<?php

use App\Command\CommandList\TestCommand;
use EasySwoole\EasySwoole\Command\CommandContainer;

//bootstrap 允许在框架未初始化之前，初始化其他业务

//自定义命令
CommandContainer::getInstance()->set(new TestCommand());

//******************注册常用全局函数******************

function jsonEncode($target)
{
    return json_encode($target);
}

function jsonDecode($target, $type = true)
{
    return json_decode($target, $type);
}

function obj2Arr($obj)
{
    return json_decode(json_encode($obj), true);
}
