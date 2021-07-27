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
            TableManager::getInstance()
                ->add($oneTable['name'], $oneTable['col'], $oneTable['size']);
        }
        return true;
    }

    function updateOrCreate(string $tableName, array $kv, string $id): ?bool
    {
        $table = $this->getTableByName($tableName);
        if ($table instanceof Table) {
            if ($table->exist($id) === true) {
                $arr = array_merge($table->get($id), $kv);
                return $table->set($id, $arr);
            }
            return $table->set($id, $kv);
        }
        return null;
    }

    function getPaginate(string $tableName, $page = null, $pageSize = 10): ?array
    {
        $table = $this->getTableByName($tableName);
        if ($table instanceof Table) {
            if (!is_numeric($page)) {
                return $this->getAll($tableName);
            } else {
                $page = $page - 0;
                $pageSize = $pageSize - 0;
                $offset = ($page - 1) * $pageSize;
                $i = 0;
                $arr = [];
                foreach ($table as $key => $val) {
                    if ($i >= $offset) {
                        $arr[$key] = $val;
                        $pageSize--;
                        if ($pageSize <= 0) {
                            break;
                        }
                    }
                    $i++;
                }
                return $arr;
            }
        }
        return null;
    }

    function getOne(string $tableName, string $id): ?array
    {
        $table = $this->getTableByName($tableName);
        if ($table instanceof Table && $table->exist($id) === true) {
            return $table->get($id);
        }
        return null;
    }

    function getAll(string $tableName): ?array
    {
        $table = $this->getTableByName($tableName);
        if ($table instanceof Table) {
            $arr = [];
            foreach ($table as $key => $row) {
                $arr[$key] = $row;
            }
            return $arr;
        }
        return null;
    }

    private function getTableByName(string $name): ?Table
    {
        foreach ($this->tableInfo as $oneTable) {
            if ($name === $oneTable['name']) {
                return TableManager::getInstance()->get($name);
            }
        }
        return null;
    }
}
