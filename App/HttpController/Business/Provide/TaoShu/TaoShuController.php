<?php

namespace App\HttpController\Business\Provide\TaoShu;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\TaoShu\TaoShuService;
use App\HttpController\Service\TaoShu\TaoShuTwoService;

class TaoShuController extends ProvideBase
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

    function lawPersonInvestmentInfo(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $postData = [
            'entName' => $entName,
            'pageNo' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'lawPersonInvestmentInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //企业对外投资
    function getInvestmentAbroadInfo()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $pageNo = $this->request()->getRequestParam('pageNo') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'entName' => $entName,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, __FUNCTION__);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //企业分支机构
    function getBranchInfo()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $pageNo = $this->request()->getRequestParam('pageNo') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'entName' => $entName,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, __FUNCTION__);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getLawPersontoOtherInfo(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $postData = [
            'entName' => $entName,
            'pageNo' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getLawPersontoOtherInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getRegisterInfo(): bool
    {
        $entName = $this->getRequestData('entName', '');

        $postData = [
            'entName' => $entName,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getRegisterInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getGoodsInfo(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $postData = [
            'entName' => $entName,
            'pageNo' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getgoodsInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getEntScore(): bool
    {
        $entName = $this->getRequestData('entName');

        $postData = [
            'entName' => $entName,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuTwoService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getEntScore');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getGraphGCoreData(): bool
    {
        $entName = $this->getRequestData('entName');
        $level = $this->getRequestData('level', '3');//3
        $nodeType = $this->getRequestData('nodeType', 'GS');
        $attIds = $this->getRequestData('attIds', 'R101;R102;R103;R104;R105;R106;R107;R108');

        $postData = [
            'keyword' => $entName,
            'attIds' => $attIds,
            'level' => $level - 0 > 3 ? '3' : $level . '',
            'nodeType' => $nodeType,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getGraphGCoreData');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getEntGraphG(): bool
    {
        $entName = $this->getRequestData('entName');
        $level = $this->getRequestData('level', 1);
        $nodeType = $this->getRequestData('nodeType', 'GS');
        $attIds = $this->getRequestData('attIds', 'R101;R102;R103;R104;R105;R106;R107;R108');

        $postData = [
            'keyword' => $entName,
            'attIds' => $attIds,
            'level' => $level - 0 > 3 ? '3' : $level . '',
            'nodeType' => $nodeType,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getEntGraphG');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getHistoryStockHolderInfo()
    {
        $entName = $this->getRequestData('entName', '');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $postData = [
            'entName' => $entName,
            'pageNo' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getHistoryStockHolderInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getHistoryPersonInfo()
    {
        $entName = $this->getRequestData('entName', '');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $postData = [
            'entName' => $entName,
            'pageNo' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getHistoryPersonInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getGeoPositionInfo(): bool
    {
        $entName = $this->getRequestData('entName');

        $postData = [
            'entName' => $entName,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getGeoPositionInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getEntGraphGShortData(): bool
    {
        $entName = $this->getRequestData('entName');
        $attIds = $this->getRequestData('attIds', 'R101');//R101;R102
        $level = $this->getRequestData('level', '3');//6

        $postData = [
            'entName' => $entName,
            'attIds' => $attIds,
            'level' => $level,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getEntGraphGShortData');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getInternetShopInfo(): bool
    {
        $entName = $this->getRequestData('entName');

        $postData = [
            'entName' => $entName,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getInternetShopInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getEnterpriseProfileInfo(): bool
    {
        $entName = $this->getRequestData('entName');

        $postData = [
            'entName' => $entName,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getEnterpriseProfileInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getEntLogoInfo(): bool
    {
        $entName = $this->getRequestData('entName');

        $postData = [
            'entName' => $entName,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getEntLogoInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getICPRecordInfo(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page');
        $pageSize = $this->getRequestData('pageSize');

        $postData = [
            'entName' => $entName,
            'pageNo' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getICPRecordInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getAPPInfo(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page');
        $pageSize = $this->getRequestData('pageSize');

        $postData = [
            'entName' => $entName,
            'pageNo' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getAPPInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getActualAddrInfo(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page');
        $pageSize = $this->getRequestData('pageSize');

        $postData = [
            'entName' => $entName,
            //'pageNo' => $page,
            //'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getActualAddrInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getRecruitmentInfo(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page');
        $pageSize = $this->getRequestData('pageSize');

        $postData = [
            'entName' => $entName,
            'pageNo' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getRecruitmentInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getBiddingInfo(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page');
        $pageSize = $this->getRequestData('pageSize');

        $postData = [
            'entName' => $entName,
            'pageNo' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getBiddingInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getCustomEntRegisterInfo(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page');
        $pageSize = $this->getRequestData('pageSize');

        $postData = [
            'entName' => $entName,
            //'pageNo' => $page,
            //'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getCustomEntRegisterInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getCustomEntCreditLevelInfo(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page');
        $pageSize = $this->getRequestData('pageSize');

        $postData = [
            'entName' => $entName,
            //'pageNo' => $page,
            //'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getCustomEntCreditLevelInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getCustomEntPenaltyInfo(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $postData = [
            'entName' => $entName,
            'pageNo' => $page - 0,
            'pageSize' => $pageSize - 0,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getCustomEntPenaltyInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getEntActualContoller(): bool
    {
        $entName = $this->getRequestData('entName');
        //R102-法人股东，R104-企业自然人股东，R108-总部
        $attIds = $this->getRequestData('attIds', 'R102');
        $level = $this->getRequestData('level', 10);

        $postData = [
            'entName' => $entName,
            'attIds' => $attIds,
            'level' => $level,
            //'pageNo' => $page,
            //'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getEntActualContoller');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getAssociatePersonOfficeInfo(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page');
        $pageSize = $this->getRequestData('pageSize');

        $postData = [
            'entName' => $entName,
            'pageNo' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getAssociatePersonOfficeInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getAssociatePersonInvestmentInfo(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page');
        $pageSize = $this->getRequestData('pageSize');

        $postData = [
            'entName' => $entName,
            'pageNo' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getAssociatePersonInvestmentInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getQualifyCertifyInfo(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page');
        $pageSize = $this->getRequestData('pageSize');

        $postData = [
            'entName' => $entName,
            'pageNo' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getQualifyCertifyInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getDataExploreInfo(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page');
        $pageSize = $this->getRequestData('pageSize');

        $postData = [
            'entName' => $entName,
            'pageNo' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getDataExploreInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getEntHonorInfo(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page');
        $pageSize = $this->getRequestData('pageSize');

        $postData = [
            'entName' => $entName,
            'pageNo' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getEntHonorInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getEntLable(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page');
        $pageSize = $this->getRequestData('pageSize');

        $postData = [
            'entName' => $entName,
            'pageNo' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getEntLable');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getEntChronicleInfo(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page');
        $pageSize = $this->getRequestData('pageSize');

        $postData = [
            'entName' => $entName,
            'pageNo' => $page,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getEntChronicleInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getEntContactInfo(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page');
        $pageSize = $this->getRequestData('pageSize');

        $postData = [
            'entName' => $entName,
            //'pageNo' => $page,
            //'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getEntContactInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getEntsRelevanceSeekGraphG(): bool
    {
        $entName = $this->getRequestData('entName');
        $attIds = $this->getRequestData('attIds', 'R101');//R101;R102
        $level = $this->getRequestData('level', 6);//10
        $page = $this->getRequestData('page');
        $pageSize = $this->getRequestData('pageSize');

        $postData = [
            'entName' => $entName,
            'attIds' => $attIds,
            'level' => $level,
            //'pageNo' => $page,
            //'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getEntsRelevanceSeekGraphG');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getRecruitmentDetailInfo(): bool
    {
        $id = $this->getRequestData('id');
        $page = $this->getRequestData('page');
        $pageSize = $this->getRequestData('pageSize');

        $postData = [
            'rowKey' => $id,
            //'pageNo' => $page,
            //'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getRecruitmentDetailInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getBiddingDetailInfo(): bool
    {
        $id = $this->getRequestData('id');
        $page = $this->getRequestData('page');
        $pageSize = $this->getRequestData('pageSize');

        $postData = [
            'rowKey' => $id,
            //'pageNo' => $page,
            //'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getBiddingDetailInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getMainManagerInfo(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $postData = [
            'entName' => $entName,
            'pageNo' => $page . '',
            'pageSize' => $pageSize . '',
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getMainManagerInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }


}