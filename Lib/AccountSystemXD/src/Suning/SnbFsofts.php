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

    // 账户销户
    function accountCancel(
        string $merUserId, string $acctNbr, string $acctName, string $closeReasonCd, string $channelSerialNo
    )
    {
        $version = '1.0';
        $transCode = 'snb.steward.account.cancel';
        !empty($channelSerialNo) ?: $channelSerialNo = 'xd' . Helper::getInstance()->getMicroTime();

        $payload = [
            'merchantId' => $this->obj->merchantId,
            'platformcd' => $this->obj->platformcd,
            'merUserId' => trim($merUserId),// 会员号
            'acctNbr' => trim($acctNbr),// 虚拟户
            'acctName' => trim($acctName),// 虚拟户
            'closeReasonCd' => trim($closeReasonCd),// 原因说明 长度最大 20
        ];

        $public = [
            'channelSerialNo' => $channelSerialNo,// 流水号
            'channelId' => $this->obj->channelId,
            'transCode' => $transCode,
        ];

        return $this->obj->setParams($payload, $public)
            ->setHeader($version)->send($transCode);
    }

    // 对账文件申请
    function reconciliationFile(string $transDate, string $channelSerialNo)
    {
        $version = '1.0';
        $transCode = 'snb.steward.reconciliation.file';
        !empty($channelSerialNo) ?: $channelSerialNo = 'xd' . Helper::getInstance()->getMicroTime();

        $payload = [
            'merchantId' => $this->obj->merchantId,
            'platformcd' => $this->obj->platformcd,
            'transDate' => trim($transDate),// 交易日期 yyyyMMdd
        ];

        $public = [
            'channelSerialNo' => $channelSerialNo,// 流水号
            'channelId' => $this->obj->channelId,
            'transCode' => $transCode,
        ];

        return $this->obj->setParams($payload, $public)
            ->setHeader($version)->send($transCode);
    }

    // 电子回单申请 入金 提现 非担保消费
    function financeReceiptApply(
        string $merUserId, string $orgReqDate, string $orgChannelSerialNo, string $channelSerialNo
    )
    {
        $version = '2.0';
        $transCode = 'snb.finance.receipt.apply';
        !empty($channelSerialNo) ?: $channelSerialNo = 'xd' . Helper::getInstance()->getMicroTime();

        $payload = [
            'merchantId' => $this->obj->merchantId,
            'platformcd' => $this->obj->platformcd,
            'merUserId' => trim($merUserId),// 会员号
            'orgReqDate' => trim($orgReqDate),// 原请求日期 yyyymmdd
            'orgChannelSerialNo' => trim($orgChannelSerialNo),// 原请求流水号
        ];

        $public = [
            'channelSerialNo' => $channelSerialNo,// 流水号
            'channelId' => $this->obj->channelId,
            'transCode' => $transCode,
        ];

        return $this->obj->setParams($payload, $public)
            ->setHeader($version)->send($transCode);
    }

    // 企业用户签约信息查询
    function enterpriseSignQuery(string $merUserId, string $acctNbr, string $channelSerialNo)
    {
        $version = '1.0';
        $transCode = 'snb.steward.enterprise.sign.query';
        !empty($channelSerialNo) ?: $channelSerialNo = 'xd' . Helper::getInstance()->getMicroTime();

        $payload = [
            'merchantId' => $this->obj->merchantId,
            'platformcd' => $this->obj->platformcd,
            'merUserId' => trim($merUserId),
            'acctNbr' => trim($acctNbr),
        ];

        $public = [
            'channelSerialNo' => $channelSerialNo,// 流水号
            'channelId' => $this->obj->channelId,
            'transCode' => $transCode,
        ];

        return $this->obj->setParams($payload, $public)
            ->setHeader($version)->send($transCode);
    }

    // 联行号查询
    function bankNumberQuery(string $keyword, string $page, string $channelSerialNo)
    {
        $version = '1.0';
        $transCode = 'snb.inter.bank.number.query';
        !empty($channelSerialNo) ?: $channelSerialNo = 'xd' . Helper::getInstance()->getMicroTime();

        $payload = [
            'merchantId' => $this->obj->merchantId,
            'limit' => '100',// 最大条数100条
            'offset' => trim($page),// 当前页
            'cityCode' => '',// 《数据字典》；城市代码、行别代码、关键字、行号字段最少输一个
            'bankCategoryCode' => '',// 《数据字典》；城市代码、行别代码、关键字、行号字段最少输一个
            'bankCode' => '',// 城市代码、行别代码、关键字、行号字段最少输一个
            'keyword' => trim($keyword),// 匹配行名字段模糊查询
        ];

        $public = [
            'channelSerialNo' => $channelSerialNo,// 流水号
            'channelId' => $this->obj->channelId,
            'transCode' => $transCode,
        ];

        return $this->obj->setParams($payload, $public)
            ->setHeader($version)->send($transCode);
    }

    // 批量入账查询
    function batchEntryQuery(string $orgChannelSerialNo, string $channelSerialNo)
    {
        $version = '1.0';
        $transCode = 'snb.steward.batch.entry.query';
        !empty($channelSerialNo) ?: $channelSerialNo = 'xd' . Helper::getInstance()->getMicroTime();

        $payload = [
            'merchantId' => $this->obj->merchantId,
            'platformcd' => $this->obj->platformcd,
            'orgChannelSerialNo' => trim($orgChannelSerialNo),// 支付分账 接口的流水号
        ];

        $public = [
            'channelSerialNo' => $channelSerialNo,// 流水号
            'channelId' => $this->obj->channelId,
            'transCode' => $transCode,
        ];

        return $this->obj->setParams($payload, $public)
            ->setHeader($version)->send($transCode);
    }

    // 企业账户信息变更
    function enterpriseUpdate(array $arr, string $channelSerialNo)
    {
        // 如果工商信息变了，我不调接口修改信息，会怎么样，很关键啊，客户工商变更是不会通知我的
        // 没有影响，我问领导，他说不怎样[捂脸]

        //1、企业账户信息变更本接口支持两类变更操作，单次接口调用只许执行一类变更操作，非变更内容项为空：
        //1）企业、账户信息变更；
        //2）绑定卡变更。绑定卡变更时，原银行卡号、银行卡号、银行账户名、开户行行号、开户行行名必输，
        //   其他非必输字段不赋值。一个用户允许变更绑卡次数上限为3次。
        //2、涉及影像资料变更的，接口调用前，需在文件服务器（平台方提供）上传影像文件。文件支持jpg、jpeg、bmp、png等常见图片格式。
        //3、会员名称、法人信息变更时，需上传“营业执照副本 url”。
        //4、法人、经办人变更时，涉及影像需上传证件照。
        //5、绑定卡变更时，受理成功后发起绑定卡打款
        //  （会由行方转账一笔随机金额到绑定卡，用户登录绑定卡所在行网银查看打款金额）。
        //   打款成功后，通过“打款金额验证”接口验证开户。
        //6、通过开户结果查询获取该笔流水状态，进行后续操作。
        //7、交易受理成功后，“交易状态”返回打款状态。

        $version = '2.0';
        $transCode = 'snb.steward.enterprise.update';
        !empty($channelSerialNo) ?: $channelSerialNo = 'xd' . Helper::getInstance()->getMicroTime();

        $payload = [
            'merchantId' => $this->obj->merchantId,
            'platformcd' => $this->obj->platformcd,
        ];

        $payload = array_merge($arr, $payload);

        $public = [
            'channelSerialNo' => $channelSerialNo,// 流水号
            'channelId' => $this->obj->channelId,
            'transCode' => $transCode,
        ];

        return $this->obj->setParams($payload, $public)
            ->setHeader($version)->send($transCode);
    }

    // 账户直接消费 虚拟户之间转账 A到B
    function directConsumption(
        string $payAcctNbr, string $rcvAcctNbr, string $transAmt, string $memo, string $channelSerialNo
    )
    {
        $version = '1.0';
        $transCode = 'snb.steward.direct.consumption';
        !empty($channelSerialNo) ?: $channelSerialNo = 'xd' . Helper::getInstance()->getMicroTime();

        $payload = [
            'merchantId' => $this->obj->merchantId,
            'platformcd' => $this->obj->platformcd,
            'payAcctNbr' => trim($payAcctNbr),// 买家账户 出金账户
            'rcvAcctNbr' => trim($rcvAcctNbr),// 卖家账户 入金账户
            'transAmt' => trim($transAmt),// 金额100.00
            'memo' => trim($memo),// 金额100.00
        ];

        $public = [
            'channelSerialNo' => $channelSerialNo,// 流水号
            'channelId' => $this->obj->channelId,
            'transCode' => $transCode,
        ];

        return $this->obj->setParams($payload, $public)
            ->setHeader($version)->send($transCode);
    }

    // 支付分账 虚拟户之间转账 A到BCDEF
    function payTransfer(string $payAcctNbr, array $list, string $memo, string $channelSerialNo)
    {
        $version = '1.0';
        $transCode = 'snb.steward.pay.transfer';
        !empty($channelSerialNo) ?: $channelSerialNo = 'xd' . Helper::getInstance()->getMicroTime();

        // $list = [
        //     [
        //         'rcvAcctNbr' => '入金账户1 虚拟号',
        //         'transAmt' => '交易金额 100.00',
        //     ],
        //     [
        //         'rcvAcctNbr' => '入金账户2 虚拟号',
        //         'transAmt' => '交易金额 200.00',
        //     ],
        // ];

        $payload = [
            'merchantId' => $this->obj->merchantId,
            'platformcd' => $this->obj->platformcd,
            'payAcctNbr' => trim($payAcctNbr),// 出金账户 虚拟号
            'list' => $list,
            'memo' => trim($memo),// 摘要
        ];

        $public = [
            'channelSerialNo' => $channelSerialNo,// 流水号
            'channelId' => $this->obj->channelId,
            'transCode' => $transCode,
        ];

        return $this->obj->setParams($payload, $public)
            ->setHeader($version)->send($transCode);
    }

    // 账户提现
    function accountWithdraw(
        string $merUserId, string $acctNbr,
        string $rcvAcctNbr, string $rcvAcctName, string $rcvBankName, string $rcvBankNo,
        string $transAmt, string $memo, string $channelSerialNo
    )
    {
        $version = '1.0';
        $transCode = 'snb.steward.account.withdraw';
        !empty($channelSerialNo) ?: $channelSerialNo = 'xd' . Helper::getInstance()->getMicroTime();

        $payload = [
            'merchantId' => $this->obj->merchantId,
            'platformcd' => $this->obj->platformcd,
            'merUserId' => $merUserId,// 会员号
            'acctNbr' => $acctNbr,// 子账号
            'rcvAcctNbr' => $rcvAcctNbr,// 收款账号
            'rcvAcctName' => $rcvAcctName,// 收款账户名称
            'rcvBankName' => $rcvBankName,// 收款账户开户行名
            'rcvBankNo' => $rcvBankNo,// 收款账户银行行号
            'transAmt' => $transAmt,// 交易金额
            'memo' => $memo,// 交易摘要
        ];

        $public = [
            'channelSerialNo' => $channelSerialNo,// 流水号
            'channelId' => $this->obj->channelId,
            'transCode' => $transCode,
        ];

        return $this->obj->setParams($payload, $public)
            ->setHeader($version)->send($transCode);
    }

    // 交易状态查询 艹要搞乱了
    function payStatusQuery(string $orgChannelSerialNo, string $channelSerialNo)
    {
        $version = '1.0';
        $transCode = 'snb.steward.pay.status.query';
        !empty($channelSerialNo) ?: $channelSerialNo = 'xd' . Helper::getInstance()->getMicroTime();

        $payload = [
            'merchantId' => $this->obj->merchantId,
            'platformcd' => $this->obj->platformcd,
            'orgChannelSerialNo' => $orgChannelSerialNo,// 原流水号
        ];

        $public = [
            'channelSerialNo' => $channelSerialNo,// 流水号
            'channelId' => $this->obj->channelId,
            'transCode' => $transCode,
        ];

        return $this->obj->setParams($payload, $public)
            ->setHeader($version)->send($transCode);
    }

    // 账户交易明细查询
    function payTransQuery(
        string $merUserId, string $acctNbr,
        string $startDate, string $endDate, string $page, string $pageSize, string $channelSerialNo
    )
    {
        $version = '1.0';
        $transCode = 'snb.steward.pay.trans.query';
        !empty($channelSerialNo) ?: $channelSerialNo = 'xd' . Helper::getInstance()->getMicroTime();

        $payload = [
            'merchantId' => $this->obj->merchantId,
            'platformcd' => $this->obj->platformcd,
            'merUserId' => $merUserId,// 会员号
            'acctNbr' => $acctNbr,// 子账号
            'startDate' => $startDate,// yyyymmdd
            'endDate' => $endDate,// yyyymmdd
            'pageNum' => $page,// 默认1
            'pageSize' => $pageSize,// 默认30，上限100
        ];

        $public = [
            'channelSerialNo' => $channelSerialNo,// 流水号
            'channelId' => $this->obj->channelId,
            'transCode' => $transCode,
        ];

        return $this->obj->setParams($payload, $public)
            ->setHeader($version)->send($transCode);
    }

    // 账户余额查询
    function accountBalanceQuery(string $merUserId, string $acctNbr, string $channelSerialNo)
    {
        $version = '1.0';
        $transCode = 'snb.steward.account.balance.query';
        !empty($channelSerialNo) ?: $channelSerialNo = 'xd' . Helper::getInstance()->getMicroTime();

        $payload = [
            'merchantId' => $this->obj->merchantId,
            'platformcd' => $this->obj->platformcd,
            'list' => [
                [
                    'merUserId' => $merUserId,// 会员号
                    'acctNbr' => $acctNbr,// 子账号
                ]
            ],
        ];

        $public = [
            'channelSerialNo' => $channelSerialNo,// 流水号
            'channelId' => $this->obj->channelId,
            'transCode' => $transCode,
        ];

        return $this->obj->setParams($payload, $public)
            ->setHeader($version)->send($transCode);
    }

    // 开户结果查询 艹
    function accountOpenQuery(string $orgChannelSerialNo, string $channelSerialNo)
    {
        $version = '1.0';
        $transCode = 'snb.steward.account.open.query';
        !empty($channelSerialNo) ?: $channelSerialNo = 'xd' . Helper::getInstance()->getMicroTime();

        $payload = [
            'merchantId' => $this->obj->merchantId,
            'platformcd' => $this->obj->platformcd,
            'orgChannelSerialNo' => trim($orgChannelSerialNo),// 原请求流水号
        ];

        $public = [
            'channelSerialNo' => $channelSerialNo,// 流水号
            'channelId' => $this->obj->channelId,
            'transCode' => $transCode,
        ];

        return $this->obj->setParams($payload, $public)
            ->setHeader($version)->send($transCode);
    }

    // 打款金额验证 妈的
    function payCheck(string $orgReqDate, string $orgChannelSerialNo, string $amount, string $channelSerialNo)
    {
        $version = '1.0';
        $transCode = 'snb.steward.pay.check';
        !empty($channelSerialNo) ?: $channelSerialNo = 'xd' . Helper::getInstance()->getMicroTime();

        $payload = [
            'merchantId' => $this->obj->merchantId,
            'platformcd' => $this->obj->platformcd,
            'orgReqDate' => trim($orgReqDate),// 原交易日期 yyyymmdd
            'orgChannelSerialNo' => trim($orgChannelSerialNo),// 原请求流水号
            'amount' => trim($amount),// 打款金额
        ];

        $public = [
            'channelSerialNo' => $channelSerialNo,// 流水号
            'channelId' => $this->obj->channelId,
            'transCode' => $transCode,
        ];

        return $this->obj->setParams($payload, $public)
            ->setHeader($version)->send($transCode);
    }

    // 企业绑卡开户 艹这么多参数
    function enterpriseOpen(array $arr, string $channelSerialNo)
    {
        $version = '2.0';
        $transCode = 'snb.steward.account.enterprise.open';
        !empty($channelSerialNo) ?: $channelSerialNo = 'xd' . Helper::getInstance()->getMicroTime();

        $payload = [
            'merchantId' => $this->obj->merchantId,
            'platformcd' => $this->obj->platformcd,
            'acctName' => trim($arr['acctName']),// 账户名称
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

        return $this->obj->setParams($payload, $public)
            ->setHeader($version)->send($transCode);
    }

    // 文件流上传 一张一张传 文件绝对路径 设置苏宁端新文件名 法人身份证 法人名称 文件类型(word) 自定义流水号和 serialNo 对应
    function fileStreamUpload(
        string $filepath, string $newName, string $no, string $name, string $type, string $channelSerialNo
    )
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

        return $this->obj->setParams($payload, $public)
            ->setHeader($version, 'multipart/form-data')->send($transCode, false);
    }
}