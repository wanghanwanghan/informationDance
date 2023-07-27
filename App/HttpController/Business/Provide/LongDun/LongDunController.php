<?php

namespace App\HttpController\Business\Provide\LongDun;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongDun\LongDunService;

class LongDunController extends ProvideBase
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
        } elseif ($res[$this->cspKey]['Status']) {
            $this->responseCode = $res[$this->cspKey]['Status'];
            $this->responsePaging = $res[$this->cspKey]['Paging'];
            $this->responseData = $res[$this->cspKey]['Result'];
            $this->responseMsg = $res[$this->cspKey]['Message'];
            $res[$this->cspKey]['Status'] === 200 ?: $this->spendMoney = 0;
        } else {
            $this->responseCode = $res[$this->cspKey]['code'];
            $this->responsePaging = $res[$this->cspKey]['paging'];
            $this->responseData = $res[$this->cspKey]['result'];
            $this->responseMsg = $res[$this->cspKey]['msg'];

            $res[$this->cspKey]['code'] === 200 ?: $this->spendMoney = 0;
        }

        return true;
    }

    //企业人员董监高信息 有对外投资信息
    function getECISeniorPerson(): bool
    {
        $entName = $this->request()->getRequestParam('entName');
        $personName = $this->request()->getRequestParam('personName');
        $type = $this->request()->getRequestParam('type');

        $postData = [
            'searchKey' => $entName,// 搜索关键字（企业名称、统一社会信用代码、注册号）
            'personName' => $personName,// 人员姓名
            'type' => trim($type),// 0：担任法定代表人；1：对外投资；2：在外任职
            'pageIndex' => '1',
            'pageSize' => '100',
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())->setCheckRespFlag(true)
                ->get($this->ldListUrl . 'ECISeniorPerson/GetList', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        CommonService::getInstance()->log4PHP($res, 'info', 'ECISeniorPerson_dongjiangao');

        return $this->checkResponse($res);
    }

    //实际控制人和控制路径
    function getBeneficiary()
    {
        $entName = $this->request()->getRequestParam('entName');
        $percent = $this->request()->getRequestParam('percent') ?? 0;
        $mode = $this->request()->getRequestParam('mode') ?? 0;

        $postData = [
            'companyName' => $entName,
            'percent' => $percent - 0,
            'mode' => $mode - 0,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())->setCheckRespFlag(true)
                ->get($this->ldListUrl . 'Beneficiary/GetBeneficiary', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getIPOGuarantee()
    {
        $entName = $this->getRequestData('entName', '');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $postData = [
            'entName' => $entName,
            'page' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            //先拿股票代码
            $info = (new LongDunService())->setCheckRespFlag(true)
                ->get($this->ldListUrl . 'ECIV4/GetBasicDetailsByName', ['keyword' => $postData['entName']]);
            if ($info['code'] === 200 && !empty($info['result'])) {
                empty($info['result']['StockNumber']) ? $stock = '' : $stock = $info['result']['StockNumber'];
            } else {
                $stock = '';
            }
            if (empty($stock)) return ['code' => 201, 'paging' => null, 'result' => null, 'msg' => '股票代码是空'];
            $postData = [
                'stockCode' => $stock,
                'pageIndex' => $postData['page'],
                'pageSize' => $postData['pageSize'],
            ];
            return (new LongDunService())->setCheckRespFlag(true)
                ->get($this->ldListUrl . 'IPO/GetIPOGuarantee', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getProjectProductCheck()
    {
        $searchKey = $this->getRequestData('searchKey');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $postData = [
            'searchKey' => $searchKey,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())
                ->setCheckRespFlag(true)
                ->get($this->ldListUrl . 'ProjectProductCheck/GetList', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getCompatProductRecommend()
    {
        $id = $this->getRequestData('id');

        $postData = [
            'id' => $id,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())
                ->setCheckRespFlag(true)
                ->get($this->ldListUrl . 'CompatProductRecommend/GetList', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //行政处罚
    function getAdministrativePenaltyList()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'AdminPenaltyCheck/GetList', $postData);//AdministrativePenalty/GetAdministrativePenaltyList
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //招投标
    function tenderSearch()
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())
                ->setCheckRespFlag(true)
                ->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'Tender/Search', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //招投标详情
    function tenderSearchDetail()
    {
        //$id = $this->request()->getRequestParam('id');
        $id = $this->getRequestData('id');

        $postData = ['id' => $id];
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())
                ->setCheckRespFlag(true)
                ->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'Tender/Detail', $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //股权冻结
    function getJudicialAssistance()
    {
        $entName = $this->request()->getRequestParam('entName');

        $postData = [
            'keyWord' => $entName,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())
                ->setCheckRespFlag(true)
                ->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'JudicialAssistance/GetJudicialAssistance', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //行政许可
    function getAdministrativeLicenseList()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('pageNo') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())
                ->setCheckRespFlag(true)
                ->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'AdminLicenseCheck/GetList', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);

    }

    //行政许可详情
    function getAdministrativeLicenseListDetail()
    {
        $id = $this->request()->getRequestParam('id');

        $postData = ['id' => $id];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())
                ->setCheckRespFlag(true)
                ->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'AdminLicenseCheck/GetDetail', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //行政处罚详情   http://api.qichacha.com/AdminPenaltyCheck/GetCreditDetail
    function getAdministrativePenaltyListDetail()
    {
        $id = $this->request()->getRequestParam('id');

        $postData = ['id' => $id];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())
                ->setCheckRespFlag(true)
                ->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'AdminPenaltyCheck/GetCreditDetail', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //银行信息
    function GetCreditCodeNew()
    {
        $entName = $this->request()->getRequestParam('entName');

        $postData = ['keyWord' => $entName];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())
                ->setCheckRespFlag(true)
                ->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'ECICreditCode/GetCreditCodeNew', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);


    }

    public function getSearchSoftwareCr()
    {

    }

    public function getSearchCopyRight()
    {

    }

    public function getSearchCertification()
    {

    }

    public function getTmSearch()
    {

    }

    public function getPatentV4Search()
    {

    }

    //行政许可
    function EquityFreezeCheckGetList()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('pageNo') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())
                ->setCheckRespFlag(true)
                ->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'EquityFreezeCheck/GetList', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);

    }

    //行政许可详情
    function EquityFreezeCheckGetDetail()
    {
        $id = $this->request()->getRequestParam('id');

        $postData = ['id' => $id];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())
                ->setCheckRespFlag(true)
                ->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'EquityFreezeCheck/GetDetail', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //限制高消费核查 https://api.qichacha.com/SumptuaryCheck/GetList
    function SumptuaryCheckGetList()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('pageNo') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())
                ->setCheckRespFlag(true)
                ->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'SumptuaryCheck/GetList', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //限制高消费详情
    function SumptuaryCheckGetDetail()
    {
        $id = $this->request()->getRequestParam('id');

        $postData = ['id' => $id];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())
                ->setCheckRespFlag(true)
                ->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'SumptuaryCheck/GetDetail', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //限制高消费核查
    function PersonSumptuaryCheckGetList()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('pageNo') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;
        //personName

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())
                ->setCheckRespFlag(true)
                ->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'PersonSumptuaryCheck/GetList', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }
}