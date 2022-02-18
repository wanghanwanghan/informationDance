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
            $routeCollector->addRoute(['GET', 'POST'], '/tenderSearch', $prefix . 'tenderSearch');//招投标

        });

        return true;
    }


    private function TaoShuRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/TaoShu/TaoShuController/';

        $routeCollector->addGroup('/ts', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/lawPersonInvestmentInfo', $prefix . 'lawPersonInvestmentInfo');//法人对外投资
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
            $routeCollector->addRoute(['GET', 'POST'], '/getInvoiceCheck', $prefix . 'getInvoiceCheck');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceIncomeStatementAnnualReport', $prefix . 'getFinanceIncomeStatementAnnualReport');//利润表--年报查询
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceIncomeStatement', $prefix . 'getFinanceIncomeStatement');//利润表查询
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBalanceSheetAnnual', $prefix . 'getFinanceBalanceSheetAnnual');//资产负债表--年度查询
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBalanceSheet', $prefix . 'getFinanceBalanceSheet');//资产负债表查询
        });

        return true;
    }

    private function XinDongRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/XinDong/XinDongController/';

        $routeCollector->addGroup('/xd', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/sendSms', $prefix . 'sendSms');
            $routeCollector->addRoute(['GET', 'POST'], '/getBankInfo', $prefix . 'getBankInfo');
            $routeCollector->addRoute(['GET', 'POST'], '/getProductStandard', $prefix . 'getProductStandard');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBaseData', $prefix . 'getFinanceBaseData');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceOriginal', $prefix . 'getFinanceOriginal');

            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBaseDataSQ', $prefix . 'getFinanceBaseDataSQ');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBaseDataZW', $prefix . 'getFinanceBaseDataZW');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBaseDataTZ', $prefix . 'getFinanceBaseDataTZ');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBaseDataXSY', $prefix . 'getFinanceBaseDataXSY');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBaseDataLLJ', $prefix . 'getFinanceBaseDataLLJ');

            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBaseMergeData', $prefix . 'getFinanceBaseMergeData');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceCalData', $prefix . 'getFinanceCalData');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceCalMergeData', $prefix . 'getFinanceCalMergeData');
            $routeCollector->addRoute(['GET', 'POST'], '/getEntLianXi', $prefix . 'getEntLianXi');
            $routeCollector->addRoute(['GET', 'POST'], '/superSearch', $prefix . 'superSearch');
            $routeCollector->addRoute(['GET', 'POST'], '/logisticsSearch', $prefix . 'logisticsSearch');
            $routeCollector->addRoute(['GET', 'POST'], '/invEntList', $prefix . 'invEntList');//企业五要素

            $routeCollector->addRoute(['GET', 'POST'], '/getCpwsList', $prefix . 'getCpwsList');//裁判文书列表
            $routeCollector->addRoute(['GET', 'POST'], '/getCpwsDetail', $prefix . 'getCpwsDetail');//裁判文书详情
            $routeCollector->addRoute(['GET', 'POST'], '/getKtggList', $prefix . 'getKtggList');//开庭公告列表
            $routeCollector->addRoute(['GET', 'POST'], '/getKtggDetail', $prefix . 'getKtggDetail');//开庭公告详情
            $routeCollector->addRoute(['GET', 'POST'], '/getFyggList', $prefix . 'getFyggList');//法院公告列表
            $routeCollector->addRoute(['GET', 'POST'], '/getFyggDetail', $prefix . 'getFyggDetail');//法院公告详情
            $routeCollector->addRoute(['GET', 'POST'], '/getSxbzxr', $prefix . 'getSxbzxr');//失信被执行人
            $routeCollector->addRoute(['GET', 'POST'], '/getBzxr', $prefix . 'getBzxr');//被执行人
            $routeCollector->addRoute(['GET', 'POST'], '/getJobInfo', $prefix . 'getJobInfo');//招聘信息
            $routeCollector->addRoute(['GET', 'POST'], '/getInv', $prefix . 'getInv');//大象发票
            $routeCollector->addRoute(['GET', 'POST'], '/vcQueryList', $prefix . 'vcQueryList');//投融快讯
            $routeCollector->addRoute(['GET', 'POST'], '/vcQueryDetail', $prefix . 'vcQueryDetail');//投融快讯
            $routeCollector->addRoute(['GET', 'POST'], '/getNaCaoRegisterInfo', $prefix . 'getNaCaoRegisterInfo');//非企业信息
            $routeCollector->addRoute(['GET', 'POST'], '/getEntDetail', $prefix . 'getEntDetail');//企业详情
            $routeCollector->addRoute(['GET', 'POST'], '/getEntNicName', $prefix . 'getEntNicName');//
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceDataTwo', $prefix . 'getFinanceDataTwo');//
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceDataForApi', $prefix . 'getFinanceDataForApi');//
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyList', $prefix . 'getCompanyList');//
            $routeCollector->addRoute(['GET', 'POST'], '/getNicCode', $prefix . 'getNicCode');//

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

            $routeCollector->addRoute(['GET', 'POST'], '/getKtggDetail', $prefix . 'getKtggDetail');//开庭公告
            $routeCollector->addRoute(['GET', 'POST'], '/getFyggDetail', $prefix . 'getFyggDetail');//法院公告
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyXinDetail', $prefix . 'getSatpartyXinDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyRegDetail', $prefix . 'getSatpartyRegDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/getCpws', $prefix . 'getCpws');
            $routeCollector->addRoute(['GET', 'POST'], '/getZxgg', $prefix . 'getZxgg');
            $routeCollector->addRoute(['GET', 'POST'], '/getShixin', $prefix . 'getShixin');
            $routeCollector->addRoute(['GET', 'POST'], '/getSifacdk', $prefix . 'getSifacdk');
            $routeCollector->addRoute(['GET', 'POST'], '/getJudicialSaleList', $prefix . 'getJudicialSaleList');

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

}
