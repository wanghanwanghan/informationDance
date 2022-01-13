<?php

//自定义命令
use App\Command\CommandList\TestCommand;
use EasySwoole\EasySwoole\Command\CommandContainer;

CommandContainer::getInstance()->set(new TestCommand());

//******************注册常用全局函数******************

/**
 * 将对象改为由某一个唯一值为索引的数组
 * @param $objs
 * @param $key
 * @return array
 */
function getArrByKey($objs,$key)
{
    if(empty($objs)){
        return [];
    }
    $arr = [];
    foreach (obj2Arr($objs) as $item) {
        $arr[$item[$key]] = $item;
    }
    return $arr;
}