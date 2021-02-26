<?php

namespace App\HttpController\Service\LongXin;

use App\HttpController\Service\ServiceBase;

class LongXinService extends ServiceBase
{
    public $rangeArr = [
        ['name' => 'A00', 'range' => [0, 10]],
        ['name' => 'A01', 'range' => [10, 15]],
        ['name' => 'A02', 'range' => [15, 20]],
        ['name' => 'A03', 'range' => [20, 25]],
        ['name' => 'A04', 'range' => [25, 30]],
        ['name' => 'A05', 'range' => [30, 35]],
        ['name' => 'A06', 'range' => [35, 40]],
        ['name' => 'A07', 'range' => [40, 45]],
        ['name' => 'A08', 'range' => [45, 50]],
        ['name' => 'A09', 'range' => [50, 55]],
    ];

    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    function __construct()
    {
        return parent::__construct();
    }

    //二分找区间
    function binaryFind(int $find, int $leftIndex = 0, int $rightIndex = 9): ?array
    {
        if ($leftIndex > $rightIndex) return null;

        $middle = ($leftIndex + $rightIndex) / 2;

        //如果大于第二个数，肯定在右边
        if ($find > $this->rangeArr[$middle]['range'][1]) {
            return $this->binaryFind($find, $middle + 1, $rightIndex);
        }
        //如果小于第一个数，肯定在左边
        if ($find < $this->rangeArr[$middle]['range'][0])
            return $this->binaryFind($find, $leftIndex, $middle - 1);

        return $this->rangeArr[$middle];
    }


}
