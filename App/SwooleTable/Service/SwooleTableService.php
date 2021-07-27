<?php

namespace App\SwooleTable\Service;

use App\SwooleTable\SwooleTableBase;
use EasySwoole\Component\Singleton;
use EasySwoole\Component\TableManager;
use Swoole\Table;

class SwooleTableService extends SwooleTableBase
{
    use Singleton;

    private $tableInfo = [
        [
            'name' => 'test_table',
            'col' => [
                'id' => ['type' => Table::TYPE_INT, 'size' => 8],//字节
                'name' => ['type' => Table::TYPE_STRING, 'size' => 64],//字节
                'price' => ['type' => Table::TYPE_FLOAT, 'size' => 8],//字节
            ],
            'size' => 1024,//最大行数
        ],
    ];

    //mainServerCreate
    function create(): bool
    {
        foreach ($this->tableInfo as $oneTable) {
            TableManager::getInstance()->add($oneTable['name'], $oneTable['col'], $oneTable['size']);
        }

        return true;
    }

    function getTableByName(string $name): ?Table
    {
        return TableManager::getInstance()->get($name);
    }


}
