<?php

namespace App\Command;

use EasySwoole\EasySwoole\Command\CommandInterface;

class CommandBase implements CommandInterface
{
    //可以用来初始化
    function commendInit(...$args)
    {
        return true;
    }

    public function commandName(): string
    {
        return '';
    }

    public function exec(array $args): ?string
    {
        return null;
    }

    public function help(array $args): ?string
    {
        return null;
    }
}