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

    function traverseMenu($menus, &$result, $pid): bool
    {
        foreach ($menus as $child_menu) {
            if ($child_menu['pid'] == $pid) {
                $item = [
                    'id' => $child_menu['id'],
                    'label' => $child_menu['label'],
                    'cond' => $child_menu['cond'],
                    'children' => []
                ];
                $this->traverseMenu($menus, $item['children'], $child_menu['id']);
                $result[] = $item;
            }
        }
        return true;
    }

    function wuliuNode(): bool
    {
        $tmp = $this->request()->getRequestParam('tmp') ?? 1;

        $menu = [
            ['id' => 1, 'pid' => 0, 'label' => '您要查询哪个行业？'],
            ['id' => 2, 'pid' => 1, 'label' => '物流'],
            ['id' => 3, 'pid' => 2, 'label' => '企业名称中含有？'],
            ['id' => 4, 'pid' => 3, 'label' => '物流', 'cond' => 'basic_entname|物流'],
            ['id' => 5, 'pid' => 3, 'label' => '货运', 'cond' => 'basic_entname|货运'],
            ['id' => 6, 'pid' => 3, 'label' => '普通货运', 'cond' => 'basic_entname|普通货运'],
            ['id' => 7, 'pid' => 2, 'label' => '经营范围含有？'],
            ['id' => 8, 'pid' => 7, 'label' => '普通货运', 'cond' => 'basic_opscope|普通货运'],
            ['id' => 9, 'pid' => 7, 'label' => '不普通货运', 'cond' => 'basic_opscope|不普通货运'],
            ['id' => 10, 'pid' => 2, 'label' => '经营状态是？'],
            ['id' => 11, 'pid' => 10, 'label' => '开业', 'cond' => 'basic_status|1'],
            ['id' => 12, 'pid' => 10, 'label' => '吊销', 'cond' => 'basic_status|2'],
            ['id' => 13, 'pid' => 10, 'label' => '注销', 'cond' => 'basic_status|3'],

            ['id' => 14, 'pid' => 2, 'label' => '行业类型是？'],
        ];

        $res = [];

        $this->traverseMenu($menu, $res, 0);

        return $this->writeJson(200, null, $res);
    }


}