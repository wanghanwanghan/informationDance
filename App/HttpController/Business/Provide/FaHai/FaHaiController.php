<?php

namespace App\HttpController\Business\Provide\FaHai;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\FaYanYuan\FaYanYuanService;
use App\HttpController\Service\LongDun\LongDunService;
use App\HttpController\Service\TaoShu\TaoShuService;

class FaHaiController extends ProvideBase
{
    public $listBaseUrl;
    public $detailBaseUrl;

    function onRequest(?string $action): ?bool
    {
        $this->listBaseUrl = CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl');
        $this->detailBaseUrl = CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl');

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

    function getKtgg()
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $docType = 'ktgg';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getList($this->listBaseUrl . 'sifa', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getFygg()
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $docType = 'fygg';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getList($this->listBaseUrl . 'sifa', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getKtggDetail()
    {
        $id = $this->getRequestData('id');

        $docType = 'ktgg';

        $postData = [
            'id' => $id,
            'doc_type' => $docType,
        ];

        $this->csp->add($this->cspKey, function () use ($postData, $docType) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getDetail($this->detailBaseUrl . $docType, $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getFyggDetail()
    {
        $id = $this->getRequestData('id');

        $docType = 'fygg';

        $postData = [
            'id' => $id,
            'doc_type' => $docType,
        ];

        $this->csp->add($this->cspKey, function () use ($postData, $docType) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getDetail($this->detailBaseUrl . $docType, $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getSatpartyXin(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $docType = 'satparty_xin';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())->setCheckRespFlag(true)
                ->getList($this->listBaseUrl . 'sat', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getSatpartyXinDetail(): bool
    {
        $id = $this->getRequestData('id');

        $docType = 'satparty_xin';

        $postData = [
            'id' => $id,
            'doc_type' => $docType,
        ];

        $this->csp->add($this->cspKey, function () use ($postData, $docType) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getDetail($this->detailBaseUrl . $docType, $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getSatpartyReg(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $docType = 'satparty_reg';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())->setCheckRespFlag(true)
                ->getList($this->listBaseUrl . 'sat', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getSatpartyRegDetail(): bool
    {
        $id = $this->getRequestData('id');

        $docType = 'satparty_reg';

        $postData = [
            'id' => $id,
            'doc_type' => $docType,
        ];

        $this->csp->add($this->cspKey, function () use ($postData, $docType) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getDetail($this->detailBaseUrl . $docType, $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getCpws(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $docType = 'cpws';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getList($this->listBaseUrl . 'sifa', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }
    function getZxgg(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $docType = 'zxgg';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getList($this->listBaseUrl . 'sifa', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }
    function getShixin(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $docType = 'shixin';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getList($this->listBaseUrl . 'sifa', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //裁判文书详情
    function getCpwsDetail()
    {
        $id = $this->getRequestData('id') ?? '';
        $docType = 'cpws';
        $postData = ['id' => $id,'doc_type' => $docType];
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getDetail($this->detailBaseUrl . 'cpws', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //失信公告详情
    function getShixinDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';
        $docType = 'shixin';
        $postData = ['id' => $id,'doc_type' => $docType];
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getDetail($this->detailBaseUrl . 'shixin', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //查冻扣
    function getSifacdkList(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $docType = 'sifacdk';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getList($this->listBaseUrl . 'sifa', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //查冻扣
    function getSifacdkDetail(): bool
    {
        $id = $this->getRequestData('id');

        $docType = 'sifacdk';

        $postData = [
            'id' => $id,
            'doc_type' => $docType,
        ];

        $this->csp->add($this->cspKey, function () use ($postData, $docType) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getDetail($this->detailBaseUrl . $docType, $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //其他动产融资列表
    function getCompanyZdwQtdcdsrList(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $docType = 'company_zdw_qtdcdsr';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getList($this->listBaseUrl . 'sifa', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //其他动产融资详情
    function getCompanyZdwQtdcdsrDetail(): bool
    {
        $id = $this->getRequestData('id');

        $docType = 'company_zdw_qtdcdsr';

        $postData = [
            'id' => $id,
            'doc_type' => $docType,
        ];

        $this->csp->add($this->cspKey, function () use ($postData, $docType) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getDetail($this->detailBaseUrl . $docType, $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //仓单质押列表
    function getCompanyZdwCdzydsrList(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $docType = 'company_zdw_cdzydsr';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getList($this->listBaseUrl . 'sifa', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //仓单质押详情
    function getCompanyZdwCdzydsrDetail(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $docType = 'company_zdw_cdzydsr';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getList($this->listBaseUrl . 'sifa', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //保证金质押登记
    function getCompanyZdwBzjzydsrList(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $docType = 'company_zdw_bzjzydsr';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getList($this->listBaseUrl . 'sifa', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //保证金质押登记详情
    function getCompanyZdwBzjzydsrDetail(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $docType = 'company_zdw_bzjzydsr';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getList($this->listBaseUrl . 'sifa', $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getJudicialSaleList(): bool
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'keyWord' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongDunService())->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'JudicialSaleCheck/GetList', $postData);//JudicialSale/GetJudicialSaleList
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //涉税处罚
    public function getSatpartyChufa(){
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;
        $docType = 'satparty_chufa';
        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getList($this->listBaseUrl . 'sifa', $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //涉税处罚公示详情
    function getSatpartyChufaDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';
        $this->entName = $this->request()->getRequestParam('entName') ?? '';
        $postData = ['id' => $id];
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getDetail($this->detailBaseUrl . 'satparty_chufa', $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //欠税公告
    function getSatpartyQs()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'satparty_qs';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getList($this->listBaseUrl . 'sat', $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //欠税公告详情
    function getSatpartyQsDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';
        $this->entName = $this->request()->getRequestParam('entName') ?? '';
        $postData = ['id' => $id];
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getDetail($this->detailBaseUrl . 'satparty_qs', $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //税务非正常户公示
    function getSatpartyFzc()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;
        $docType = 'satparty_fzc';
        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getList($this->listBaseUrl . 'sat', $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //税务非正常户公示详情
    function getSatpartyFzcDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';
        $this->entName = $this->request()->getRequestParam('entName') ?? '';
        $postData = ['id' => $id];
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getDetail($this->detailBaseUrl . 'satparty_fzc', $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //税务许可
    function getSatpartyXuke()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;
        $docType = 'satparty_xuke';
        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getList($this->listBaseUrl . 'sat', $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //税务许可详情
    function getSatpartyXukeDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';
        $this->entName = $this->request()->getRequestParam('entName') ?? '';
        $postData = ['id' => $id];
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getDetail($this->detailBaseUrl . 'satparty_xuke', $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //央行行政处罚
    function xingZhengPunishList()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $doc_type = 'pbcparty';
        $postData = [
            'doc_type' => $doc_type,
            'keyword' => $entName,
            'pageno' => $pageno,
            'range' => $range,
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return  (new FaYanYuanService())
                    //->setCheckRespFlag(false)
                    ->setCheckRespFlag(true)
                    ->getList( CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'pbc', $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        CommonService::writeTestLog(json_encode([
            'xingZhengPunishList_post'=>$postData,
            'xingZhengPunishList_$res'=>$res,
        ]));
        return $this->checkResponse($res);
    }

    //央行行政处罚
    function xingZhengPunishDetails()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entryId = $this->request()->getRequestParam('entryId') ?? '';
        $doc_type = 'pbcparty';
        //取详情
        $postData = [
            'id' => $entryId,
            'doc_type' => $doc_type
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return (new FaYanYuanService())
                ->setCheckRespFlag(true)
                ->getDetail( CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $postData['doc_type'], $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        CommonService::writeTestLog(json_encode([
            'xingZhengPunishDetails_post'=>$postData,
            'xingZhengPunishDetails_$res'=>$res,
        ]));
        return $this->checkResponse($res);
    }

    //银保监会处罚公示
    function yinJianHuiPunishNoticeList()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $doc_type = 'pbcparty_cbrc';
        $postData = [
            'doc_type' => $doc_type,
            'keyword' => $entName,
            'pageno' => $pageno,
            'range' => $range,
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return  (new FaYanYuanService())
                //->setCheckRespFlag(false)
                ->setCheckRespFlag(true)
                ->getList( CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'pbc', $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        CommonService::writeTestLog(json_encode([
            'yinJianHuiPunishNoticeList_post'=>$postData,
            'yinJianHuiPunishNoticeList_$res'=>$res,
        ]));
        return $this->checkResponse($res);
    }

    //银保监会处罚公示
    function yinJianHuiPunishNoticeDetail()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entryId = $this->request()->getRequestParam('entryId') ?? '';
        $doc_type = 'pbcparty_cbrc';
        $postData = [
            'id' => $entryId,
            'doc_type' => $doc_type
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return (new FaYanYuanService())->setCheckRespFlag(true)->getDetail(
                CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $postData['doc_type'], $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        CommonService::writeTestLog(json_encode([
            'yinJianHuiPunishNoticeDetail_post'=>$postData,
            'yinJianHuiPunishNoticeDetail_$res'=>$res,
        ]));
        return $this->checkResponse($res);
    }

    // 证监处罚公示列表
    function zhengJianHuiPunishNoticeList()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $doc_type = 'pbcparty_csrc_chufa';
        $postData = [
            'doc_type' => $doc_type,
            'keyword' => $entName,
            'pageno' => $pageno,
            'range' => $range,
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return  (new FaYanYuanService())
                //->setCheckRespFlag(false)
                ->setCheckRespFlag(true)
                ->getList( CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'pbc', $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        CommonService::writeTestLog(json_encode([
            'zhengJianHuiPunishNoticeList_post'=>$postData,
            'zhengJianHuiPunishNoticeList_$res'=>$res,
        ]));
        return $this->checkResponse($res);
    }

    // 证监处罚公示详情
    function zhengJianHuiPunishNoticeDetail()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entryId = $this->request()->getRequestParam('entryId') ?? '';
        $doc_type = 'pbcparty_csrc_chufa';
        $postData = [
            'id' => $entryId,
            'doc_type' => $doc_type
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return (new FaYanYuanService())->setCheckRespFlag(true)->getDetail(
                CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $postData['doc_type'], $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        CommonService::writeTestLog(json_encode([
            'zhengJianHuiPunishNoticeDetail_post'=>$postData,
            'zhengJianHuiPunishNoticeDetail_$res'=>$res,
        ]));
        return $this->checkResponse($res);
    }

    //证监会许可信息列表
    function zhengJianHuiLicenseList()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $doc_type = 'pbcparty_csrc_xkpf';
        $postData = [
            'doc_type' => $doc_type,
            'keyword' => $entName,
            'pageno' => $pageno,
            'range' => $range,
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return  (new FaYanYuanService())
                //->setCheckRespFlag(false)
                ->setCheckRespFlag(true)
                ->getList( CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'pbc', $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        CommonService::writeTestLog(json_encode([
            'zhengJianHuilicenseList_post'=>$postData,
            'zhengJianHuilicenseList_$res'=>$res,
        ]));
        return $this->checkResponse($res);
    }

    // 证监处罚公示详情
    function zhengJianHuiLicenseDetail()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entryId = $this->request()->getRequestParam('entryId') ?? '';
        $doc_type = 'pbcparty_csrc_xkpf';
        $postData = [
            'id' => $entryId,
            'doc_type' => $doc_type
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return (new FaYanYuanService())->setCheckRespFlag(true)->getDetail(
                CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $postData['doc_type'], $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        CommonService::writeTestLog(json_encode([
            'zhengJianHuiLicenseDetail_post'=>$postData,
            'zhengJianHuiLicenseDetail_$res'=>$res,
        ]));
        return $this->checkResponse($res);
    }

    //外汇局处罚
    function waiHuiJuPunishList()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $doc_type = 'safe_chufa';
        $postData = [
            'doc_type' => $doc_type,
            'keyword' => $entName,
            'pageno' => $pageno,
            'range' => $range,
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return  (new FaYanYuanService())
                //->setCheckRespFlag(false)
                ->setCheckRespFlag(true)
                ->getList( CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'pbc', $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        CommonService::writeTestLog(json_encode([
            'waiHuiJuPunishList_post'=>$postData,
            'waiHuiJuPunishList_$res'=>$res,
        ]));
        return $this->checkResponse($res);
    }

    function waiHuiJuPunishDetail()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entryId = $this->request()->getRequestParam('entryId') ?? '';
        $doc_type = 'safe_chufa';
        $postData = [
            'id' => $entryId,
            'doc_type' => $doc_type
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return (new FaYanYuanService())->setCheckRespFlag(true)->getDetail(
                CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $postData['doc_type'], $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
//        CommonService::writeTestLog(json_encode([
//            'waiHuiJuPunishDetail_post'=>$postData,
//            'waiHuiJuPunishDetail_$res'=>$res,
//        ]));
        return $this->checkResponse($res);
    }

    //外汇局许可
    function waiHuiJuLicenseList()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $doc_type = 'safe_xuke';
        $postData = [
            'doc_type' => $doc_type,
            'keyword' => $entName,
            'pageno' => $pageno,
            'range' => $range,
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return  (new FaYanYuanService())
                //->setCheckRespFlag(false)
                ->setCheckRespFlag(true)
                ->getList( CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'pbc', $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    function waiHuiJuLicenseDetail()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entryId = $this->request()->getRequestParam('entryId') ?? '';
        $doc_type = 'safe_xuke';
        $postData = [
            'id' => $entryId,
            'doc_type' => $doc_type
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return (new FaYanYuanService())->setCheckRespFlag(true)->getDetail(
                CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $postData['doc_type'], $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
//        CommonService::writeTestLog(json_encode([
//            'waiHuiJuPunishDetail_post'=>$postData,
//            'waiHuiJuPunishDetail_$res'=>$res,
//        ]));
        return $this->checkResponse($res);
    }

    // 法院公告
    function faYuanNoticeList()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $doc_type = 'fygg';
        $postData = [
            'doc_type' => $doc_type,
            'keyword' => $entName,
            'pageno' => $pageno,
            'range' => $range,
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return  (new FaYanYuanService())
                //->setCheckRespFlag(false)
                ->setCheckRespFlag(true)
                ->getList( CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'pbc', $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }
    // 法院公告
    function faYuanNoticeDetail()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entryId = $this->request()->getRequestParam('entryId') ?? '';
        $doc_type = 'fygg';
        $postData = [
            'id' => $entryId,
            'doc_type' => $doc_type
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return (new FaYanYuanService())->setCheckRespFlag(true)->getDetail(
                CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $postData['doc_type'], $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
//        CommonService::writeTestLog(json_encode([
//            'waiHuiJuPunishDetail_post'=>$postData,
//            'waiHuiJuPunishDetail_$res'=>$res,
//        ]));
        return $this->checkResponse($res);
    }

    //开庭公告列表
    function kaiTingNoticeList()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $doc_type = 'ktgg';
        $postData = [
            'doc_type' => $doc_type,
            'keyword' => $entName,
            'pageno' => $pageno,
            'range' => $range,
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return  (new FaYanYuanService())
                //->setCheckRespFlag(false)
                ->setCheckRespFlag(true)
                ->getList( CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'pbc', $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }
    //开庭公告详情
    function kaiTingNoticeDetail()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entryId = $this->request()->getRequestParam('entryId') ?? '';
        $doc_type = 'ktgg';
        $postData = [
            'id' => $entryId,
            'doc_type' => $doc_type
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return (new FaYanYuanService())->setCheckRespFlag(true)->getDetail(
                CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $postData['doc_type'], $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
//        CommonService::writeTestLog(json_encode([
//            'waiHuiJuPunishDetail_post'=>$postData,
//            'waiHuiJuPunishDetail_$res'=>$res,
//        ]));
        return $this->checkResponse($res);
    }

    //裁判文书列表
    function caiPanWenShuList()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $doc_type = 'cpws';
        $postData = [
            'doc_type' => $doc_type,
            'keyword' => $entName,
            'pageno' => $pageno,
            'range' => $range,
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return  (new FaYanYuanService())
                //->setCheckRespFlag(false)
                ->setCheckRespFlag(true)
                ->getList( CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'pbc', $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }
    //裁判文书详情
    function caiPanWenShuDetail()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entryId = $this->request()->getRequestParam('entryId') ?? '';
        $doc_type = 'cpws';
        $postData = [
            'id' => $entryId,
            'doc_type' => $doc_type
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return (new FaYanYuanService())->setCheckRespFlag(true)->getDetail(
                CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $postData['doc_type'], $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
//        CommonService::writeTestLog(json_encode([
//            'waiHuiJuPunishDetail_post'=>$postData,
//            'waiHuiJuPunishDetail_$res'=>$res,
//        ]));
        return $this->checkResponse($res);
    }

    //执行公告列表
    function zhiXingGongGaoList()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $doc_type = 'zxgg';
        $postData = [
            'doc_type' => $doc_type,
            'keyword' => $entName,
            'pageno' => $pageno,
            'range' => $range,
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return  (new FaYanYuanService())
                //->setCheckRespFlag(false)
                ->setCheckRespFlag(true)
                ->getList( CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'pbc', $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //执行公告详情
    function zhiXingGongGaoDetail()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entryId = $this->request()->getRequestParam('entryId') ?? '';
        $doc_type = 'zxgg';
        $postData = [
            'id' => $entryId,
            'doc_type' => $doc_type
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return (new FaYanYuanService())->setCheckRespFlag(true)->getDetail(
                CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $postData['doc_type'], $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
//        CommonService::writeTestLog(json_encode([
//            'waiHuiJuPunishDetail_post'=>$postData,
//            'waiHuiJuPunishDetail_$res'=>$res,
//        ]));
        return $this->checkResponse($res);
    }

    //失信公告
    function shiXinGongGaoList()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $doc_type = 'shixin';
        $postData = [
            'doc_type' => $doc_type,
            'keyword' => $entName,
            'pageno' => $pageno,
            'range' => $range,
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return  (new FaYanYuanService())
                //->setCheckRespFlag(false)
                ->setCheckRespFlag(true)
                ->getList( CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'pbc', $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }
    //失信公告
    function shiXinGongGaoDetail()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entryId = $this->request()->getRequestParam('entryId') ?? '';
        $doc_type = 'shixin';
        $postData = [
            'id' => $entryId,
            'doc_type' => $doc_type
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return (new FaYanYuanService())->setCheckRespFlag(true)->getDetail(
                CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $postData['doc_type'], $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
//        CommonService::writeTestLog(json_encode([
//            'waiHuiJuPunishDetail_post'=>$postData,
//            'waiHuiJuPunishDetail_$res'=>$res,
//        ]));
        return $this->checkResponse($res);
    }

    //被执行人
    function beiZhiXingRenList()
    {
       $entName = $this->request()->getRequestParam('entName') ?? '';

        $postData = [
            'searchKey' => $entName,
            'isExactlySame' => true,
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return  (new LongDunService())->setCheckRespFlag(true)->get(
                CreateConf::getInstance()->getConf('longdun.baseUrl') . 'CourtV4/SearchZhiXing', $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    // 保证金质押登记
    function baoZhengJinZhiYaList()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $doc_type = 'company_zdw_bzjzydsr';
        $postData = [
            'doc_type' => $doc_type,
            'keyword' => $entName,
            'pageno' => $pageno,
            'range' => $range,
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return  (new FaYanYuanService())
                //->setCheckRespFlag(false)
                ->setCheckRespFlag(true)
                ->getList( CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'pbc', $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    // 保证金质押登记
    function baoZhengJinZhiYaDetail()
    {
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $entryId = $this->request()->getRequestParam('entryId') ?? '';
        $doc_type = 'company_zdw_bzjzydsr';
        $postData = [
            'id' => $entryId,
            'doc_type' => $doc_type
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {

            return (new FaYanYuanService())->setCheckRespFlag(true)->getDetail(
                CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $postData['doc_type'], $postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
//        CommonService::writeTestLog(json_encode([
//            'waiHuiJuPunishDetail_post'=>$postData,
//            'waiHuiJuPunishDetail_$res'=>$res,
//        ]));
        return $this->checkResponse($res);
    }

    //动产抵押
    function dongChanDiYa()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $pageno = $this->request()->getRequestParam('page') ?? '1';
        $range = $this->request()->getRequestParam('pageSize') ?? '20';
        $postData = [
            'entName' => $entName,
            'pageNo' => $pageno,
            'pageSize' => $range,
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {
            $res = (new TaoShuService())->setCheckRespFlag(true)->post($postData, 'getChattelMortgageInfo');

            return  $res;
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }


}



