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
            ['id' => 4, 'pid' => 3, 'label' => '物流'],
            ['id' => 5, 'pid' => 3, 'label' => '货运'],
            ['id' => 6, 'pid' => 3, 'label' => '普通货运'],
            ['id' => 7, 'pid' => 2, 'label' => '经营范围属于？'],
            ['id' => 8, 'pid' => 7, 'label' => '普通货运'],
            ['id' => 9, 'pid' => 7, 'label' => '不普通货运'],
            ['id' => 10, 'pid' => 2, 'label' => '行业类型是？'],
            ['id' => 11, 'pid' => 10, 'label' => '交通运输、仓储和邮政业'],
            ['id' => 12, 'pid' => 11, 'label' => '铁路运输业'],
            ['id' => 13, 'pid' => 11, 'label' => '道路运输业'],
            ['id' => 14, 'pid' => 11, 'label' => '水上运输业'],
            ['id' => 15, 'pid' => 11, 'label' => '航空运输业'],
            ['id' => 16, 'pid' => 11, 'label' => '管道运输业'],
            ['id' => 17, 'pid' => 11, 'label' => '装卸搬运和运输代理业'],
            ['id' => 18, 'pid' => 11, 'label' => '仓储业'],
            ['id' => 19, 'pid' => 11, 'label' => '邮政业'],
            ['id' => 20, 'pid' => 12, 'label' => '铁路运输辅助活动'],
            ['id' => 21, 'pid' => 12, 'label' => '铁路旅客运输'],
            ['id' => 22, 'pid' => 12, 'label' => '铁路货物运输'],
            ['id' => 23, 'pid' => 20, 'label' => '客运火车站'],
            ['id' => 24, 'pid' => 20, 'label' => '货运火车站'],
            ['id' => 25, 'pid' => 20, 'label' => '其他铁路运输辅助活动'],
            ['id' => 26, 'pid' => 13, 'label' => '城市公共交通运输'],
            ['id' => 27, 'pid' => 13, 'label' => '道路运输辅助活动'],
            ['id' => 28, 'pid' => 13, 'label' => '公路旅客运输'],
            ['id' => 29, 'pid' => 13, 'label' => '道路货物运输'],
            ['id' => 30, 'pid' => 26, 'label' => '公共电汽车客运'],
            ['id' => 31, 'pid' => 26, 'label' => '城市轨道交通'],
            ['id' => 32, 'pid' => 26, 'label' => '出租车客运'],
            ['id' => 33, 'pid' => 26, 'label' => '其他城市公共交通运输'],
            ['id' => 34, 'pid' => 27, 'label' => '客运汽车站'],
            ['id' => 35, 'pid' => 27, 'label' => '公路管理与养护'],
            ['id' => 36, 'pid' => 27, 'label' => '其他道路运输辅助活动'],
            ['id' => 37, 'pid' => 14, 'label' => '水上旅客运输'],
            ['id' => 38, 'pid' => 14, 'label' => '水上货物运输'],
            ['id' => 39, 'pid' => 14, 'label' => '水上运输辅助活动'],
            ['id' => 40, 'pid' => 37, 'label' => '海洋旅客运输'],
            ['id' => 41, 'pid' => 37, 'label' => '内河旅客运输'],
            ['id' => 42, 'pid' => 37, 'label' => '客运轮渡运输'],
            ['id' => 43, 'pid' => 38, 'label' => '远洋货物运输'],
            ['id' => 44, 'pid' => 38, 'label' => '沿海货物运输'],
            ['id' => 45, 'pid' => 38, 'label' => '内河货物运输'],
            ['id' => 46, 'pid' => 39, 'label' => '客运港口'],
            ['id' => 47, 'pid' => 39, 'label' => '货运港口'],
            ['id' => 48, 'pid' => 39, 'label' => '其他水上运输辅助活动'],
        ];

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
            ['id' => 10, 'pid' => 2, 'label' => '行业类型是？'],
        ];

        $res = [];

        $this->traverseMenu($menu, $res, 0);

        return $this->writeJson(200, null, $res);
    }


}