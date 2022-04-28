<?php

namespace App\HttpController;

use EasySwoole\Http\AbstractInterface\AbstractRouter;
use FastRoute\RouteCollector;

class Router extends AbstractRouter
{
    function initialize(RouteCollector $routeCollector)
    {
        //全局模式拦截下,路由将只匹配Router.php中的控制器方法响应,将不会执行框架的默认解析
        $this->setGlobalMode(true);

        $routeCollector->addGroup('/api/v1', function (RouteCollector $routeCollector) {
            $this->CommonRouterV1($routeCollector);//公共功能
            $this->UserRouterV1($routeCollector);//用户相关
            $this->XinDongRouterV1($routeCollector);//信动
            $this->LongDunRouterV1($routeCollector);//龙盾
            $this->TaoShuRouterV1($routeCollector);//淘数
            $this->FaYanYuanRouterV1($routeCollector);//法研院
            $this->QianQiRouterV1($routeCollector);//乾启
            $this->LongXinRouterV1($routeCollector);//龙信
            $this->YuanSuRouterV1($routeCollector);//元素
            $this->GuoPiaoRouterV1($routeCollector);//国票
            $this->HuoYanRouterV1($routeCollector);//火眼
            $this->Notify($routeCollector);//通知
            $this->ExportExcelRouterV1($routeCollector);//导出excel
            $this->ExportWordRouterV1($routeCollector);//导出word
            $this->ExportPdfRouterV1($routeCollector);//导出pdf
            $this->TestRouterV1($routeCollector);//测试路由
            $this->ZhiChiRouterV1($routeCollector);//智齿科技
        });

        $routeCollector->addGroup('/admin/v1', function (RouteCollector $routeCollector) {
            AdminRouter::getInstance()->addRouterV1($routeCollector);
        });

        $routeCollector->addGroup('/admin_provide/v1', function (RouteCollector $routeCollector) {
            AdminProvideRouter::getInstance()->addRouterV1($routeCollector);
        });

        $routeCollector->addGroup('/admin_new/v1', function (RouteCollector $routeCollector) {
            AdminNewRouter::getInstance()->addRouterV1($routeCollector);
        });

        $routeCollector->addGroup('/provide/v1', function (RouteCollector $routeCollector) {
            ProvideRouter::getInstance()->addRouterV1($routeCollector);
        });

        $routeCollector->addGroup('/admin_roles/v1', function (RouteCollector $routeCollector) {
            AdminRoles::getInstance()->addRouterV1($routeCollector);
        });
    }

