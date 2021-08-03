<?php

namespace App\HttpController\Business\Admin\GroceryStore;

use wanghanwanghan\someUtils\control;

class GroceryStoreController extends GroceryStoreBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function wuliuNode(): bool
    {
        $tmp = $this->request()->getRequestParam('tmp') ?? 1;

        $menu = [
            ['id' => 1, 'pid' => 0, 'label' => '您要查询哪个行业？'],
            ['id' => 2, 'pid' => 1, 'label' => '物流'],
            ['id' => 3, 'pid' => 2, 'label' => '企业名称中含有？'],
            ['id' => 4, 'pid' => 3, 'label' => '物流'],
            ['id' => 5, 'pid' => 3, 'label' => '货运'],
            ['id' => 6, 'pid' => 3, 'label' => '普通货运'],
            ['id' => 7, 'pid' => 2, 'label' => '经营范围属于？'],
            ['id' => 8, 'pid' => 7, 'label' => '普通货运'],
            ['id' => 9, 'pid' => 7, 'label' => '不普通货运'],
        ];

        $res = [];

        control::traverseMenu($menu, $res);

        return $this->writeJson(200, null, $res);
    }


}