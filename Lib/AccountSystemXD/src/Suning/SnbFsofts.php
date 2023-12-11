<?php

namespace AccountSystemXD\Suning;

use AccountSystemXD\Helper\Helper;

class SnbFsofts
{
    private static $instance;
    private $obj;

    static function getInstance(...$args): SnbFsofts
    {
        if (!isset(self::$instance)) {
            self::$instance = new static(...$args);
        }

        return self::$instance;
    }

    function setObject(SuningBank $obj): SnbFsofts
    {
        $this->obj = $obj;
        return $this;
    }

    // 企业绑卡开户 艹这么多参数
    function enterpriseOpen()
    {
        $transCode = 'snb.steward.account.enterprise.open';


    }

    // 文件流上传 一张一张传 文件绝对路径 设置苏宁端新文件名 法人身份证 法人名称 文件类型(word) 自定义流水号和 serialNo 对应
    function fileStreamUpload(string $filepath, string $newName, string $no, string $name, string $type, string $channelSerialNo)
    {
        $transCode = 'snb.fsofts.fileStream.upload';
        !empty($channelSerialNo) ?: $channelSerialNo = 'mrxd' . Helper::getInstance()->getMicroTime();

        $file = file_get_contents($filepath);

        $payload = [
            'merchantId' => $this->obj->merchantId,
            'sceneCode' => '1102',// 场景码 此函数固定的
            'fileDigestMap' => [
                'file_0' => md5($file),// 文件摘要 MD5
                'file_0_type' => $type ?? 'Z201',// 营业执照
            ],
            'extendParam' => [
                'legalPersonIdNo' => $no ?? '130625198801010336',
                'legalPersonName' => $name ?? '每日信动法人一',
            ],
        ];

        $public = [
            'file_0' => new \CURLFile(realpath($filepath), '', $newName),
            'channelSerialNo' => $channelSerialNo,// 流水号
            'transCode' => $transCode,
            'channelId' => $this->obj->channelId,
        ];

        return $this->obj->setParams($payload, $public)->send($transCode);
    }
}