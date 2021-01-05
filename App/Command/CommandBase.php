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

    function commandName(): string
    {
        return '';
    }

    function exec(array $args): ?string
    {
        return null;
    }

    function help(array $args): ?string
    {
        return null;
    }
}