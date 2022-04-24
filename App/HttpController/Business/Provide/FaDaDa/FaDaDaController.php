<?php

namespace App\HttpController\Business\Provide\FaDaDa;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\FaDaDa\FaDaDaService;

class FaDaDaController extends ProvideBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function checkResponse($res): bool
    {
        if (empty($res[$this->cspKey])) {
            $this->responseCode = 500;
            $this->responsePaging = null;
            $this->responseData = $res[$this->cspKey];
            $this->spendMoney = 0;
            $this->responseMsg = '请求超时';
        } else {
            $this->responseCode = $res[$this->cspKey]['code'];
            $this->responsePaging = $res[$this->cspKey]['paging'];
            $this->responseData = $res[$this->cspKey]['result'];
            $this->responseMsg = $res[$this->cspKey]['msg'];

            $res[$this->cspKey]['code'] === 200 ?: $this->spendMoney = 0;
        }

        return true;
    }

    //
    function getAuthFile(): bool
    {
        $entName = $this->getRequestData('entName');
        $socialCredit = $this->getRequestData('socialCredit');
        $legalPerson = $this->getRequestData('legalPerson');
        $idCard = $this->getRequestData('idCard');
        $phone = $this->getRequestData('phone');
        $city = $this->getRequestData('city');
        $regAddress = $this->getRequestData('regAddress');

        $postData = [
            'entName' => $entName,
            'socialCredit' => $socialCredit,
            'legalPerson' => $legalPerson,
            'idCard' => $idCard,
            'phone' => $phone,
            'city' => $city,
            'regAddress' => $regAddress,
        ];
        CommonService::getInstance()->log4PHP([$postData],'info','getAuthFile');

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaDaDaService())->setCheckRespFlag(true)->getAuthFile($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    public function getAuthFileByFile(): bool
    {
        $entName = $this->getRequestData('entName');
        $socialCredit = $this->getRequestData('socialCredit');
        $legalPerson = $this->getRequestData('legalPerson');
        $idCard = $this->getRequestData('idCard');
        $phone = $this->getRequestData('phone');
        $city = $this->getRequestData('city');
        $regAddress = $this->getRequestData('regAddress');
        $file = $this->getRequestData('file');
        $postData = [
            'entName' => $entName,
            'socialCredit' => $socialCredit,
            'legalPerson' => $legalPerson,
            'idCard' => $idCard,
            'phone' => $phone,
            'city' => $city,
            'regAddress' => $regAddress,
            'file' => $file,
        ];
        CommonService::getInstance()->log4PHP([$postData],'info','getAuthFile');

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaDaDaService())->setCheckRespFlag(true)->getAuthFile($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getAuthFileForAnt(): bool
    {
        $entName = $this->getRequestData('entName');
        $socialCredit = $this->getRequestData('socialCredit');
        $legalPerson = $this->getRequestData('legalPerson');
        $idCard = $this->getRequestData('idCard');
        $phone = $this->getRequestData('phone');
        $city = $this->getRequestData('city');
        $regAddress = $this->getRequestData('regAddress');
        $file_address = $this->getRequestData('file_address');
        $postData = [
            'entName' => $entName,
            'socialCredit' => $socialCredit,
            'legalPerson' => $legalPerson,
            'idCard' => $idCard,
            'phone' => $phone,
            'city' => $city,
            'regAddress' => $regAddress,
            'file_address' => $file_address,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaDaDaService())->setCheckRespFlag(true)->getAuthFileForAnt($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function test()
    {
        $sql = <<<Eof
CREATE TABLE `fa_da_da_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `entName` varchar(64) NOT NULL DEFAULT '',
  `code` varchar(32) NOT NULL DEFAULT '',
  `account_type` varchar(4) NOT NULL DEFAULT '' COMMENT '2是企业 1是个人',
  `customer_id` varchar(64) NOT NULL DEFAULT '' COMMENT '客户号',
  `open_id` varchar(64) NOT NULL DEFAULT '' COMMENT '在信动的唯一键',
  `evidence_no` varchar(64) NOT NULL DEFAULT '' COMMENT '哈希存证唯一键',
  `signature_id` varchar(64) NOT NULL DEFAULT '' COMMENT '印章唯一键',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `entName_index` (`entName`),
  KEY `code_index` (`code`),
  KEY `customer_id_index` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='法大大用户表';
Eof;
    }


}



