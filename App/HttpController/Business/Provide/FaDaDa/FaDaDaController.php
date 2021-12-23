<?php

namespace App\HttpController\Business\Provide\FaDaDa;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;

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
        $code = $this->getRequestData('code');

        $postData = [
            'entName' => $entName,
            'code' => $code,
        ];

        $sql = <<<Eof
CREATE TABLE `information_dance_fa_da_da_auth` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `entName` varchar(64) NOT NULL DEFAULT '',
  `code` varchar(32) NOT NULL DEFAULT '',
  `transaction_id` varchar(64) NOT NULL DEFAULT '交易号',
  `contract_id` varchar(64) NOT NULL DEFAULT '合同号',
  `customer_id` varchar(64) NOT NULL DEFAULT '客户号',
  `signature_id` varchar(64) NOT NULL DEFAULT '印章号',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `customer_id_index` (`customer_id`),
  KEY `hash_deposit_id_index` (`hash_deposit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='法大大存证';
Eof;

        $this->csp->add($this->cspKey, function () use ($postData) {

        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }


}



