<?php

namespace App\HttpController;

use EasySwoole\Component\Singleton;
use FastRoute\RouteCollector;

class ProvideRouter
{
    use Singleton;

    //加载对外全部api
    function addRouterV1(RouteCollector $routeCollector)
    {
        $this->LongDunRouterV1($routeCollector);
        $this->TaoShuRouterV1($routeCollector);
        $this->QianQiRouterV1($routeCollector);
        $this->GuoPiaoRouterV1($routeCollector);
        $this->XinDongRouterV1($routeCollector);
        $this->FaYanYuanRouterV1($routeCollector);
        $this->YunMaTongRouterV1($routeCollector);
        $this->FaHaiRouterV1($routeCollector);
        $this->MaYiRouterV1($routeCollector);
        $this->QiXiangYunRouterV1($routeCollector);
        $this->LiuLengJingRouterV1($routeCollector);
        $this->YongTaiRouterV1($routeCollector);
        $this->ShuMengRouterV1($routeCollector);
        $this->BaiXiangRouterV1($routeCollector);
        $this->YiZhangTongRouterV1($routeCollector);
        $this->FaDaDaRouterV1($routeCollector);
        $this->Notify($routeCollector);
        $this->ChuangLanV1($routeCollector);
        $this->ZhiChiRouterV1($routeCollector);//智齿科技
        $this->GuangZhouYinLianV1($routeCollector);
        $this->DianZiQianV1($routeCollector);
        $this->NanJingXiaoAnV1($routeCollector);
        $this->ShenZhouYunHeV1($routeCollector);
        $this->JinCaiV1($routeCollector);
        $this->WhTest($routeCollector);
    }

