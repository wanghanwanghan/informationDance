<?php

namespace App\Command;

class TestCommand extends CommandBase
{
    public function commandName(): string
    {
        return 'test';
    }

    //php easyswoole test
    public function exec(array $args): ?string
    {
        parent::commendInit();

        return 'this is exec' . PHP_EOL;
    }

    //php easyswoole help test
    public function help(array $args): ?string
    {
        return 'this is help' . PHP_EOL;
    }
}