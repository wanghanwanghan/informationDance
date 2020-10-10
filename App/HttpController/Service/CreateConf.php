<?php

namespace App\HttpController\Service;

use EasySwoole\Component\Singleton;

class CreateConf extends ServiceBase
{
    use Singleton;

    private $isCreate = false;

    private $yaConf = [];

    //只在mainServerCreate调用
    function create($dir)
    {
        if ($this->isCreate) return true;

        $iniDir = $dir . DIRECTORY_SEPARATOR . 'Yaconf' . DIRECTORY_SEPARATOR;

        if ($dh = opendir($iniDir))
        {
            while (false !== ($file = readdir($dh)))
            {
                if (strpos($file, '.ini') !== false)
                {
                    $key = current(explode('.', $file));

                    foreach (\Yaconf::get($key) as $k => $conf)
                    {
                        $this->yaConf[$key][$k] = $conf;
                    }
                }
            }
        }

        closedir($dh);

        $this->isCreate = true;

        return true;
    }

    //获取配置
    function getConf(string $path)
    {
        $path = explode('.',$path);

        return isset($this->yaConf[reset($path)][end($path)]) ? $this->yaConf[reset($path)][end($path)] : null;
    }


}
