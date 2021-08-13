<?php

namespace App\HttpController\Service\JuHe;

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;

class JuHeService extends ServiceBase
{
    use Singleton;

    private $testUrl;
    private $testKey;

    function __construct()
    {
        $this->testUrl = CreateConf::getInstance()->getConf('juhe.testUrl');
        $this->testKey = CreateConf::getInstance()->getConf('juhe.testKey');
        return parent::__construct();
    }

    function test(): ?array
    {
        $res = (new CoHttpClient())
            ->useCache(false)
            ->send($this->testUrl . '?key=' . $this->testKey);

        return is_array($res) ? $res : jsonDecode($res);
    }


}
