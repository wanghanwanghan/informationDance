<?php

namespace App\HttpController\Service\XinDong\Score;

use EasySwoole\Component\Singleton;

class xds
{
    use Singleton;

    public static $a = 1;
    public $b = 1;

    function get()
    {
        self::$a++;
        $this->b++;

        return [self::$a, $this->b];
    }
}
