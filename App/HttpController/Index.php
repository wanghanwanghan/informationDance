<?php

namespace App\HttpController;

use EasySwoole\Http\AbstractInterface\Controller;

class Index extends Controller
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //递归处理数组数据，是空的变成-
    function handleResult($result, $type = '-')
    {
        if (!is_array($result)) return $result;

        foreach ($result as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->handleResult($value, $type);
            } else {
                if (trim($value) === '' || trim($value) === null)
                    $result[$key] = $type;
            }
        }

        return $result;
    }

    function index() {}
}