<?php

namespace App\HttpController\Service\OSS;

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;
use EasySwoole\Oss\AliYun\OssClient;

class OSSService extends ServiceBase
{
    use Singleton;

    private $ali_oss_cli;

    function __construct()
    {
        parent::__construct();

        $config = new \EasySwoole\Oss\AliYun\Config([
            'accessKeyId' => CreateConf::getInstance()->getConf('env.aliAk'),
            'accessKeySecret' => CreateConf::getInstance()->getConf('env.aliSk'),
            'endpoint' => 'oss-cn-beijing.aliyuncs.com',
        ]);

        $this->ali_oss_cli = new \EasySwoole\Oss\AliYun\OssClient($config);
    }

    function getAliCli(): OssClient
    {
        // $client->putObject('invoice-mrxd', 'test', __FILE__);
        return $this->ali_oss_cli;
    }

}
