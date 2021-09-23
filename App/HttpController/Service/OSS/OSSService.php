<?php

namespace App\HttpController\Service\OSS;

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;

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

    function doUploadFile(string $bucket, string $storeName, string $path, int $timeout, ?array $option = null): ?string
    {
        $this->ali_oss_cli->uploadFile($bucket, $storeName, $path, $option);

        return $this->ali_oss_cli->signUrl($bucket, $storeName, $timeout);
    }

    function createObjectDir(string $bucket, string $dirName, ?array $option = null): OSSService
    {
        $this->ali_oss_cli->createObjectDir($bucket, $dirName, $option);
        return $this;
    }

}
