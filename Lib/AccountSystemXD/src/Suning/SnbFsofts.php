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
    function enterpriseOpen(array $arr)
    {
        $version = '2.0';
        $transCode = 'snb.steward.account.enterprise.open';
        !empty($channelSerialNo) ?: $channelSerialNo = 'xd' . Helper::getInstance()->getMicroTime();

        $payload = [
            'merchantId' => $this->obj->merchantId,
            'platformcd' => $this->obj->platformcd,
            'acctName' => trim($arr['acctName']),
            'corpType' => trim($arr['corpType']),// 1是公司 2是个体
            'isBearing' => '2',// 送2，这个功能在规划中，暂时不支持
            'corpName' => trim($arr['corpName']),
            'corpIdKind' => trim($arr['corpIdKind']),// 1-三证三号；2-三证合一；0-个体工商户；默认2
            'idType' => '20',// 20-统一社会信用代码
            'businessLicenceNo' => trim($arr['businessLicenceNo']),// 三证合一时传统一社会信用代码
            'corpContractPhone' => trim($arr['corpContractPhone']),// 法人手机
            'legalPersonName' => trim($arr['legalPersonName']),// 法人名称
            'legalPersonIdNo' => trim($arr['legalPersonIdNo']),// 法人身份证
            'operatorName' => trim($arr['operatorName']),// 经办人
            'operatorIdNo' => trim($arr['operatorIdNo']),// 经办人
            'operatorPhone' => trim($arr['operatorPhone']),// 经办人
            'legalIdType' => '10',
            'operatorIdType' => '10',
            'relCardNbr' => trim($arr['relCardNbr']),// 银行卡号
            'relCardName' => trim($arr['relCardName']),// 银行账户名
            'relCardBankNo' => trim($arr['relCardBankNo']),// 12位人行联行号
            'relCardBankName' => trim($arr['relCardBankName']),// 开户行行名
            'openLicCmii' => '0',// 开户许可证核准号或基本存款账户编号
            'busiLicenceUrl' => trim($arr['busiLicenceUrl']),// 营业执照副本url 文件流上传后的返回内容
            'legalPersonIdUrlFront' => trim($arr['legalPersonIdUrlFront']),// 法人个人信息页url 文件流上传后的返回内容
            'legalPersonIdUrlBack' => trim($arr['legalPersonIdUrlBack']),// 法人国徽页url 文件流上传后的返回内容
            'operatorIdUrlFront' => trim($arr['operatorIdUrlFront']),// 经办人个人信息页url 文件流上传后的返回内容
            'operatorIdUrlBack' => trim($arr['operatorIdUrlBack']),// 经办人国徽页url 文件流上传后的返回内容
            'remark' => trim($arr['remark']),// 备注
            'registeredCapital' => trim($arr['registeredCapital']),// 注册资本 单位元。例如：102382.01 反洗钱
            'legalPersonIdStartDate' => trim($arr['legalPersonIdStartDate']),// 法人证件有效开始日期 反洗钱
            'legalPersonIdEndDate' => trim($arr['legalPersonIdEndDate']),// 如果长期，填写 “长期” 反洗钱
            'operatorIdStartDate' => trim($arr['operatorIdStartDate']),// 经办人证件有效开始日期 反洗钱
            'operatorIdEndDate' => trim($arr['operatorIdEndDate']),// 如果长期，填写 “长期” 反洗钱
            'holdingComName' => trim($arr['holdingComName']),// 控股股东/实际控制人名称 反洗钱
            'holdingComBusiLicNo' => trim($arr['holdingComBusiLicNo']),// 控股股东/实际控制人证件号 反洗钱
            'holdingComStartDate' => trim($arr['holdingComStartDate']),// 控股股东/实际控制人证件开始日期 反洗钱 yyyyMMdd
            'holdingComEndDate' => trim($arr['holdingComEndDate']),// 控股股东/实际控制人证件截止日期 反洗钱 yyyyMMdd，如果长期，填写 “长期”
            'holdingComIdType' => trim($arr['holdingComIdType']),// 控股股东/实际控制人证件类型 反洗钱 10-身份证 20-统一社会信用代码
            'beneficiaryName' => trim($arr['beneficiaryName']),// 受益所有人名称 反洗钱
            'beneficiaryAddress' => trim($arr['beneficiaryAddress']),// 受益所有人地址 反洗钱
            'beneficiaryIdType' => '10',// 受益所有人证件类型 反洗钱
            'beneficiaryIdNo' => trim($arr['beneficiaryIdNo']),// 受益所有人证件号码 反洗钱
            'beneficiaryIdStartDate' => trim($arr['beneficiaryIdStartDate']),// 受益所有人证件开始日期 反洗钱
            'beneficiaryIdEndDate' => trim($arr['beneficiaryIdEndDate']),// 受益所有人证件到期日期 反洗钱 如果长期，填写“长期”
            'startDate' => trim($arr['startDate']),// 营业期限开始日期 反洗钱
            'endDate' => trim($arr['endDate']),// 营业期限结束日期 反洗钱
            'registerProvince' => trim($arr['registerProvince']),// 注册地址省 反洗钱
            'registerCity' => trim($arr['registerCity']),// 注册地址市 反洗钱
            'registerDistrict' => trim($arr['registerDistrict']),// 注册地址区 反洗钱
            'registerAddress' => trim($arr['registerAddress']),// 注册详细地址 反洗钱
        ];

        $public = [
            'channelSerialNo' => $channelSerialNo,// 流水号
            'channelId' => $this->obj->channelId,
            'transCode' => $transCode,
        ];

        return $this->obj->setParams($payload, $public)->setHeader($version)->send($transCode);
    }

    // 文件流上传 一张一张传 文件绝对路径 设置苏宁端新文件名 法人身份证 法人名称 文件类型(word) 自定义流水号和 serialNo 对应
    function fileStreamUpload(string $filepath, string $newName, string $no, string $name, string $type, string $channelSerialNo)
    {
        $version = '1.0';
        $transCode = 'snb.fsofts.fileStream.upload';
        !empty($channelSerialNo) ?: $channelSerialNo = 'xd' . Helper::getInstance()->getMicroTime();

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

        return $this->obj->setParams($payload, $public)->setHeader($version)->send($transCode);
    }
}