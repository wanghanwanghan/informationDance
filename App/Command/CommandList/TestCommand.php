<?php

namespace App\Command\CommandList;

use App\Command\CommandBase;

class TestCommand extends CommandBase
{
    function commandName(): string
    {
        return 'test';
    }

    //php easyswoole test
    function exec(array $args): ?string
    {
        parent::commendInit();

        co::sleep(5);

        return 'this is exec' . PHP_EOL;
    }

    //php easyswoole help test
    function help(array $args): ?string
    {
        return 'this is help' . PHP_EOL;
    }
}