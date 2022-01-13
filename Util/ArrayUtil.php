<?php

/**
 * 将对象改为由某一个唯一值为索引的数组
 * @param $objs
 * @param $key
 * @return array
 */
function getArrByKey($objs,$key): array
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