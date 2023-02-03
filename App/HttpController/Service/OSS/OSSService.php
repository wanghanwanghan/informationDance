<?php

namespace App\HttpController\Service\OSS;

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\ServiceBase;

class OSSService extends ServiceBase
{
    private $ali_oss_cli;

    function __construct(string $internal = '')
    {
        parent::__construct();

        $internal === 'internal' ?
            $endpoint = 'oss-cn-beijing-internal.aliyuncs.com' :
            $endpoint = 'oss-cn-beijing.aliyuncs.com';

        $config = new \EasySwoole\Oss\AliYun\Config([
            'accessKeyId' => CreateConf::getInstance()->getConf('env.aliAk'),
            'accessKeySecret' => CreateConf::getInstance()->getConf('env.aliSk'),
            'endpoint' => $endpoint,
        ]);

        $this->ali_oss_cli = new \EasySwoole\Oss\AliYun\OssClient($config);
    }

    function doUploadFile(string $bucket, string $storeName, string $path, int $timeout, ?array $option = null): ?string
    {
        $this->ali_oss_cli->uploadFile($bucket, $storeName, $path, $option);

        return $this->ali_oss_cli->signUrl($bucket, $storeName, $timeout);
    }

}