    private function WhTest(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/WhTest/WhTestController/';
        $routeCollector->addGroup('/whtest', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getBranchInfo', $prefix . 'getBranchInfo');
        });
        return true;
    }

    private function JinCaiV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/JinCai/JinCaiController/';
        $routeCollector->addGroup('/jc', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/obtainFpInfoNew', $prefix . 'obtainFpInfoNew');
        });
        return true;
    }

    private function ShenZhouYunHeV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/ShenZhouYunHe/ShenZhouYunHeController/';
        $routeCollector->addGroup('/szyh', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/invoices', $prefix . 'invoices');
            $routeCollector->addRoute(['GET', 'POST'], '/collection', $prefix . 'collection');
        });
        return true;
    }

    private function DianZiQianV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/DianziQian/DianZiQianController/';
        $routeCollector->addGroup('/dzq', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getAuthFile', $prefix . 'getAuthFile');
            $routeCollector->addRoute(['GET', 'POST'], '/getUrl', $prefix . 'getUrl');
            $routeCollector->addRoute(['GET', 'POST'], '/getCarAuthFile', $prefix . 'getCarAuthFile');
            $routeCollector->addRoute(['GET', 'POST'], '/doTemporaryAction', $prefix . 'doTemporaryAction');//doTemporaryAction
            $routeCollector->addRoute(['GET', 'POST'], '/testInvEntList', $prefix . 'testInvEntList');//testInvEntList
            $routeCollector->addRoute(['GET', 'POST'], '/accountInfo', $prefix . 'accountInfo');
            $routeCollector->addRoute(['GET', 'POST'], '/costRecord', $prefix . 'costRecord');
            $routeCollector->addRoute(['GET', 'POST'], '/gaiZhang', $prefix . 'gaiZhang');
            $routeCollector->addRoute(['GET', 'POST'], '/gaiZhang2', $prefix . 'gaiZhang2');//通过传过来的文件盖企业章
            $routeCollector->addRoute(['GET', 'POST'], '/getAuthFile2Id', $prefix . 'getAuthFile2Id');//

        });
        return true;
    }

    private function GuangZhouYinLianV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/GuangZhouYinLian/GuangZhouYinLianController/';
        $routeCollector->addGroup('/gzyl', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/queryUsedVehicleInfo', $prefix . 'queryUsedVehicleInfo');
            $routeCollector->addRoute(['GET', 'POST'], '/queryInancialBank', $prefix . 'queryInancialBank');
        });
        return true;
    }

    private function ChuangLanV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/ChuangLan/ChuangLanController/';
        $routeCollector->addGroup('/cl', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getCheckPhoneStatus', $prefix . 'getCheckPhoneStatus');
            $routeCollector->addRoute(['GET', 'POST'], '/mobileNetStatus', $prefix . 'mobileNetStatus');
            $routeCollector->addRoute(['GET', 'POST'], '/carriersTwoAuth', $prefix . 'carriersTwoAuth');
        });
        return true;
    }

    private function ZhiChiRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/ZhiChi/ZhiChiController/';
        $routeCollector->addGroup('/zc', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/directUrl', $prefix . 'directUrl');
        });
    }

    private function LongDunRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/LongDun/LongDunController/';

        $routeCollector->addGroup('/qcc', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getIPOGuarantee', $prefix . 'getIPOGuarantee');
            $routeCollector->addRoute(['GET', 'POST'], '/getProjectProductCheck', $prefix . 'getProjectProductCheck');
            $routeCollector->addRoute(['GET', 'POST'], '/getCompatProductRecommend', $prefix . 'getCompatProductRecommend');
            $routeCollector->addRoute(['GET', 'POST'], '/getBeneficiary', $prefix . 'getBeneficiary');
            $routeCollector->addRoute(['GET', 'POST'], '/getAdministrativePenaltyList', $prefix . 'getAdministrativePenaltyList');//行政处罚
            $routeCollector->addRoute(['GET', 'POST'], '/getAdministrativePenaltyListDetail', $prefix . 'getAdministrativePenaltyListDetail');//  行政处罚详情
            $routeCollector->addRoute(['GET', 'POST'], '/getAdministrativeLicenseList', $prefix . 'getAdministrativeLicenseList');//  行政许可
            $routeCollector->addRoute(['GET', 'POST'], '/getAdministrativeLicenseListDetail', $prefix . 'getAdministrativeLicenseListDetail');//  行政许可详情
            $routeCollector->addRoute(['GET', 'POST'], '/tenderSearch', $prefix . 'tenderSearch');//招投标
            $routeCollector->addRoute(['GET', 'POST'], '/tenderSearchDetail', $prefix . 'tenderSearchDetail');//招投标详情
            $routeCollector->addRoute(['GET', 'POST'], '/getSearchSoftwareCr', $prefix . 'getSearchSoftwareCr');//软件著作权
            $routeCollector->addRoute(['GET', 'POST'], '/getSearchCopyRight', $prefix . 'getSearchCopyRight');//作品著作权
            $routeCollector->addRoute(['GET', 'POST'], '/getSearchCertification', $prefix . 'getSearchCertification');//企业证书查询
            $routeCollector->addRoute(['GET', 'POST'], '/getTmSearch', $prefix . 'getTmSearch');//商标
            $routeCollector->addRoute(['GET', 'POST'], '/getPatentV4Search', $prefix . 'getPatentV4Search');//专利
            $routeCollector->addRoute(['GET', 'POST'], '/GetCreditCodeNew', $prefix . 'GetCreditCodeNew');// 银行信息
            $routeCollector->addRoute(['GET', 'POST'], '/getJudicialAssistance', $prefix . 'getJudicialAssistance');// 股权冻结
            $routeCollector->addRoute(['GET', 'POST'], '/EquityFreezeCheckGetList', $prefix . 'EquityFreezeCheckGetList');//股权冻结核查
            $routeCollector->addRoute(['GET', 'POST'], '/EquityFreezeCheckGetDetail', $prefix . 'EquityFreezeCheckGetDetail');//股权冻结详情
            $routeCollector->addRoute(['GET', 'POST'], '/SumptuaryCheckGetList', $prefix . 'SumptuaryCheckGetList');//  限制高消费核查
            $routeCollector->addRoute(['GET', 'POST'], '/SumptuaryCheckGetDetail', $prefix . 'SumptuaryCheckGetDetail');// 限制高消费详情
            $routeCollector->addRoute(['GET', 'POST'], '/PersonSumptuaryCheckGetList', $prefix . 'PersonSumptuaryCheckGetList');//  限制高消费核查【董监高】
        });

        return true;
    }


    private function TaoShuRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/TaoShu/TaoShuController/';

        $routeCollector->addGroup('/ts', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getBeneficiaryInfo', $prefix . 'getBeneficiaryInfo');//受益人
            $routeCollector->addRoute(['GET', 'POST'], '/lawPersonInvestmentInfo', $prefix . 'lawPersonInvestmentInfo');//法人对外投资
            $routeCollector->addRoute(['GET', 'POST'], '/getInvestmentAbroadInfo', $prefix . 'getInvestmentAbroadInfo');//企业对外投资
            $routeCollector->addRoute(['GET', 'POST'], '/getBranchInfo', $prefix . 'getBranchInfo');//企业分支机构
            $routeCollector->addRoute(['GET', 'POST'], '/getLawPersontoOtherInfo', $prefix . 'getLawPersontoOtherInfo');//法人对外任职
            $routeCollector->addRoute(['GET', 'POST'], '/getRegisterInfo', $prefix . 'getRegisterInfo');
            $routeCollector->addRoute(['GET', 'POST'], '/getGoodsInfo', $prefix . 'getGoodsInfo');//企业生产的流通性产品信息
            $routeCollector->addRoute(['GET', 'POST'], '/getEntScore', $prefix . 'getEntScore');//企业竞争力
            $routeCollector->addRoute(['GET', 'POST'], '/getGraphGCoreData', $prefix . 'getGraphGCoreData');//企业核心图谱
            $routeCollector->addRoute(['GET', 'POST'], '/getEntGraphG', $prefix . 'getEntGraphG');//企业图谱查询
            $routeCollector->addRoute(['GET', 'POST'], '/getHistoryStockHolderInfo', $prefix . 'getHistoryStockHolderInfo');//历史退出股东
            $routeCollector->addRoute(['GET', 'POST'], '/getHistoryPersonInfo', $prefix . 'getHistoryPersonInfo');//历史退出高管
            $routeCollector->addRoute(['GET', 'POST'], '/getEntGraphGShortData', $prefix . 'getEntGraphGShortData');//
            $routeCollector->addRoute(['GET', 'POST'], '/getGeoPositionInfo', $prefix . 'getGeoPositionInfo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getInternetShopInfo', $prefix . 'getInternetShopInfo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getEnterpriseProfileInfo', $prefix . 'getEnterpriseProfileInfo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getEntLogoInfo', $prefix . 'getEntLogoInfo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getICPRecordInfo', $prefix . 'getICPRecordInfo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getAPPInfo', $prefix . 'getAPPInfo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getActualAddrInfo', $prefix . 'getActualAddrInfo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getRecruitmentInfo', $prefix . 'getRecruitmentInfo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getBiddingInfo', $prefix . 'getBiddingInfo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getCustomEntRegisterInfo', $prefix . 'getCustomEntRegisterInfo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getCustomEntCreditLevelInfo', $prefix . 'getCustomEntCreditLevelInfo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getCustomEntPenaltyInfo', $prefix . 'getCustomEntPenaltyInfo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getEntActualContoller', $prefix . 'getEntActualContoller');//
            $routeCollector->addRoute(['GET', 'POST'], '/getAssociatePersonOfficeInfo', $prefix . 'getAssociatePersonOfficeInfo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getAssociatePersonInvestmentInfo', $prefix . 'getAssociatePersonInvestmentInfo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getQualifyCertifyInfo', $prefix . 'getQualifyCertifyInfo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getDataExploreInfo', $prefix . 'getDataExploreInfo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getEntHonorInfo', $prefix . 'getEntHonorInfo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getEntLable', $prefix . 'getEntLable');//
            $routeCollector->addRoute(['GET', 'POST'], '/getEntChronicleInfo', $prefix . 'getEntChronicleInfo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getEntContactInfo', $prefix . 'getEntContactInfo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getEntsRelevanceSeekGraphG', $prefix . 'getEntsRelevanceSeekGraphG');//
            $routeCollector->addRoute(['GET', 'POST'], '/getRecruitmentDetailInfo', $prefix . 'getRecruitmentDetailInfo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getBiddingDetailInfo', $prefix . 'getBiddingDetailInfo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getMainManagerInfo', $prefix . 'getMainManagerInfo');//企业主要管理人员
            $routeCollector->addRoute(['GET', 'POST'], '/getRegisterChangeInfo', $prefix . 'getRegisterChangeInfo');//企业变更信息
            $routeCollector->addRoute(['GET', 'POST'], '/getShareHolderInfo', $prefix . 'getShareHolderInfo');//企业股东及出资信息
            $routeCollector->addRoute(['GET', 'POST'], '/dongChanDiYa', $prefix . 'dongChanDiYa');//企业股东及出资信息
            $routeCollector->addRoute(['GET', 'POST'], '/getOperatingExceptionRota', $prefix . 'getOperatingExceptionRota');// 企业经营异常
            $routeCollector->addRoute(['GET', 'POST'], '/getEquityPledgedDetailInfo', $prefix . 'getEquityPledgedDetailInfo');//  企业股权出质详情
            $routeCollector->addRoute(['GET', 'POST'], '/getEquityPledgedInfo', $prefix . 'getEquityPledgedInfo');//  企业股权出质列表
            $routeCollector->addRoute(['GET', 'POST'], '/getChattelMortgageDetailInfo', $prefix . 'getChattelMortgageDetailInfo');//  企业动产抵押详情
            $routeCollector->addRoute(['GET', 'POST'], '/getChattelMortgageInfo', $prefix . 'getChattelMortgageInfo');//  企业动产抵押列表
        });

        return true;
    }

    private function QianQiRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/QianQi/QianQiController/';

        $routeCollector->addGroup('/qq', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getThreeYearsData', $prefix . 'getThreeYearsData');
            $routeCollector->addRoute(['GET', 'POST'], '/getThreeYearsDataForASSGRO_REL', $prefix . 'getThreeYearsDataForASSGRO_REL');
            $routeCollector->addRoute(['GET', 'POST'], '/getThreeYearsDataForLIAGRO_REL', $prefix . 'getThreeYearsDataForLIAGRO_REL');
            $routeCollector->addRoute(['GET', 'POST'], '/getThreeYearsDataForVENDINC_REL', $prefix . 'getThreeYearsDataForVENDINC_REL');
            $routeCollector->addRoute(['GET', 'POST'], '/getThreeYearsDataForMAIBUSINC_REL', $prefix . 'getThreeYearsDataForMAIBUSINC_REL');
            $routeCollector->addRoute(['GET', 'POST'], '/getThreeYearsDataForPROGRO_REL', $prefix . 'getThreeYearsDataForPROGRO_REL');
            $routeCollector->addRoute(['GET', 'POST'], '/getThreeYearsDataForNETINC_REL', $prefix . 'getThreeYearsDataForNETINC_REL');
            $routeCollector->addRoute(['GET', 'POST'], '/getThreeYearsDataForRATGRO_REL', $prefix . 'getThreeYearsDataForRATGRO_REL');
            $routeCollector->addRoute(['GET', 'POST'], '/getThreeYearsDataForTOTEQU_REL', $prefix . 'getThreeYearsDataForTOTEQU_REL');
            $routeCollector->addRoute(['GET', 'POST'], '/getThreeYearsDataForSOCNUM', $prefix . 'getThreeYearsDataForSOCNUM');
        });

        return true;
    }

    private function GuoPiaoRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/GuoPiao/GuoPiaoController/';

        $routeCollector->addGroup('/zw', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getAuthentication', $prefix . 'getAuthentication');
            $routeCollector->addRoute(['GET', 'POST'], '/getInvoiceOcr', $prefix . 'getInvoiceOcr');
            $routeCollector->addRoute(['GET', 'POST'], '/getInvoiceOcrV2', $prefix . 'getInvoiceOcrV2');
            $routeCollector->addRoute(['GET', 'POST'], '/getInvoiceCheck', $prefix . 'getInvoiceCheck');
            $routeCollector->addRoute(['GET', 'POST'], '/getInvoiceCheckV2', $prefix . 'getInvoiceCheckV2');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceIncomeStatementAnnualReport', $prefix . 'getFinanceIncomeStatementAnnualReport');//利润表--年报查询
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceIncomeStatement', $prefix . 'getFinanceIncomeStatement');//利润表查询
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBalanceSheetAnnual', $prefix . 'getFinanceBalanceSheetAnnual');//资产负债表--年度查询
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBalanceSheet', $prefix . 'getFinanceBalanceSheet');//资产负债表查询
//            $routeCollector->addRoute(['GET', 'POST'], '/getAuthentication', $prefix . 'getAuthentication');//
            $routeCollector->addRoute(['GET', 'POST'], '/getEssential', $prefix . 'getEssential');//
            $routeCollector->addRoute(['GET', 'POST'], '/getIncometaxMonthlyDeclaration', $prefix . 'getIncometaxMonthlyDeclaration');//
            $routeCollector->addRoute(['GET', 'POST'], '/getIncometaxAnnualReport', $prefix . 'getIncometaxAnnualReport');//
            $routeCollector->addRoute(['GET', 'POST'], '/getVatReturn', $prefix . 'getVatReturn');//
            $routeCollector->addRoute(['GET', 'POST'], '/getInvoiceMain', $prefix . 'getInvoiceMain');//
            $routeCollector->addRoute(['GET', 'POST'], '/getInvoiceGoods', $prefix . 'getInvoiceGoods');//
        });

        return true;
    }

    private function XinDongRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/XinDong/XinDongController/';

        $routeCollector->addGroup('/xd', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/ztbList101', $prefix . 'ztbList101');//启客招投标列表
            $routeCollector->addRoute(['GET', 'POST'], '/ztbDetail101', $prefix . 'ztbDetail101');//启客招投标详情
            $routeCollector->addRoute(['GET', 'POST'], '/pendingEnt', $prefix . 'pendingEnt');
            $routeCollector->addRoute(['GET', 'POST'], '/sendSms', $prefix . 'sendSms');
            $routeCollector->addRoute(['GET', 'POST'], '/getBankInfo', $prefix . 'getBankInfo');
            $routeCollector->addRoute(['GET', 'POST'], '/getProductStandard', $prefix . 'getProductStandard');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBaseData', $prefix . 'getFinanceBaseData');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceOriginal', $prefix . 'getFinanceOriginal');

            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBaseDataSQ', $prefix . 'getFinanceBaseDataSQ');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBaseDataSQ20230706', $prefix . 'getFinanceBaseDataSQ20230706');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBaseDataYBR', $prefix . 'getFinanceBaseDataYBR');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBaseDataZW', $prefix . 'getFinanceBaseDataZW');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBaseDataTZ', $prefix . 'getFinanceBaseDataTZ');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBaseDataXSY', $prefix . 'getFinanceBaseDataXSY');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBaseDataLLJ', $prefix . 'getFinanceBaseDataLLJ');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBaseDataTC', $prefix . 'getFinanceBaseDataTC');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBaseDataQX', $prefix . 'getFinanceBaseDataQX');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBaseDataDLBT', $prefix . 'getFinanceBaseDataDLBT');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBaseDataGLD', $prefix . 'getFinanceBaseDataGLD');

            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBaseMergeData', $prefix . 'getFinanceBaseMergeData');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceCalData', $prefix . 'getFinanceCalData');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceCalMergeData', $prefix . 'getFinanceCalMergeData');
            $routeCollector->addRoute(['GET', 'POST'], '/getEntLianXi', $prefix . 'getEntLianXi');
            $routeCollector->addRoute(['GET', 'POST'], '/getLessCredit', $prefix . 'getLessCredit');//失信记录
            $routeCollector->addRoute(['GET', 'POST'], '/getEndCase', $prefix . 'getEndCase');//终本案件
            $routeCollector->addRoute(['GET', 'POST'], '/getLiquidate', $prefix . 'getLiquidate');//终本案件
            $routeCollector->addRoute(['GET', 'POST'], '/getCancledate', $prefix . 'getCancledate');//终本案件 getCancledate
            $routeCollector->addRoute(['GET', 'POST'], '/BankruptcyCheck', $prefix . 'BankruptcyCheck');//破产重整核查
            $routeCollector->addRoute(['GET', 'POST'], '/superSearch', $prefix . 'superSearch');
            $routeCollector->addRoute(['GET', 'POST'], '/logisticsSearch', $prefix . 'logisticsSearch');
            $routeCollector->addRoute(['GET', 'POST'], '/invEntList', $prefix . 'invEntList');//企业五要素
            $routeCollector->addRoute(['GET', 'POST'], '/invEntListWithoutAuth', $prefix . 'invEntListWithoutAuth');//企业五要素
            $routeCollector->addRoute(['GET', 'POST'], '/getCpwsList', $prefix . 'getCpwsList');//裁判文书列表
            $routeCollector->addRoute(['GET', 'POST'], '/getCpwsDetail', $prefix . 'getCpwsDetail');//裁判文书详情
            $routeCollector->addRoute(['GET', 'POST'], '/getKtggList', $prefix . 'getKtggList');//开庭公告列表
            $routeCollector->addRoute(['GET', 'POST'], '/getKtggDetail', $prefix . 'getKtggDetail');//开庭公告详情
            $routeCollector->addRoute(['GET', 'POST'], '/getFyggList', $prefix . 'getFyggList');//法院公告列表
            $routeCollector->addRoute(['GET', 'POST'], '/getFyggDetail', $prefix . 'getFyggDetail');//法院公告详情
            $routeCollector->addRoute(['GET', 'POST'], '/getSxbzxr', $prefix . 'getSxbzxr');//失信被执行人
            $routeCollector->addRoute(['GET', 'POST'], '/getBzxr', $prefix . 'getBzxr');//被执行人
            $routeCollector->addRoute(['GET', 'POST'], '/getJobInfo', $prefix . 'getJobInfo');//招聘信息
            $routeCollector->addRoute(['GET', 'POST'], '/getJobDetail', $prefix . 'getJobDetail');//招聘信息
            $routeCollector->addRoute(['GET', 'POST'], '/getInv', $prefix . 'getInv');//大象发票
            $routeCollector->addRoute(['GET', 'POST'], '/vcQueryList', $prefix . 'vcQueryList');//投融快讯
            $routeCollector->addRoute(['GET', 'POST'], '/vcQueryDetail', $prefix . 'vcQueryDetail');//投融快讯
            $routeCollector->addRoute(['GET', 'POST'], '/getNaCaoRegisterInfo', $prefix . 'getNaCaoRegisterInfo');//非企业信息
            $routeCollector->addRoute(['GET', 'POST'], '/getEntDetail', $prefix . 'getEntDetail');//企业详情
            $routeCollector->addRoute(['GET', 'POST'], '/getEntNicName', $prefix . 'getEntNicName');//
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceDataTwo', $prefix . 'getFinanceDataTwo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceDataForApi', $prefix . 'getFinanceDataForApi');//
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceDataXD', $prefix . 'getFinanceDataXD');//
            $routeCollector->addRoute(['GET', 'POST'], '/getBidInfo', $prefix . 'getBidInfo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyList', $prefix . 'getCompanyList');//
            $routeCollector->addRoute(['GET', 'POST'], '/getNicCode', $prefix . 'getNicCode');//
            $routeCollector->addRoute(['GET', 'POST'], '/searchClue', $prefix . 'searchClue');//
            $routeCollector->addRoute(['GET', 'POST'], '/collectInvoice', $prefix . 'collectInvoice');//金财发票归集
            $routeCollector->addRoute(['GET', 'POST'], '/getInvoice', $prefix . 'getInvoice');//金财发票提取
            $routeCollector->addRoute(['GET', 'POST'], '/invCertification', $prefix . 'invCertification');//金财发票认证
            $routeCollector->addRoute(['GET', 'POST'], '/getEntInfoByName', $prefix . 'getEntInfoByName');//
            $routeCollector->addRoute(['GET', 'POST'], '/testCsp', $prefix . 'testCsp');//
            $routeCollector->addRoute(['GET', 'POST'], '/get24Month', $prefix . 'get24Month');//
            $routeCollector->addRoute(['GET', 'POST'], '/getEntAddress', $prefix . 'getEntAddress');//
            $routeCollector->addRoute(['GET', 'POST'], '/getEnterprise', $prefix . 'getEnterprise');//
            $routeCollector->addRoute(['GET', 'POST'], '/getHistoricalEvolution', $prefix . 'getHistoricalEvolution');//历史沿革
            $routeCollector->addRoute(['GET', 'POST'], '/createEntReportE', $prefix . 'createEntReportE');//对外报告
            $routeCollector->addRoute(['GET', 'POST'], '/createEntReportD', $prefix . 'createEntReportD');//对外报告
            $routeCollector->addRoute(['GET', 'POST'], '/getEntMarketInfo', $prefix . 'getEntMarketInfo');//企业上市信息
            $routeCollector->addRoute(['GET', 'POST'], '/getEntLiquidation', $prefix . 'getEntLiquidation');//清算
            $routeCollector->addRoute(['GET', 'POST'], '/fuzzyMatchEntName', $prefix . 'fuzzyMatchEntName');//
            $routeCollector->addRoute(['GET', 'POST'], '/getPersonalInformation', $prefix . 'getPersonalInformation');//

            //获取本库中的数据接口
            $routeCollector->addRoute(['GET', 'POST'], '/getCncaRzGltx_h', $prefix . 'getCncaRzGltx_h');//认监委-ISO管理体系认证
            $routeCollector->addRoute(['GET', 'POST'], '/getCaseAll_h', $prefix . 'getCaseAll_h');//工商-行政处罚
            $routeCollector->addRoute(['GET', 'POST'], '/getCaseCheck_h', $prefix . 'getCaseCheck_h');//工商-抽查检查信息
            $routeCollector->addRoute(['GET', 'POST'], '/getCaseYzwfsx_h', $prefix . 'getCaseYzwfsx_h');//工商-严重违法失信
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyAbnormity_h', $prefix . 'getCompanyAbnormity_h');// 工商-经营异常
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyBasic_h', $prefix . 'getCompanyBasic_h');//获取企业基本信息
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyAr_h', $prefix . 'getCompanyAr_h');//工商-年报-主表
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyArForinvestment_h', $prefix . 'getCompanyArForinvestment_h');// 工商-年报-对外投资
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyArAlterstockinfo_h', $prefix . 'getCompanyArAlterstockinfo_h');//工商-年报股权变更
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyArCapital_h', $prefix . 'getCompanyArCapital_h');// 工商-年报出资
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyArAsset_h', $prefix . 'getCompanyArAsset_h');// 工商-年报-资产
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyArForguaranteeinfo_h', $prefix . 'getCompanyArForguaranteeinfo_h');//  工商-年报-对外提供保证担保信息
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyArSocialfee_h', $prefix . 'getCompanyArSocialfee_h');// 工商-年报-社保信息
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyArWebsiteinfo_h', $prefix . 'getCompanyArWebsiteinfo_h');//  工商-年报-网站或网店信息
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyCancelInfo_h', $prefix . 'getCompanyCancelInfo_h');//  工商-注吊销信息
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyFiliation_h', $prefix . 'getCompanyFiliation_h');// 工商-分支机构
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyHistoryName_h', $prefix . 'getCompanyHistoryName_h');// 大数据-曾用名表
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyInv_h', $prefix . 'getCompanyInv_h');// 工商-企业股东
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyManager_h', $prefix . 'getCompanyManager_h');// 工商-企业主要人员
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyArModify_h', $prefix . 'getCompanyArModify_h');// 工商-年报变更信息

            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyCertificate_h', $prefix . 'getCompanyCertificate_h');// 工商-行政许可
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyHistoryInv_h', $prefix . 'getCompanyHistoryInv_h');// 大数据-股权轨迹表
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyHistoryManager_h', $prefix . 'getCompanyHistoryManager_h');// 大数据-历史高管表
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyInvestment_h', $prefix . 'getCompanyInvestment_h');// 工商-对外投资信息
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyIpr_h', $prefix . 'getCompanyIpr_h');// 工商-知识产权出质
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyIprChange_h', $prefix . 'getCompanyIprChange_h');// 工商-知识产权出质-变更信息
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyLiquidation_h', $prefix . 'getCompanyLiquidation_h');// 工商-清算信息
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyModify_h', $prefix . 'getCompanyModify_h');// 工商-公司变更信息
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyMort_h', $prefix . 'getCompanyMort_h');// 工商-动产抵押
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyMortChange_h', $prefix . 'getCompanyMortChange_h');// 工商-动产抵押-变更信息
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyMortPawn_p', $prefix . 'getCompanyMortPawn_p');// 工商-动产抵押-抵押物信息
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyMortPeople_h', $prefix . 'getCompanyMortPeople_h');// 工商-动产抵押-抵押权人信息
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyStockImpawn_h', $prefix . 'getCompanyStockImpawn_h');// 工商-股权质押
            $routeCollector->addRoute(['GET', 'POST'], '/getListedIndexLLJ', $prefix . 'getListedIndexLLJ');//
            $routeCollector->addRoute(['GET', 'POST'], '/getCommodityCode', $prefix . 'getCommodityCode');
            $routeCollector->addRoute(['GET', 'POST'], '/getFeatures', $prefix . 'getFeatures');//二次特征分数

            //鲸准
            $routeCollector->addRoute(['GET', 'POST'], '/enterpriseList', $prefix . 'enterpriseList');// 投资事件
            $routeCollector->addRoute(['GET', 'POST'], '/investmentList', $prefix . 'investmentList');// 公司融资事件
            $routeCollector->addRoute(['GET', 'POST'], '/searchComs', $prefix . 'searchComs');// 企业搜索
            $routeCollector->addRoute(['GET', 'POST'], '/getFengXian', $prefix . 'getFengXian');// 获取企业的风险分


            $routeCollector->addRoute(['GET', 'POST'], '/obtainFpInfoNew', $prefix . 'obtainFpInfoNew');//  s


        });

        return true;
    }

    private function FaYanYuanRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/FaYanYuan/FaYanYuanController/';

        $routeCollector->addGroup('/fyy', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/entout/org', $prefix . 'entoutOrg');
            $routeCollector->addRoute(['GET', 'POST'], '/entout/people', $prefix . 'entoutPeople');
            $routeCollector->addRoute(['GET', 'POST'], '/sxbzxr/org', $prefix . 'sxbzxrOrg');
            $routeCollector->addRoute(['GET', 'POST'], '/sxbzxr/people', $prefix . 'sxbzxrPeople');
            $routeCollector->addRoute(['GET', 'POST'], '/xgbzxr/org', $prefix . 'xgbzxrOrg');
            $routeCollector->addRoute(['GET', 'POST'], '/xgbzxr/people', $prefix . 'xgbzxrPeople');
        });

        return true;
    }

    private function YunMaTongRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/YunMaTong/YunMaTongController/';

        $routeCollector->addGroup('/ymt', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/bankCardInfo', $prefix . 'bankCardInfo');
        });
    }

    private function FaHaiRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/FaHai/FaHaiController/';

        $routeCollector->addGroup('/fh', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getKtgg', $prefix . 'getKtgg');
            $routeCollector->addRoute(['GET', 'POST'], '/getFygg', $prefix . 'getFygg');
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyXin', $prefix . 'getSatpartyXin');
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyReg', $prefix . 'getSatpartyReg');//税务登记列表

            $routeCollector->addRoute(['GET', 'POST'], '/xingZhengPunishList', $prefix . 'xingZhengPunishList');
            $routeCollector->addRoute(['GET', 'POST'], '/xingZhengPunishDetails', $prefix . 'xingZhengPunishDetails');
            $routeCollector->addRoute(['GET', 'POST'], '/yinJianHuiPunishNoticeList', $prefix . 'yinJianHuiPunishNoticeList');
            $routeCollector->addRoute(['GET', 'POST'], '/yinJianHuiPunishNoticeDetail', $prefix . 'yinJianHuiPunishNoticeDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/zhengJianHuiPunishNoticeList', $prefix . 'zhengJianHuiPunishNoticeList');
            $routeCollector->addRoute(['GET', 'POST'], '/zhengJianHuiPunishNoticeDetail', $prefix . 'zhengJianHuiPunishNoticeDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/zhengJianHuiLicenseList', $prefix . 'zhengJianHuiLicenseList');
            $routeCollector->addRoute(['GET', 'POST'], '/zhengJianHuiLicenseDetail', $prefix . 'zhengJianHuiLicenseDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/waiHuiJuPunishList', $prefix . 'waiHuiJuPunishList');
            $routeCollector->addRoute(['GET', 'POST'], '/waiHuiJuPunishDetail', $prefix . 'waiHuiJuPunishDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/waiHuiJuLicenseList', $prefix . 'waiHuiJuLicenseList');
            $routeCollector->addRoute(['GET', 'POST'], '/waiHuiJuLicenseDetail', $prefix . 'waiHuiJuLicenseDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/faYuanNoticeList', $prefix . 'faYuanNoticeList');
            $routeCollector->addRoute(['GET', 'POST'], '/faYuanNoticeDetail', $prefix . 'faYuanNoticeDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/kaiTingNoticeList', $prefix . 'kaiTingNoticeList');
            $routeCollector->addRoute(['GET', 'POST'], '/kaiTingNoticeDetail', $prefix . 'kaiTingNoticeDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/caiPanWenShuList', $prefix . 'caiPanWenShuList');
            $routeCollector->addRoute(['GET', 'POST'], '/caiPanWenShuDetail', $prefix . 'caiPanWenShuDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/zhiXingGongGaoList', $prefix . 'zhiXingGongGaoList');
            $routeCollector->addRoute(['GET', 'POST'], '/zhiXingGongGaoDetail', $prefix . 'zhiXingGongGaoDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/shiXinGongGaoList', $prefix . 'shiXinGongGaoList');
            $routeCollector->addRoute(['GET', 'POST'], '/shiXinGongGaoDetail', $prefix . 'shiXinGongGaoDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/beiZhiXingRenList', $prefix . 'beiZhiXingRenList');
            $routeCollector->addRoute(['GET', 'POST'], '/baoZhengJinZhiYaList', $prefix . 'baoZhengJinZhiYaList');
            $routeCollector->addRoute(['GET', 'POST'], '/baoZhengJinZhiYaDetail', $prefix . 'baoZhengJinZhiYaDetail');

            $routeCollector->addRoute(['GET', 'POST'], '/getKtggDetail', $prefix . 'getKtggDetail');//开庭公告
            $routeCollector->addRoute(['GET', 'POST'], '/getFyggDetail', $prefix . 'getFyggDetail');//法院公告
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyXinDetail', $prefix . 'getSatpartyXinDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyRegDetail', $prefix . 'getSatpartyRegDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/getCpws', $prefix . 'getCpws');
            $routeCollector->addRoute(['GET', 'POST'], '/getZxgg', $prefix . 'getZxgg');
            $routeCollector->addRoute(['GET', 'POST'], '/getShixin', $prefix . 'getShixin');
            $routeCollector->addRoute(['GET', 'POST'], '/getSifacdkList', $prefix . 'getSifacdkList');
            $routeCollector->addRoute(['GET', 'POST'], '/getSifacdkDetail', $prefix . 'getSifacdkDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyZdwQtdcdsrList', $prefix . 'getCompanyZdwQtdcdsrList');
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyZdwQtdcdsrDetail', $prefix . 'getCompanyZdwQtdcdsrDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyZdwCdzydsrList', $prefix . 'getCompanyZdwCdzydsrList');
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyZdwCdzydsrDetail', $prefix . 'getCompanyZdwCdzydsrDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyZdwBzjzydsrList', $prefix . 'getCompanyZdwBzjzydsrList');
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyZdwBzjzydsrDetail', $prefix . 'getCompanyZdwBzjzydsrDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/getJudicialSaleList', $prefix . 'getJudicialSaleList');
            $routeCollector->addRoute(['GET', 'POST'], '/getCpwsDetail', $prefix . 'getCpwsDetail');//裁判文书
            $routeCollector->addRoute(['GET', 'POST'], '/getShixinDetail', $prefix . 'getShixinDetail');//裁判文书
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyChufa', $prefix . 'getSatpartyChufa');
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyChufaDetail', $prefix . 'getSatpartyChufaDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyQs', $prefix . 'getSatpartyQs');
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyQsDetail', $prefix . 'getSatpartyQsDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyFzc', $prefix . 'getSatpartyFzc');
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyFzcDetail', $prefix . 'getSatpartyFzcDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyXuke', $prefix . 'getSatpartyXuke');
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyXukeDetail', $prefix . 'getSatpartyXukeDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/getPbcparty', $prefix . 'getPbcparty');
            $routeCollector->addRoute(['GET', 'POST'], '/getPbcpartyCbrc', $prefix . 'getPbcpartyCbrc');
            $routeCollector->addRoute(['GET', 'POST'], '/getPbcpartyCsrcChufa', $prefix . 'getPbcpartyCsrcChufa');
            $routeCollector->addRoute(['GET', 'POST'], '/getPbcpartyCsrcXkpf', $prefix . 'getPbcpartyCsrcXkpf');
            $routeCollector->addRoute(['GET', 'POST'], '/getSafeChufa', $prefix . 'getSafeChufa');
            $routeCollector->addRoute(['GET', 'POST'], '/getSafeXuke', $prefix . 'getSafeXuke');

        });

        return true;
    }

    private function MaYiRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/MaYi/MaYiController/';

        $routeCollector->addGroup('/my', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/invEntList', $prefix . 'invEntList');
            $routeCollector->addRoute(['GET', 'POST'], '/invSelectAuth', $prefix . 'invSelectAuth');
        });

        return true;
    }

    private function QiXiangYunRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/QiXiangYun/QiXiangYunController/';

        $routeCollector->addGroup('/qxy', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/cySync', $prefix . 'cySync');
            $routeCollector->addRoute(['GET', 'POST'], '/ocr', $prefix . 'ocr');
            $routeCollector->addRoute(['GET', 'POST'], '/createEnt', $prefix . 'createEnt');
            $routeCollector->addRoute(['GET', 'POST'], '/getInv', $prefix . 'getInv');
            $routeCollector->addRoute(['GET', 'POST'], '/getFpxzStatus', $prefix . 'getFpxzStatus');
            $routeCollector->addRoute(['GET', 'POST'], '/getCjYgxByFplxs', $prefix . 'getCjYgxByFplxs');
            $routeCollector->addRoute(['GET', 'POST'], '/getGxgxztStatus', $prefix . 'getGxgxztStatus');
        });

        return true;
    }

    private function LiuLengJingRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/LiuLengJing/LiuLengJingController/';

        $routeCollector->addGroup('/llj', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/patentCnBasics', $prefix . 'patentCnBasics');
            $routeCollector->addRoute(['GET', 'POST'], '/patentCnIndexHit', $prefix . 'patentCnIndexHit');
        });

        return true;
    }

    private function YongTaiRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/YongTai/YongTaiController/';

        $routeCollector->addGroup('/yt', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getBranch', $prefix . 'getBranch');
            $routeCollector->addRoute(['GET', 'POST'], '/getHolder', $prefix . 'getHolder');
            $routeCollector->addRoute(['GET', 'POST'], '/getHolderChange', $prefix . 'getHolderChange');
            $routeCollector->addRoute(['GET', 'POST'], '/getChangeinfo', $prefix . 'getChangeinfo');
            $routeCollector->addRoute(['GET', 'POST'], '/getBaseinfo', $prefix . 'getBaseinfo');
            $routeCollector->addRoute(['GET', 'POST'], '/getEnterpriseTicketQuery', $prefix . 'getEnterpriseTicketQuery');
            $routeCollector->addRoute(['GET', 'POST'], '/getStaff', $prefix . 'getStaff');
            $routeCollector->addRoute(['GET', 'POST'], '/getSearch', $prefix . 'getSearch');
            $routeCollector->addRoute(['GET', 'POST'], '/getHistorynames', $prefix . 'getHistorynames');
            $routeCollector->addRoute(['GET', 'POST'], '/getTaxescode', $prefix . 'getTaxescode');
            $routeCollector->addRoute(['GET', 'POST'], '/getParentcompany', $prefix . 'getParentcompany');
            $routeCollector->addRoute(['GET', 'POST'], '/getEciother', $prefix . 'getEciother');
            $routeCollector->addRoute(['GET', 'POST'], '/getAnnualreport', $prefix . 'getAnnualreport');
            $routeCollector->addRoute(['GET', 'POST'], '/getBaseinfop', $prefix . 'getBaseinfop');
            $routeCollector->addRoute(['GET', 'POST'], '/getBaseinfos', $prefix . 'getBaseinfos');
            $routeCollector->addRoute(['GET', 'POST'], '/getSpecial', $prefix . 'getSpecial');
            $routeCollector->addRoute(['GET', 'POST'], '/getInverst', $prefix . 'getInverst');
            $routeCollector->addRoute(['GET', 'POST'], '/getComverify', $prefix . 'getComverify');
            $routeCollector->addRoute(['GET', 'POST'], '/getContact', $prefix . 'getContact');
        });

        return true;
    }

    private function ShuMengRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/ShuMeng/ShuMengController/';

        $routeCollector->addGroup('/sm', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getBidsResult_c', $prefix . 'getBidsResult_c');
            $routeCollector->addRoute(['GET', 'POST'], '/getBidsResult_z', $prefix . 'getBidsResult_z');
        });

        return true;
    }

    private function BaiXiangRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/BaiXiang/BaiXiangController/';

        $routeCollector->addGroup('/bx', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getDptEnterpriseMedicineDetailList', $prefix . 'getDptEnterpriseMedicineDetailList');
            $routeCollector->addRoute(['GET', 'POST'], '/getDptDrugDetail', $prefix . 'getDptDrugDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/getDptHospitalDetail', $prefix . 'getDptHospitalDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/getDptInstrumentDetail', $prefix . 'getDptInstrumentDetail');
        });

        return true;
    }

    private function YiZhangTongRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/YiZhangTong/YiZhangTongController/';

        $routeCollector->addGroup('/yzt', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getProductList', $prefix . 'getProductList');
            $routeCollector->addRoute(['GET', 'POST'], '/getLogin', $prefix . 'getLogin');
            $routeCollector->addRoute(['GET', 'POST'], '/getOrderList', $prefix . 'getOrderList');
        });

        return true;
    }

    private function FaDaDaRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/FaDaDa/FaDaDaController/';

        $routeCollector->addGroup('/fdd', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getAuthFile', $prefix . 'getAuthFile');
            $routeCollector->addRoute(['GET', 'POST'], '/getAuthFileForAnt', $prefix . 'getAuthFileForAnt');
            $routeCollector->addRoute(['GET', 'POST'], '/getAuthFileByFile', $prefix . 'getAuthFileByFile');
        });

        return true;
    }

    private function Notify(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/Notify/NotifyController/';

        $routeCollector->addGroup('/notify', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/creditAuthUrl', $prefix . 'creditAuthUrl');
        });

        return true;
    }

    private function SaibopengkeAdmin(RouteCollector $routeCollector): bool
    {
        $prefix = '/Business/Admin/SaibopengkeAdmin/SaibopengkeAdminController/';
        $routeCollector->addGroup('/sbpk', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/uploadEntList', $prefix . 'uploadEntList');
        });
        return true;
    }

    private function NanJingXiaoAnV1(RouteCollector $routeCollector): bool
    {
        $prefix = '/Business/Provide/NanJingXiaoAn/NanJingXiaoAnController/';
        $routeCollector->addGroup('/njxa', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/generalMobileInfo', $prefix . 'generalMobileInfo');
        });
        return true;
    }

}
