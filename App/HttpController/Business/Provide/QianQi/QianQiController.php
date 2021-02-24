<?php

namespace App\HttpController\Business\Provide\QianQi;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\QianQi\QianQiService;

class QianQiController extends ProvideBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function checkResponse($res)
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

    //对外的最近三年财务数据 全部字段
    function getThreeYearsData()
    {
        $entName = $this->getRequestData('entName', '');

        $postData = [
            'entName' => $entName
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new QianQiService())->setCheckRespFlag(true)->getThreeYears($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //对外的最近三年财务数据 单独字段 ASSGRO_REL 资产总额
    function getThreeYearsDataForASSGRO_REL()
    {
        $entName = $this->getRequestData('entName', '');
        $target = 'ASSGRO_REL';

        $postData = [
            'entName' => $entName
        ];

        $this->csp->add($this->cspKey, function () use ($postData, $target) {
            return (new QianQiService())
                ->setCheckRespFlag(true)
                ->getThreeYearsReturnOneField($postData, $target);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //对外的最近三年财务数据 单独字段 LIAGRO_REL 负债总额
    function getThreeYearsDataForLIAGRO_REL()
    {
    }

    //对外的最近三年财务数据 单独字段 VENDINC_REL 营业总收入
    function getThreeYearsDataForVENDINC_REL()
    {
    }

    //对外的最近三年财务数据 单独字段 MAIBUSINC_REL 主营业务收入
    function getThreeYearsDataForMAIBUSINC_REL()
    {
    }

    //对外的最近三年财务数据 单独字段 PROGRO_REL 利润总额
    function getThreeYearsDataForPROGRO_REL()
    {
    }

    //对外的最近三年财务数据 单独字段 NETINC_REL 净利润
    function getThreeYearsDataForNETINC_REL()
    {
    }

    //对外的最近三年财务数据 单独字段 RATGRO_REL 纳税总额
    function getThreeYearsDataForRATGRO_REL()
    {
    }

    //对外的最近三年财务数据 单独字段 TOTEQU_REL 所有者权益
    function getThreeYearsDataForTOTEQU_REL()
    {
    }

    //对外的最近三年财务数据 单独字段 SOCNUM 社保人数
    function getThreeYearsDataForSOCNUM()
    {
    }


}