    private function ZhiChiRouterV1(RouteCollector $routeCollector){
        $prefix = '/Business/Api/ZhiChi/ZhiChiController/';
        $routeCollector->addGroup('/zc', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/directUrl', $prefix . 'directUrl');
        });
    }
    private function CommonRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Api/Common/CommonController/';

        $routeCollector->addGroup('/comm', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/image/upload', $prefix . 'imageUpload');//图片上传
            $routeCollector->addRoute(['GET', 'POST'], '/file/upload', $prefix . 'fileUpload');//文件上传
            $routeCollector->addRoute(['GET', 'POST'], '/create/image/verifyCode', $prefix . 'imageVerifyCode');//创建图片验证码
            $routeCollector->addRoute(['GET', 'POST'], '/create/sms/verifyCode', $prefix . 'smsVerifyCode');//发送手机验证码
            $routeCollector->addRoute(['GET', 'POST'], '/userLngLatUpload', $prefix . 'userLngLatUpload');//上传用户经纬度
            $routeCollector->addRoute(['GET', 'POST'], '/refundToWallet', $prefix . 'refundToWallet');//退钱到钱包
            $routeCollector->addRoute(['GET', 'POST'], '/ocrForBaiDu', $prefix . 'ocrForBaiDu');//百度ocr
            $routeCollector->addRoute(['GET', 'POST'], '/addressToLatLng', $prefix . 'addressToLatLng');//百度地址转换
            $routeCollector->addRoute(['GET', 'POST'], '/ocrForHeHe', $prefix . 'ocrForHeHe');//合合ocr
            $routeCollector->addRoute(['GET', 'POST'], '/ocr/queue', $prefix . 'ocrQueue');//ocr识别
        });

        return true;
    }

    private function GuoPiaoRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Api/GuoPiao/GuoPiaoController/';

        $routeCollector->addGroup('/zw', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/sendCertificateAccess', $prefix . 'sendCertificateAccess');//证书授权
            $routeCollector->addRoute(['GET', 'POST'], '/getReceiptDetailByClient', $prefix . 'getReceiptDetailByClient');//进销项发票详情（税盘）
            $routeCollector->addRoute(['GET', 'POST'], '/getReceiptDetailByCert', $prefix . 'getReceiptDetailByCert');//进销项发票详情（证书）
            $routeCollector->addRoute(['GET', 'POST'], '/getInvoiceOcr', $prefix . 'getInvoiceOcr');//发票实时ocr查验
            $routeCollector->addRoute(['GET', 'POST'], '/getInvoiceCheck', $prefix . 'getInvoiceCheck');//发票实时查验
            $routeCollector->addRoute(['GET', 'POST'], '/getAuthentication', $prefix . 'getAuthentication');//企业授权认证
            $routeCollector->addRoute(['GET', 'POST'], '/getTaxInvoice', $prefix . 'getTaxInvoice');//进销项发票统计查询
            $routeCollector->addRoute(['GET', 'POST'], '/getTaxInvoiceUpgrade', $prefix . 'getTaxInvoiceUpgrade');//进销项月度发票统计查询
            $routeCollector->addRoute(['GET', 'POST'], '/getInvoiceMain', $prefix . 'getInvoiceMain');//进销项发票信息
            $routeCollector->addRoute(['GET', 'POST'], '/getInvoiceGoods', $prefix . 'getInvoiceGoods');//进销项发票商品明细
            $routeCollector->addRoute(['GET', 'POST'], '/getEssential', $prefix . 'getEssential');//企业税务基本信息查询
            $routeCollector->addRoute(['GET', 'POST'], '/getIncometaxMonthlyDeclaration', $prefix . 'getIncometaxMonthlyDeclaration');//企业所得税--月（季）度申报表查询
            $routeCollector->addRoute(['GET', 'POST'], '/getIncometaxAnnualReport', $prefix . 'getIncometaxAnnualReport');//企业所得税--年报查询
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceIncomeStatementAnnualReport', $prefix . 'getFinanceIncomeStatementAnnualReport');//利润表--年报查询
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceIncomeStatement', $prefix . 'getFinanceIncomeStatement');//利润表查询
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBalanceSheetAnnual', $prefix . 'getFinanceBalanceSheetAnnual');//资产负债表--年度查询
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBalanceSheet', $prefix . 'getFinanceBalanceSheet');//资产负债表查询
            $routeCollector->addRoute(['GET', 'POST'], '/getVatReturn', $prefix . 'getVatReturn');//增值税申报表查询
        });

        return true;
    }

    private function HuoYanRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Api/HuoYan/HuoYanController/';

        $routeCollector->addGroup('/hy', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getData', $prefix . 'getData');
        });

        return true;
    }

    private function UserRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Api/User/UserController/';

        $routeCollector->addGroup('/user', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/addAuthEntName', $prefix . 'addAuthEntName');
            $routeCollector->addRoute(['GET', 'POST'], '/reg', $prefix . 'reg');//注册
            $routeCollector->addRoute(['GET', 'POST'], '/login', $prefix . 'login');//登录
            $routeCollector->addRoute(['GET', 'POST'], '/setLoginPassword', $prefix . 'setLoginPassword');//修改登录密码
            $routeCollector->addRoute(['GET', 'POST'], '/destroy', $prefix . 'destroyUser');//注销
            $routeCollector->addRoute(['GET', 'POST'], '/purchase/list', $prefix . 'purchaseList');//获取用户充值详情列表
            $routeCollector->addRoute(['GET', 'POST'], '/pay/list', $prefix . 'payList');//获取用户消费详情列表
            $routeCollector->addRoute(['GET', 'POST'], '/purchase/goods', $prefix . 'purchaseGoods');//获取充值商品列表
            $routeCollector->addRoute(['GET', 'POST'], '/purchase/do', $prefix . 'purchaseDo');//充值
            $routeCollector->addRoute(['GET', 'POST'], '/purchase/check', $prefix . 'purchaseCheck');//检查状态
            $routeCollector->addRoute(['GET', 'POST'], '/create/oneSaid', $prefix . 'createOneSaid');//发布一句话
            $routeCollector->addRoute(['GET', 'POST'], '/edit/oneSaid', $prefix . 'editOneSaid');//修改一句话
            $routeCollector->addRoute(['GET', 'POST'], '/get/oneSaid', $prefix . 'getOneSaid');//获取用户发布一句话
            $routeCollector->addRoute(['GET', 'POST'], '/create/supervisor', $prefix . 'createSupervisor');//创建风险监控
            $routeCollector->addRoute(['GET', 'POST'], '/del/supervisor', $prefix . 'delSupervisor');//删除风险监控
            $routeCollector->addRoute(['GET', 'POST'], '/get/supervisor', $prefix . 'getSupervisor');//获取用户风险监控数据
            $routeCollector->addRoute(['GET', 'POST'], '/get/supervisorLimit', $prefix . 'getSupervisorLimit');//获取风险阈值
            $routeCollector->addRoute(['GET', 'POST'], '/get/supervisorListByExcel', $prefix . 'getSupervisorListByExcel');//导出列表
            $routeCollector->addRoute(['GET', 'POST'], '/edit/supervisorLimit', $prefix . 'editSupervisorLimit');//修改风险阈值
            $routeCollector->addRoute(['GET', 'POST'], '/report/list', $prefix . 'getReportList');//获取报告列表
            $routeCollector->addRoute(['GET', 'POST'], '/create/authBook', $prefix . 'createAuthBook');//上传授权书后的确认按钮
            $routeCollector->addRoute(['GET', 'POST'], '/get/authBook', $prefix . 'getAuthBook');//获取用户授权书审核列表
            $routeCollector->addRoute(['GET', 'POST'], '/check/authBook', $prefix . 'checkAuthBook');//检查用户上没上传过企业授权书
            $routeCollector->addRoute(['GET', 'POST'], '/check/auth', $prefix . 'checkAuth');//
        });

        return true;
    }

    private function XinDongRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Api/XinDong/XinDongController/';

        $routeCollector->addGroup('/xd', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getCorporateShareholderRisk', $prefix . 'getCorporateShareholderRisk');//控股法人股东的司法风险
            $routeCollector->addRoute(['GET', 'POST'], '/getProductStandard', $prefix . 'getProductStandard');//产品标准
            $routeCollector->addRoute(['GET', 'POST'], '/getAssetLeads', $prefix . 'getAssetLeads');//资产线索
            $routeCollector->addRoute(['GET', 'POST'], '/getNaCaoRegisterInfo', $prefix . 'getNaCaoRegisterInfo');//非企业信息
            $routeCollector->addRoute(['GET', 'POST'], '/getFeatures', $prefix . 'getFeatures');//二次特征分数
            $routeCollector->addRoute(['GET', 'POST'], '/industryTop', $prefix . 'industryTop');//行业top
            $routeCollector->addRoute(['GET', 'POST'], '/logisticsSearch', $prefix . 'logisticsSearch');//物流搜索
            $routeCollector->addRoute(['GET', 'POST'], '/financesSearch', $prefix . 'financesSearch');//
            $routeCollector->addRoute(['GET', 'POST'], '/financesGroupSearch', $prefix . 'financesGroupSearch');//
            $routeCollector->addRoute(['GET', 'POST'], '/financesSearchResToMysql', $prefix . 'financesSearchResToMysql');//
            $routeCollector->addRoute(['GET', 'POST'], '/financesSearchHandleFengXianLabel', $prefix . 'financesSearchHandleFengXianLabel');//
            $routeCollector->addRoute(['GET', 'POST'], '/financesSearchHandleCaiWuLabel', $prefix . 'financesSearchHandleCaiWuLabel');//
            $routeCollector->addRoute(['GET', 'POST'], '/financesSearchHandleLianJieLabel', $prefix . 'financesSearchHandleLianJieLabel');//
            $routeCollector->addRoute(['GET', 'POST'], '/financesSearchEditGroupDesc', $prefix . 'financesSearchEditGroupDesc');//
            $routeCollector->addRoute(['GET', 'POST'], '/financesSearchExportDetail', $prefix . 'financesSearchExportDetail');//
            $routeCollector->addRoute(['GET', 'POST'], '/delUserGroupList', $prefix . 'delUserGroupList');//
            $routeCollector->addRoute(['GET', 'POST'], '/editGroupRemarks', $prefix . 'editGroupRemarks');//
            $routeCollector->addRoute(['GET', 'POST'], '/getVendincScale', $prefix . 'getVendincScale');//
            $routeCollector->addRoute(['GET', 'POST'], '/getSearchOption', $prefix . 'getSearchOption');// 返回接口支持的搜索项 https://api.meirixindong.com//api/v1/xd/getSearchOption
            $routeCollector->addRoute(['GET', 'POST'], '/advancedSearch', $prefix . 'advancedSearch');// 高级搜索 https://api.meirixindong.com//api/v1/xd/advancedSearch
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyBasicInfo', $prefix . 'getCompanyBasicInfo');// 获取企业基本信息 https://api.meirixindong.com//api/v1/xd/getCompanyBasicInfo
            $routeCollector->addRoute(['GET', 'POST'], '/getCpwsList', $prefix . 'getCpwsList');// 获取司法信息-裁判文书列表 https://api.meirixindong.com//api/v1/xd/getCpwsList
            $routeCollector->addRoute(['GET', 'POST'], '/getCpwsDetail', $prefix . 'getCpwsDetail');// 获取司法信息-裁判文书详情 https://api.meirixindong.com//api/v1/xd/getCpwsDetail
            $routeCollector->addRoute(['GET', 'POST'], '/getKtggList', $prefix . 'getKtggList');// 获取司法信息-裁判文书详情 https://api.meirixindong.com//api/v1/xd/getKtggList
        });

        return true;
    }

    private function LongDunRouterV1(RouteCollector $routeCollector)
    {
        $pre = '/Business/Api/LongDun/LongDunController/';

        $routeCollector->addGroup('/qcc', function (RouteCollector $routeCollector) use ($pre) {
            $routeCollector->addRoute(['GET', 'POST'], '/getEntList', $pre . 'getEntList');//模糊搜索企业
            $routeCollector->addRoute(['GET', 'POST'], '/getBasicDetailsByEntName', $pre . 'getBasicDetailsByEntName');//企业工商信息
            $routeCollector->addRoute(['GET', 'POST'], '/getSpecialEntDetails', $pre . 'getSpecialEntDetails');//律所及其他特殊基本信息
            $routeCollector->addRoute(['GET', 'POST'], '/getEntType', $pre . 'getEntType');//企业类型查询
            $routeCollector->addRoute(['GET', 'POST'], '/getBeneficiary', $pre . 'getBeneficiary');//实际控制人和控制路径
            $routeCollector->addRoute(['GET', 'POST'], '/getOpException', $pre . 'getOpException');//经营异常
            $routeCollector->addRoute(['GET', 'POST'], '/getEntFinancing', $pre . 'getEntFinancing');//融资历史
            $routeCollector->addRoute(['GET', 'POST'], '/tenderSearch', $pre . 'tenderSearch');//招投标
            $routeCollector->addRoute(['GET', 'POST'], '/landPurchaseList', $pre . 'landPurchaseList');//购地信息
            $routeCollector->addRoute(['GET', 'POST'], '/landPublishList', $pre . 'landPublishList');//土地公示
            $routeCollector->addRoute(['GET', 'POST'], '/landTransferList', $pre . 'landTransferList');//土地转让
            $routeCollector->addRoute(['GET', 'POST'], '/getRecruitmentList', $pre . 'getRecruitmentList');//招聘信息
            $routeCollector->addRoute(['GET', 'POST'], '/getQualificationList', $pre . 'getQualificationList');//建筑资质证书
            $routeCollector->addRoute(['GET', 'POST'], '/getBuildingProjectList', $pre . 'getBuildingProjectList');//建筑工程项目
            $routeCollector->addRoute(['GET', 'POST'], '/getBondList', $pre . 'getBondList');//债券
            $routeCollector->addRoute(['GET', 'POST'], '/getAdministrativeLicenseList', $pre . 'getAdministrativeLicenseList');//行政许可
            $routeCollector->addRoute(['GET', 'POST'], '/getAdministrativePenaltyList', $pre . 'getAdministrativePenaltyList');//行政处罚
            $routeCollector->addRoute(['GET', 'POST'], '/getJudicialSaleList', $pre . 'getJudicialSaleList');//司法拍卖
            $routeCollector->addRoute(['GET', 'POST'], '/getStockPledgeList', $pre . 'getStockPledgeList');//股权出质
            $routeCollector->addRoute(['GET', 'POST'], '/getChattelMortgage', $pre . 'getChattelMortgage');//动产抵押
            $routeCollector->addRoute(['GET', 'POST'], '/getLandMortgageList', $pre . 'getLandMortgageList');//土地抵押
            $routeCollector->addRoute(['GET', 'POST'], '/getAnnualReport', $pre . 'getAnnualReport');//对外担保
            $routeCollector->addRoute(['GET', 'POST'], '/getIPOGuarantee', $pre . 'getIPOGuarantee');//上市公司对外担保
            $routeCollector->addRoute(['GET', 'POST'], '/getTmSearch', $pre . 'getTmSearch');//商标
            $routeCollector->addRoute(['GET', 'POST'], '/getPatentV4Search', $pre . 'getPatentV4Search');//专利
            $routeCollector->addRoute(['GET', 'POST'], '/getSearchSoftwareCr', $pre . 'getSearchSoftwareCr');//软件著作权
            $routeCollector->addRoute(['GET', 'POST'], '/getSearchCopyRight', $pre . 'getSearchCopyRight');//作品著作权
            $routeCollector->addRoute(['GET', 'POST'], '/getSearchCertification', $pre . 'getSearchCertification');//企业证书查询
            $routeCollector->addRoute(['GET', 'POST'], '/getSearchNews', $pre . 'getSearchNews');//新闻舆情
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyWebSite', $pre . 'getCompanyWebSite');//网站信息
            $routeCollector->addRoute(['GET', 'POST'], '/getMicroblogGetList', $pre . 'getMicroblogGetList');//微博
            $routeCollector->addRoute(['GET', 'POST'], '/getECIPartnerGetList', $pre . 'getECIPartnerGetList');//股东信息
            $routeCollector->addRoute(['GET', 'POST'], '/getCourtV4SearchShiXin', $pre . 'getCourtV4SearchShiXin');//失信信息
            $routeCollector->addRoute(['GET', 'POST'], '/getCourtV4SearchZhiXing', $pre . 'getCourtV4SearchZhiXing');//被执行人
            $routeCollector->addRoute(['GET', 'POST'], '/getJudicialAssistance', $pre . 'getJudicialAssistance');//股权冻结
            $routeCollector->addRoute(['GET', 'POST'], '/getSeriousViolationList', $pre . 'getSeriousViolationList');//严重违法

            //详情系列
            $routeCollector->addRoute(['GET', 'POST'], '/tenderSearchDetail', $pre . 'tenderSearchDetail');//招投标
            $routeCollector->addRoute(['GET', 'POST'], '/landPurchaseListDetail', $pre . 'landPurchaseListDetail');//购地信息
            $routeCollector->addRoute(['GET', 'POST'], '/landPublishListDetail', $pre . 'landPublishListDetail');//土地公示
            $routeCollector->addRoute(['GET', 'POST'], '/landTransferListDetail', $pre . 'landTransferListDetail');//土地转让
            $routeCollector->addRoute(['GET', 'POST'], '/getRecruitmentListDetail', $pre . 'getRecruitmentListDetail');//招聘信息
            $routeCollector->addRoute(['GET', 'POST'], '/getQualificationListDetail', $pre . 'getQualificationListDetail');//建筑资质证书
            $routeCollector->addRoute(['GET', 'POST'], '/getBuildingProjectListDetail', $pre . 'getBuildingProjectListDetail');//建筑工程项目
            $routeCollector->addRoute(['GET', 'POST'], '/getBondListDetail', $pre . 'getBondListDetail');//债券
            $routeCollector->addRoute(['GET', 'POST'], '/getAdministrativeLicenseListDetail', $pre . 'getAdministrativeLicenseListDetail');//行政许可
            $routeCollector->addRoute(['GET', 'POST'], '/getAdministrativePenaltyListDetail', $pre . 'getAdministrativePenaltyListDetail');//行政处罚
            $routeCollector->addRoute(['GET', 'POST'], '/getJudicialSaleListDetail', $pre . 'getJudicialSaleListDetail');//司法拍卖
            $routeCollector->addRoute(['GET', 'POST'], '/getLandMortgageListDetail', $pre . 'getLandMortgageListDetail');//土地抵押
            $routeCollector->addRoute(['GET', 'POST'], '/getTmSearchDetail', $pre . 'getTmSearchDetail');//商标
            $routeCollector->addRoute(['GET', 'POST'], '/getPatentV4SearchDetail', $pre . 'getPatentV4SearchDetail');//专利
            $routeCollector->addRoute(['GET', 'POST'], '/getSearchCertificationDetail', $pre . 'getSearchCertificationDetail');//企业证书查询
            $routeCollector->addRoute(['GET', 'POST'], '/getSearchNewsDetail', $pre . 'getSearchNewsDetail');//新闻舆情
        });

        return true;
    }

    private function TaoShuRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Api/TaoShu/TaoShuController/';

        $routeCollector->addGroup('/ts', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getEntByKeyword', $prefix . 'getEntByKeyword');//企业名称检索
            $routeCollector->addRoute(['GET', 'POST'], '/getRegisterInfo', $prefix . 'getRegisterInfo');//企业基本信息
            $routeCollector->addRoute(['GET', 'POST'], '/getShareHolderInfo', $prefix . 'getShareHolderInfo');//企业股东及出资信息
            $routeCollector->addRoute(['GET', 'POST'], '/getInvestmentAbroadInfo', $prefix . 'getInvestmentAbroadInfo');//企业对外投资
            $routeCollector->addRoute(['GET', 'POST'], '/getBranchInfo', $prefix . 'getBranchInfo');//企业分支机构
            $routeCollector->addRoute(['GET', 'POST'], '/getRegisterChangeInfo', $prefix . 'getRegisterChangeInfo');//企业变更信息
            $routeCollector->addRoute(['GET', 'POST'], '/getMainManagerInfo', $prefix . 'getMainManagerInfo');//企业主要管理人员
            $routeCollector->addRoute(['GET', 'POST'], '/lawPersonInvestmentInfo', $prefix . 'lawPersonInvestmentInfo');//法人代表对外投资
            $routeCollector->addRoute(['GET', 'POST'], '/getLawPersontoOtherInfo', $prefix . 'getLawPersontoOtherInfo');//法人代表其他公司任职
            $routeCollector->addRoute(['GET', 'POST'], '/getGraphGFinalData', $prefix . 'getGraphGFinalData');//企业最终控制人
            $routeCollector->addRoute(['GET', 'POST'], '/getOperatingExceptionRota', $prefix . 'getOperatingExceptionRota');//企业经营异常
            $routeCollector->addRoute(['GET', 'POST'], '/getEquityPledgedInfo', $prefix . 'getEquityPledgedInfo');//企业股权出质列表
            $routeCollector->addRoute(['GET', 'POST'], '/getEquityPledgedDetailInfo', $prefix . 'getEquityPledgedDetailInfo');//企业股权出质详情
            $routeCollector->addRoute(['GET', 'POST'], '/getChattelMortgageInfo', $prefix . 'getChattelMortgageInfo');//企业动产抵押列表
            $routeCollector->addRoute(['GET', 'POST'], '/getChattelMortgageDetailInfo', $prefix . 'getChattelMortgageDetailInfo');//企业动产抵押详情
            $routeCollector->addRoute(['GET', 'POST'], '/getEntActualContoller', $prefix . 'getEntActualContoller');//企业实际控制人信息
            $routeCollector->addRoute(['GET', 'POST'], '/getEntAnnReportForGuaranteeInfo', $prefix . 'getEntAnnReportForGuaranteeInfo');//企业年报对外担保信息
            $routeCollector->addRoute(['GET', 'POST'], '/frbg', $prefix . 'frbg');//法人变更
            $routeCollector->addRoute(['GET', 'POST'], '/getGoodsInfo', $prefix . 'getGoodsInfo');//企业生产的流通性产品信息
            $routeCollector->addRoute(['GET', 'POST'], '/getEntScore', $prefix . 'getEntScore');//企业竞争力
        });

        return true;
    }

    private function FaYanYuanRouterV1(RouteCollector $routeCollector)
    {
        $pre = '/Business/Api/FaYanYuan/FaYanYuanController/';

        $routeCollector->addGroup('/fh', function (RouteCollector $routeCollector) use ($pre) {
            $routeCollector->addRoute(['GET', 'POST'], '/getEpbparty', $pre . 'getEpbparty');//环保处罚
            $routeCollector->addRoute(['GET', 'POST'], '/getEpbpartyJkqy', $pre . 'getEpbpartyJkqy');//重点监控企业名单
            $routeCollector->addRoute(['GET', 'POST'], '/getEpbpartyZxjc', $pre . 'getEpbpartyZxjc');//环保企业自行监测结果
            $routeCollector->addRoute(['GET', 'POST'], '/getEpbpartyHuanping', $pre . 'getEpbpartyHuanping');//环评公示数据
            $routeCollector->addRoute(['GET', 'POST'], '/getCustomQy', $pre . 'getCustomQy');//海关企业
            $routeCollector->addRoute(['GET', 'POST'], '/getCustomXuke', $pre . 'getCustomXuke');//海关许可
            $routeCollector->addRoute(['GET', 'POST'], '/getCustomCredit', $pre . 'getCustomCredit');//海关信用
            $routeCollector->addRoute(['GET', 'POST'], '/getCustomPunish', $pre . 'getCustomPunish');//海关处罚
            $routeCollector->addRoute(['GET', 'POST'], '/getKtgg', $pre . 'getKtgg');//开庭公告
            $routeCollector->addRoute(['GET', 'POST'], '/getCpws', $pre . 'getCpws');//裁判文书
            $routeCollector->addRoute(['GET', 'POST'], '/getFygg', $pre . 'getFygg');//法院公告
            $routeCollector->addRoute(['GET', 'POST'], '/getZxgg', $pre . 'getZxgg');//执行公告
            $routeCollector->addRoute(['GET', 'POST'], '/getShixin', $pre . 'getShixin');//失信公告
            $routeCollector->addRoute(['GET', 'POST'], '/getSifacdk', $pre . 'getSifacdk');//司法查封冻结扣押
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyQs', $pre . 'getSatpartyQs');//欠税公告
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyChufa', $pre . 'getSatpartyChufa');//涉税处罚公示
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyFzc', $pre . 'getSatpartyFzc');//税务非正常户公示
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyXin', $pre . 'getSatpartyXin');//纳税信用等级
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyReg', $pre . 'getSatpartyReg');//税务登记
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyXuke', $pre . 'getSatpartyXuke');//税务许可
            $routeCollector->addRoute(['GET', 'POST'], '/getPbcparty', $pre . 'getPbcparty');//央行行政处罚
            $routeCollector->addRoute(['GET', 'POST'], '/getPbcpartyCbrc', $pre . 'getPbcpartyCbrc');//银保监会处罚公示
            $routeCollector->addRoute(['GET', 'POST'], '/getPbcpartyCsrcChufa', $pre . 'getPbcpartyCsrcChufa');//证监处罚公示
            $routeCollector->addRoute(['GET', 'POST'], '/getPbcpartyCsrcXkpf', $pre . 'getPbcpartyCsrcXkpf');//证监会许可批复等级
            $routeCollector->addRoute(['GET', 'POST'], '/getSafeChufa', $pre . 'getSafeChufa');//外汇局处罚
            $routeCollector->addRoute(['GET', 'POST'], '/getSafeXuke', $pre . 'getSafeChufa');//外汇局许可
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyZdwYszkdsr', $pre . 'getCompanyZdwYszkdsr');//应收帐款
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyZdwZldjdsr', $pre . 'getCompanyZdwZldjdsr');//租赁登记
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyZdwBzjzydsr', $pre . 'getCompanyZdwBzjzydsr');//保证金质押登记
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyZdwCdzydsr', $pre . 'getCompanyZdwCdzydsr');//仓单质押
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyZdwSyqbldsr', $pre . 'getCompanyZdwSyqbldsr');//所有权保留
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyZdwQtdcdsr', $pre . 'getCompanyZdwQtdcdsr');//其他动产融资
            $routeCollector->addRoute(['GET', 'POST'], '/getPersonSifa', $pre . 'getPersonSifa');//个人涉诉
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyDcrzDiyajc', $pre . 'getCompanyDcrzDiyajc');//上市公司-抵押解除
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyDcrzZhiyajc', $pre . 'getCompanyDcrzZhiyajc');//上市公司-解质押数据
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyDcrzDanbao', $pre . 'getCompanyDcrzDanbao');//上市公司-担保数据

            //详情系列
            $routeCollector->addRoute(['GET', 'POST'], '/getEpbpartyDetail', $pre . 'getEpbpartyDetail');//环保处罚
            $routeCollector->addRoute(['GET', 'POST'], '/getEpbpartyJkqyDetail', $pre . 'getEpbpartyJkqyDetail');//重点监控企业名单
            $routeCollector->addRoute(['GET', 'POST'], '/getEpbpartyZxjcDetail', $pre . 'getEpbpartyZxjcDetail');//环保企业自行监测结果
            $routeCollector->addRoute(['GET', 'POST'], '/getEpbpartyHuanpingDetail', $pre . 'getEpbpartyHuanpingDetail');//环评公示数据
            $routeCollector->addRoute(['GET', 'POST'], '/getCustomQyDetail', $pre . 'getCustomQyDetail');//海关企业
            $routeCollector->addRoute(['GET', 'POST'], '/getCustomXukeDetail', $pre . 'getCustomXukeDetail');//海关许可
            $routeCollector->addRoute(['GET', 'POST'], '/getCustomCreditDetail', $pre . 'getCustomCreditDetail');//海关信用
            $routeCollector->addRoute(['GET', 'POST'], '/getCustomPunishDetail', $pre . 'getCustomPunishDetail');//海关处罚
            $routeCollector->addRoute(['GET', 'POST'], '/getKtggDetail', $pre . 'getKtggDetail');//开庭公告
            $routeCollector->addRoute(['GET', 'POST'], '/getCpwsDetail', $pre . 'getCpwsDetail');//裁判文书
            $routeCollector->addRoute(['GET', 'POST'], '/getFyggDetail', $pre . 'getFyggDetail');//法院公告
            $routeCollector->addRoute(['GET', 'POST'], '/getZxggDetail', $pre . 'getZxggDetail');//执行公告
            $routeCollector->addRoute(['GET', 'POST'], '/getShixinDetail', $pre . 'getShixinDetail');//失信公告
            $routeCollector->addRoute(['GET', 'POST'], '/getSifacdkDetail', $pre . 'getSifacdkDetail');//司法查封冻结扣押
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyQsDetail', $pre . 'getSatpartyQsDetail');//欠税公告
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyChufaDetail', $pre . 'getSatpartyChufaDetail');//涉税处罚公示
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyFzcDetail', $pre . 'getSatpartyFzcDetail');//税务非正常户公示
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyXinDetail', $pre . 'getSatpartyXinDetail');//纳税信用等级
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyRegDetail', $pre . 'getSatpartyRegDetail');//税务登记
            $routeCollector->addRoute(['GET', 'POST'], '/getSatpartyXukeDetail', $pre . 'getSatpartyXukeDetail');//税务许可
            $routeCollector->addRoute(['GET', 'POST'], '/getPbcpartyDetail', $pre . 'getPbcpartyDetail');//央行行政处罚
            $routeCollector->addRoute(['GET', 'POST'], '/getPbcpartyCbrcDetail', $pre . 'getPbcpartyCbrcDetail');//银保监会处罚公示
            $routeCollector->addRoute(['GET', 'POST'], '/getPbcpartyCsrcChufaDetail', $pre . 'getPbcpartyCsrcChufaDetail');//证监处罚公示
            $routeCollector->addRoute(['GET', 'POST'], '/getPbcpartyCsrcXkpfDetail', $pre . 'getPbcpartyCsrcXkpfDetail');//证监会许可批复等级
            $routeCollector->addRoute(['GET', 'POST'], '/getSafeChufaDetail', $pre . 'getSafeChufaDetail');//外汇局处罚
            $routeCollector->addRoute(['GET', 'POST'], '/getSafeXukeDetail', $pre . 'getSafeChufaDetail');//外汇局许可
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyZdwYszkdsrDetail', $pre . 'getCompanyZdwYszkdsrDetail');//应收帐款
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyZdwZldjdsrDetail', $pre . 'getCompanyZdwZldjdsrDetail');//租赁登记
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyZdwBzjzydsrDetail', $pre . 'getCompanyZdwBzjzydsrDetail');//保证金质押登记
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyZdwCdzydsrDetail', $pre . 'getCompanyZdwCdzydsrDetail');//仓单质押
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyZdwSyqbldsrDetail', $pre . 'getCompanyZdwSyqbldsrDetail');//所有权保留
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyZdwQtdcdsrDetail', $pre . 'getCompanyZdwQtdcdsrDetail');//其他动产融资
            $routeCollector->addRoute(['GET', 'POST'], '/getPersonSifaDetail', $pre . 'getPersonSifaDetail');//个人涉诉详情
        });

        return true;
    }

    private function QianQiRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Api/QianQi/QianQiController/';

        $routeCollector->addGroup('/qq', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getThreeYearsData', $prefix . 'getThreeYearsData');//最近三年财务数据，不需授权
            $routeCollector->addRoute(['GET', 'POST'], '/getThreeYearsDataNeedAuth', $prefix . 'getThreeYearsDataNeedAuth');//最近三年财务数据，需授权
            $routeCollector->addRoute(['GET', 'POST'], '/getDataTest', $prefix . 'getDataTest');
        });

        return true;
    }

    private function LongXinRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Api/LongXin/LongXinController/';

        $routeCollector->addGroup('/lx', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceNotAuth', $prefix . 'getFinanceNotAuth');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceNotAuthNew', $prefix . 'getFinanceNotAuthNew');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceNeedAuth', $prefix . 'getFinanceNeedAuth');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceNeedAuthNew', $prefix . 'getFinanceNeedAuthNew');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceTemp', $prefix . 'getFinanceTemp');//仿企名片时的财务数据
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceTempMergeData', $prefix . 'getFinanceTempMergeData');//仿企名片时的财务数据
            $routeCollector->addRoute(['GET', 'POST'], '/superSearch', $prefix . 'superSearch');
        });

        return true;
    }

    private function YuanSuRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Api/YuanSu/YuanSuController/';

        $routeCollector->addGroup('/ys', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/personCheck', $prefix . 'personCheck');//三要素
        });

        return true;
    }

    private function Notify(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Api/Notify/NotifyController/';

        $routeCollector->addGroup('/notify', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/zw/authNotify', $prefix . 'zwAuthNotify');//授权认证通知
            $routeCollector->addRoute(['GET', 'POST'], '/zw/dataNotify', $prefix . 'zwDataNotify');//获取数据通知

            $routeCollector->addRoute(['GET', 'POST'], '/wx', $prefix . 'wxNotify');//微信小程序通知 信动
            $routeCollector->addRoute(['GET', 'POST'], '/wx_wh', $prefix . 'wxNotify_wh');//微信小程序通知 伟衡
            $routeCollector->addRoute(['GET', 'POST'], '/wx/scan', $prefix . 'wxNotifyScan');//微信扫码通知
            $routeCollector->addRoute(['GET', 'POST'], '/ali/scan', $prefix . 'aliNotifyScan');//支付宝扫码通知
        });

        return true;
    }

    private function ExportExcelRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Api/Export/Excel/ExcelController/';

        $routeCollector->addGroup('/export/excel', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/test', $prefix . 'test');
        });

        return true;
    }

    private function ExportWordRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Api/Export/Word/WordController/';

        $routeCollector->addGroup('/export/word', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/createVeryEasy', $prefix . 'createVeryEasy');//极简报告
            $routeCollector->addRoute(['GET', 'POST'], '/createEasy', $prefix . 'createEasy');//简版报告
            $routeCollector->addRoute(['GET', 'POST'], '/createTwoTable', $prefix . 'createTwoTable');//两表报告
            $routeCollector->addRoute(['GET', 'POST'], '/createDeep', $prefix . 'createDeep');//深度报告
        });

        return true;
    }

    private function ExportPdfRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Api/Export/Pdf/PdfController/';

        $routeCollector->addGroup('/export/pdf', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/createVeryEasy', $prefix . 'createVeryEasy');//极简报告
            $routeCollector->addRoute(['GET', 'POST'], '/createEasy', $prefix . 'createEasy');//简版报告
            $routeCollector->addRoute(['GET', 'POST'], '/createTwoTable', $prefix . 'createTwoTable');//两表报告
            $routeCollector->addRoute(['GET', 'POST'], '/createDeep', $prefix . 'createDeep');//深度报告
        });

        return true;
    }

    private function TestRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Test/TestController/';

        $routeCollector->addGroup('/test', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/caiwu', $prefix . 'caiwu');
            $routeCollector->addRoute(['GET', 'POST'], '/product', $prefix . 'product');
            $routeCollector->addRoute(['GET', 'POST'], '/test', $prefix . 'test');
            $routeCollector->addRoute(['GET', 'POST'], '/getInv', $prefix . 'getInv');
            $routeCollector->addRoute(['GET', 'POST'], '/getFpxzStatus', $prefix . 'getFpxzStatus');
            $routeCollector->addRoute(['GET', 'POST'], '/fadadatest', $prefix . 'fadadatest');
            $routeCollector->addRoute(['GET', 'POST'], '/zhichiLogin', $prefix . 'zhichiLogin');
        });

        return true;
    }


}
