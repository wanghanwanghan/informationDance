<?php

use App\Command\TestCommand;
use EasySwoole\EasySwoole\Command\CommandContainer;

//bootstrap 允许在框架未初始化之前，初始化其他业务

//自定义命令
CommandContainer::getInstance()->set(new TestCommand());




