<?php

namespace App\HttpController\Service;

use EasySwoole\Component\Singleton;

class CreateDefine extends ServiceBase
{
    use Singleton;

    //只能在mainServerCreate中用
    public function CreateDefine($root)
    {
        //用来做str_replace的
        define('ROOT_PATH',$root);

        define('STATIC_PATH',$root.DIRECTORY_SEPARATOR.'Static'.DIRECTORY_SEPARATOR);

        define('LOG_PATH',STATIC_PATH.'Log'.DIRECTORY_SEPARATOR);

        define('AVATAR_PATH',STATIC_PATH.'Image'.DIRECTORY_SEPARATOR.'Avatar'.DIRECTORY_SEPARATOR);

        return true;
    }
}
