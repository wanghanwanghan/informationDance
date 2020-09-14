<?php

namespace App\HttpController;

use EasySwoole\Http\AbstractInterface\AbstractRouter;
use FastRoute\RouteCollector;

class Router extends AbstractRouter
{
    public function initialize(RouteCollector $routeCollector)
    {
        //全局模式拦截下,路由将只匹配Router.php中的控制器方法响应,将不会执行框架的默认解析
        $this->setGlobalMode(true);

        $routeCollector->addGroup('/api/v1',function (RouteCollector $routeCollector)
        {
            $this->CommonRouteV1($routeCollector);//公共功能
            $this->UserRouteV1($routeCollector);//用户相关
            $this->QiChaChaRouteV1($routeCollector);//企查查路由
            $this->TaoShuRouteV1($routeCollector);//淘数路由
            $this->FaHaiRouteV1($routeCollector);//法海路由
            $this->QianQiRouteV1($routeCollector);//乾启路由
        });
    }

    private function CommonRouteV1(RouteCollector $routeCollector)
    {
        $prefix='/Business/Api/Common/CommonController/';

        $routeCollector->addGroup('/comm',function (RouteCollector $routeCollector) use ($prefix)
        {
            $routeCollector->addRoute(['GET','POST'],'/image/upload',$prefix.'imageUpload');//图片上传
            $routeCollector->addRoute(['GET','POST'],'/wx/pay',$prefix.'wxPay');//微信支付
            $routeCollector->addRoute(['GET','POST'],'/ali/pay',$prefix.'aliPay');//支付宝支付
            $routeCollector->addRoute(['GET','POST'],'/create/verifyCode',$prefix.'createVerifyCode');//创建验证码
        });

        return true;
    }

    private function UserRouteV1(RouteCollector $routeCollector)
    {
        $prefix='/Business/Api/User/UserController/';

        $routeCollector->addGroup('/user',function (RouteCollector $routeCollector) use ($prefix)
        {
            $routeCollector->addRoute(['GET','POST'],'/reg',$prefix.'reg');//注册
            $routeCollector->addRoute(['GET','POST'],'/login',$prefix.'login');//登录
        });

        return true;
    }

    private function QiChaChaRouteV1(RouteCollector $routeCollector)
    {
        $routeCollector->addGroup('/qcc',function (RouteCollector $routeCollector)
        {
            $routeCollector->addRoute(['GET','POST'],'/getEntList','/Business/Api/QiChaCha/QiChaChaController/getEntList');//模糊搜索企业
            $routeCollector->addRoute(['GET','POST'],'/getSpecialEntDetails','/Business/Api/QiChaCha/QiChaChaController/getSpecialEntDetails');//律所及其他特殊基本信息
            $routeCollector->addRoute(['GET','POST'],'/getEntType','/Business/Api/QiChaCha/QiChaChaController/getEntType');//企业类型查询
            $routeCollector->addRoute(['GET','POST'],'/getBeneficiary','/Business/Api/QiChaCha/QiChaChaController/getBeneficiary');//实际控制人和控制路径
            $routeCollector->addRoute(['GET','POST'],'/getOpException','/Business/Api/QiChaCha/QiChaChaController/getOpException');//经营异常
            $routeCollector->addRoute(['GET','POST'],'/getEntFinancing','/Business/Api/QiChaCha/QiChaChaController/getEntFinancing');//融资历史
            $routeCollector->addRoute(['GET','POST'],'/tenderSearch','/Business/Api/QiChaCha/QiChaChaController/tenderSearch');//招投标
            $routeCollector->addRoute(['GET','POST'],'/landPurchaseList','/Business/Api/QiChaCha/QiChaChaController/landPurchaseList');//购地信息
            $routeCollector->addRoute(['GET','POST'],'/landPublishList','/Business/Api/QiChaCha/QiChaChaController/landPublishList');//土地公示
            $routeCollector->addRoute(['GET','POST'],'/landTransferList','/Business/Api/QiChaCha/QiChaChaController/landTransferList');//土地转让
            $routeCollector->addRoute(['GET','POST'],'/getRecruitmentList','/Business/Api/QiChaCha/QiChaChaController/getRecruitmentList');//招聘信息
            $routeCollector->addRoute(['GET','POST'],'/getQualificationList','/Business/Api/QiChaCha/QiChaChaController/getQualificationList');//建筑资质证书
            $routeCollector->addRoute(['GET','POST'],'/getBuildingProjectList','/Business/Api/QiChaCha/QiChaChaController/getBuildingProjectList');//建筑工程项目
            $routeCollector->addRoute(['GET','POST'],'/getBondList','/Business/Api/QiChaCha/QiChaChaController/getBondList');//债券
            $routeCollector->addRoute(['GET','POST'],'/getAdministrativeLicenseList','/Business/Api/QiChaCha/QiChaChaController/getAdministrativeLicenseList');//行政许可
            $routeCollector->addRoute(['GET','POST'],'/getAdministrativePenaltyList','/Business/Api/QiChaCha/QiChaChaController/getAdministrativePenaltyList');//行政处罚
            $routeCollector->addRoute(['GET','POST'],'/getJudicialSaleList','/Business/Api/QiChaCha/QiChaChaController/getJudicialSaleList');//司法拍卖
            $routeCollector->addRoute(['GET','POST'],'/getStockPledgeList','/Business/Api/QiChaCha/QiChaChaController/getStockPledgeList');//股权出质
            $routeCollector->addRoute(['GET','POST'],'/getChattelMortgage','/Business/Api/QiChaCha/QiChaChaController/getChattelMortgage');//动产抵押
            $routeCollector->addRoute(['GET','POST'],'/getLandMortgageList','/Business/Api/QiChaCha/QiChaChaController/getLandMortgageList');//土地抵押
            $routeCollector->addRoute(['GET','POST'],'/getAnnualReport','/Business/Api/QiChaCha/QiChaChaController/getAnnualReport');//对外担保
            $routeCollector->addRoute(['GET','POST'],'/getIPOGuarantee','/Business/Api/QiChaCha/QiChaChaController/getIPOGuarantee');//上市公司对外担保
            $routeCollector->addRoute(['GET','POST'],'/getTmSearch','/Business/Api/QiChaCha/QiChaChaController/getTmSearch');//商标
            $routeCollector->addRoute(['GET','POST'],'/getPatentV4Search','/Business/Api/QiChaCha/QiChaChaController/getPatentV4Search');//专利
            $routeCollector->addRoute(['GET','POST'],'/getSearchSoftwareCr','/Business/Api/QiChaCha/QiChaChaController/getSearchSoftwareCr');//软件著作权
            $routeCollector->addRoute(['GET','POST'],'/getSearchCopyRight','/Business/Api/QiChaCha/QiChaChaController/getSearchCopyRight');//作品著作权
            $routeCollector->addRoute(['GET','POST'],'/getSearchCertification','/Business/Api/QiChaCha/QiChaChaController/getSearchCertification');//企业证书查询
            $routeCollector->addRoute(['GET','POST'],'/getSearchNews','/Business/Api/QiChaCha/QiChaChaController/getSearchNews');//新闻舆情
            $routeCollector->addRoute(['GET','POST'],'/getCompanyWebSite','/Business/Api/QiChaCha/QiChaChaController/getCompanyWebSite');//网站信息
            $routeCollector->addRoute(['GET','POST'],'/getMicroblogGetList','/Business/Api/QiChaCha/QiChaChaController/getMicroblogGetList');//微博

            //详情系列
            $routeCollector->addRoute(['GET','POST'],'/tenderSearchDetail','/Business/Api/QiChaCha/QiChaChaController/tenderSearchDetail');//招投标
            $routeCollector->addRoute(['GET','POST'],'/landPurchaseListDetail','/Business/Api/QiChaCha/QiChaChaController/landPurchaseListDetail');//购地信息
            $routeCollector->addRoute(['GET','POST'],'/landPublishListDetail','/Business/Api/QiChaCha/QiChaChaController/landPublishListDetail');//土地公示
            $routeCollector->addRoute(['GET','POST'],'/landTransferListDetail','/Business/Api/QiChaCha/QiChaChaController/landTransferListDetail');//土地转让
            $routeCollector->addRoute(['GET','POST'],'/getRecruitmentListDetail','/Business/Api/QiChaCha/QiChaChaController/getRecruitmentListDetail');//招聘信息
            $routeCollector->addRoute(['GET','POST'],'/getQualificationListDetail','/Business/Api/QiChaCha/QiChaChaController/getQualificationListDetail');//建筑资质证书
            $routeCollector->addRoute(['GET','POST'],'/getBuildingProjectListDetail','/Business/Api/QiChaCha/QiChaChaController/getBuildingProjectListDetail');//建筑工程项目
            $routeCollector->addRoute(['GET','POST'],'/getBondListDetail','/Business/Api/QiChaCha/QiChaChaController/getBondListDetail');//债券
            $routeCollector->addRoute(['GET','POST'],'/getAdministrativeLicenseListDetail','/Business/Api/QiChaCha/QiChaChaController/getAdministrativeLicenseListDetail');//行政许可
            $routeCollector->addRoute(['GET','POST'],'/getAdministrativePenaltyListDetail','/Business/Api/QiChaCha/QiChaChaController/getAdministrativePenaltyListDetail');//行政处罚
            $routeCollector->addRoute(['GET','POST'],'/getJudicialSaleListDetail','/Business/Api/QiChaCha/QiChaChaController/getJudicialSaleListDetail');//司法拍卖
            $routeCollector->addRoute(['GET','POST'],'/getLandMortgageListDetail','/Business/Api/QiChaCha/QiChaChaController/getLandMortgageListDetail');//土地抵押
            $routeCollector->addRoute(['GET','POST'],'/getTmSearchDetail','/Business/Api/QiChaCha/QiChaChaController/getTmSearchDetail');//商标
            $routeCollector->addRoute(['GET','POST'],'/getPatentV4SearchDetail','/Business/Api/QiChaCha/QiChaChaController/getPatentV4SearchDetail');//专利
            $routeCollector->addRoute(['GET','POST'],'/getSearchCertificationDetail','/Business/Api/QiChaCha/QiChaChaController/getSearchCertificationDetail');//企业证书查询
            $routeCollector->addRoute(['GET','POST'],'/getSearchNewsDetail','/Business/Api/QiChaCha/QiChaChaController/getSearchNewsDetail');//新闻舆情


        });

        return true;
    }

    private function TaoShuRouteV1(RouteCollector $routeCollector)
    {
        $prefix='/Business/Api/TaoShu/TaoShuController/';

        $routeCollector->addGroup('/ts',function (RouteCollector $routeCollector) use ($prefix)
        {
            $routeCollector->addRoute(['GET','POST'],'/getEntByKeyword',$prefix.'getEntByKeyword');//企业名称检索
            $routeCollector->addRoute(['GET','POST'],'/getRegisterInfo',$prefix.'getRegisterInfo');//企业基本信息
            $routeCollector->addRoute(['GET','POST'],'/getShareHolderInfo',$prefix.'getShareHolderInfo');//企业股东及出资信息
            $routeCollector->addRoute(['GET','POST'],'/getInvestmentAbroadInfo',$prefix.'getInvestmentAbroadInfo');//企业对外投资
            $routeCollector->addRoute(['GET','POST'],'/getBranchInfo',$prefix.'getBranchInfo');//企业分支机构
            $routeCollector->addRoute(['GET','POST'],'/getRegisterChangeInfo',$prefix.'getRegisterChangeInfo');//企业变更信息
            $routeCollector->addRoute(['GET','POST'],'/getMainManagerInfo',$prefix.'getMainManagerInfo');//企业主要管理人员
            $routeCollector->addRoute(['GET','POST'],'/lawPersonInvestmentInfo',$prefix.'lawPersonInvestmentInfo');//法人代表对外投资
            $routeCollector->addRoute(['GET','POST'],'/getLawPersontoOtherInfo',$prefix.'getLawPersontoOtherInfo');//法人代表其他公司任职
            $routeCollector->addRoute(['GET','POST'],'/getGraphGFinalData',$prefix.'getGraphGFinalData');//企业最终控制人
            $routeCollector->addRoute(['GET','POST'],'/getOperatingExceptionRota',$prefix.'getOperatingExceptionRota');//企业经营异常
            $routeCollector->addRoute(['GET','POST'],'/getEquityPledgedInfo',$prefix.'getEquityPledgedInfo');//企业股权出质列表
            $routeCollector->addRoute(['GET','POST'],'/getEquityPledgedDetailInfo',$prefix.'getEquityPledgedDetailInfo');//企业股权出质详情
            $routeCollector->addRoute(['GET','POST'],'/getChattelMortgageInfo',$prefix.'getChattelMortgageInfo');//企业动产抵押列表
            $routeCollector->addRoute(['GET','POST'],'/getChattelMortgageDetailInfo',$prefix.'getChattelMortgageDetailInfo');//企业动产抵押详情
            $routeCollector->addRoute(['GET','POST'],'/getEntActualContoller',$prefix.'getEntActualContoller');//企业实际控制人信息
            $routeCollector->addRoute(['GET','POST'],'/getEntAnnReportForGuaranteeInfo',$prefix.'getEntAnnReportForGuaranteeInfo');//企业年报对外担保信息
        });

        return true;
    }

    private function FaHaiRouteV1(RouteCollector $routeCollector)
    {
        $routeCollector->addGroup('/fh',function (RouteCollector $routeCollector)
        {
            $routeCollector->addRoute(['GET','POST'],'/getEpbparty','/Business/Api/FaHai/FaHaiController/getEpbparty');//环保处罚
            $routeCollector->addRoute(['GET','POST'],'/getEpbpartyJkqy','/Business/Api/FaHai/FaHaiController/getEpbpartyJkqy');//重点监控企业名单
            $routeCollector->addRoute(['GET','POST'],'/getEpbpartyZxjc','/Business/Api/FaHai/FaHaiController/getEpbpartyZxjc');//环保企业自行监测结果
            $routeCollector->addRoute(['GET','POST'],'/getEpbpartyHuanping','/Business/Api/FaHai/FaHaiController/getEpbpartyHuanping');//环评公示数据
            $routeCollector->addRoute(['GET','POST'],'/getCustomQy','/Business/Api/FaHai/FaHaiController/getCustomQy');//海关企业
            $routeCollector->addRoute(['GET','POST'],'/getCustomXuke','/Business/Api/FaHai/FaHaiController/getCustomXuke');//海关许可
            $routeCollector->addRoute(['GET','POST'],'/getCustomCredit','/Business/Api/FaHai/FaHaiController/getCustomCredit');//海关信用
            $routeCollector->addRoute(['GET','POST'],'/getCustomPunish','/Business/Api/FaHai/FaHaiController/getCustomPunish');//海关处罚
            $routeCollector->addRoute(['GET','POST'],'/getKtgg','/Business/Api/FaHai/FaHaiController/getKtgg');//开庭公告
            $routeCollector->addRoute(['GET','POST'],'/getCpws','/Business/Api/FaHai/FaHaiController/getCpws');//裁判文书
            $routeCollector->addRoute(['GET','POST'],'/getFygg','/Business/Api/FaHai/FaHaiController/getFygg');//法院公告
            $routeCollector->addRoute(['GET','POST'],'/getZxgg','/Business/Api/FaHai/FaHaiController/getZxgg');//执行公告
            $routeCollector->addRoute(['GET','POST'],'/getShixin','/Business/Api/FaHai/FaHaiController/getShixin');//失信公告
            $routeCollector->addRoute(['GET','POST'],'/getSifacdk','/Business/Api/FaHai/FaHaiController/getSifacdk');//司法查封冻结扣押
            $routeCollector->addRoute(['GET','POST'],'/getSatpartyQs','/Business/Api/FaHai/FaHaiController/getSatpartyQs');//欠税公告
            $routeCollector->addRoute(['GET','POST'],'/getSatpartyChufa','/Business/Api/FaHai/FaHaiController/getSatpartyChufa');//涉税处罚公示
            $routeCollector->addRoute(['GET','POST'],'/getSatpartyFzc','/Business/Api/FaHai/FaHaiController/getSatpartyFzc');//税务非正常户公示
            $routeCollector->addRoute(['GET','POST'],'/getSatpartyXin','/Business/Api/FaHai/FaHaiController/getSatpartyXin');//纳税信用等级
            $routeCollector->addRoute(['GET','POST'],'/getSatpartyReg','/Business/Api/FaHai/FaHaiController/getSatpartyReg');//税务登记
            $routeCollector->addRoute(['GET','POST'],'/getSatpartyXuke','/Business/Api/FaHai/FaHaiController/getSatpartyXuke');//税务许可
            $routeCollector->addRoute(['GET','POST'],'/getPbcparty','/Business/Api/FaHai/FaHaiController/getPbcparty');//央行行政处罚
            $routeCollector->addRoute(['GET','POST'],'/getPbcpartyCbrc','/Business/Api/FaHai/FaHaiController/getPbcpartyCbrc');//银保监会处罚公示
            $routeCollector->addRoute(['GET','POST'],'/getPbcpartyCsrcChufa','/Business/Api/FaHai/FaHaiController/getPbcpartyCsrcChufa');//证监处罚公示
            $routeCollector->addRoute(['GET','POST'],'/getPbcpartyCsrcXkpf','/Business/Api/FaHai/FaHaiController/getPbcpartyCsrcXkpf');//证监会许可批复等级
            $routeCollector->addRoute(['GET','POST'],'/getSafeChufa','/Business/Api/FaHai/FaHaiController/getSafeChufa');//外汇局处罚
            $routeCollector->addRoute(['GET','POST'],'/getSafeXuke','/Business/Api/FaHai/FaHaiController/getSafeChufa');//外汇局许可
            $routeCollector->addRoute(['GET','POST'],'/getCompanyZdwYszkdsr','/Business/Api/FaHai/FaHaiController/getCompanyZdwYszkdsr');//应收帐款
            $routeCollector->addRoute(['GET','POST'],'/getCompanyZdwZldjdsr','/Business/Api/FaHai/FaHaiController/getCompanyZdwZldjdsr');//租赁登记
            $routeCollector->addRoute(['GET','POST'],'/getCompanyZdwBzjzydsr','/Business/Api/FaHai/FaHaiController/getCompanyZdwBzjzydsr');//保证金质押登记
            $routeCollector->addRoute(['GET','POST'],'/getCompanyZdwCdzydsr','/Business/Api/FaHai/FaHaiController/getCompanyZdwCdzydsr');//仓单质押
            $routeCollector->addRoute(['GET','POST'],'/getCompanyZdwSyqbldsr','/Business/Api/FaHai/FaHaiController/getCompanyZdwSyqbldsr');//所有权保留
            $routeCollector->addRoute(['GET','POST'],'/getCompanyZdwQtdcdsr','/Business/Api/FaHai/FaHaiController/getCompanyZdwQtdcdsr');//其他动产融资
            $routeCollector->addRoute(['GET','POST'],'/getPersonSifa','/Business/Api/FaHai/FaHaiController/getPersonSifa');//个人涉诉

            //详情系列
            $routeCollector->addRoute(['GET','POST'],'/getEpbpartyDetail','/Business/Api/FaHai/FaHaiController/getEpbpartyDetail');//环保处罚
            $routeCollector->addRoute(['GET','POST'],'/getEpbpartyJkqyDetail','/Business/Api/FaHai/FaHaiController/getEpbpartyJkqyDetail');//重点监控企业名单
            $routeCollector->addRoute(['GET','POST'],'/getEpbpartyZxjcDetail','/Business/Api/FaHai/FaHaiController/getEpbpartyZxjcDetail');//环保企业自行监测结果
            $routeCollector->addRoute(['GET','POST'],'/getEpbpartyHuanpingDetail','/Business/Api/FaHai/FaHaiController/getEpbpartyHuanpingDetail');//环评公示数据
            $routeCollector->addRoute(['GET','POST'],'/getCustomQyDetail','/Business/Api/FaHai/FaHaiController/getCustomQyDetail');//海关企业
            $routeCollector->addRoute(['GET','POST'],'/getCustomXukeDetail','/Business/Api/FaHai/FaHaiController/getCustomXukeDetail');//海关许可
            $routeCollector->addRoute(['GET','POST'],'/getCustomCreditDetail','/Business/Api/FaHai/FaHaiController/getCustomCreditDetail');//海关信用
            $routeCollector->addRoute(['GET','POST'],'/getCustomPunishDetail','/Business/Api/FaHai/FaHaiController/getCustomPunishDetail');//海关处罚
            $routeCollector->addRoute(['GET','POST'],'/getKtggDetail','/Business/Api/FaHai/FaHaiController/getKtggDetail');//开庭公告
            $routeCollector->addRoute(['GET','POST'],'/getCpwsDetail','/Business/Api/FaHai/FaHaiController/getCpwsDetail');//裁判文书
            $routeCollector->addRoute(['GET','POST'],'/getFyggDetail','/Business/Api/FaHai/FaHaiController/getFyggDetail');//法院公告
            $routeCollector->addRoute(['GET','POST'],'/getZxggDetail','/Business/Api/FaHai/FaHaiController/getZxggDetail');//执行公告
            $routeCollector->addRoute(['GET','POST'],'/getShixinDetail','/Business/Api/FaHai/FaHaiController/getShixinDetail');//失信公告
            $routeCollector->addRoute(['GET','POST'],'/getSifacdkDetail','/Business/Api/FaHai/FaHaiController/getSifacdkDetail');//司法查封冻结扣押
            $routeCollector->addRoute(['GET','POST'],'/getSatpartyQsDetail','/Business/Api/FaHai/FaHaiController/getSatpartyQsDetail');//欠税公告
            $routeCollector->addRoute(['GET','POST'],'/getSatpartyChufaDetail','/Business/Api/FaHai/FaHaiController/getSatpartyChufaDetail');//涉税处罚公示
            $routeCollector->addRoute(['GET','POST'],'/getSatpartyFzcDetail','/Business/Api/FaHai/FaHaiController/getSatpartyFzcDetail');//税务非正常户公示
            $routeCollector->addRoute(['GET','POST'],'/getSatpartyXinDetail','/Business/Api/FaHai/FaHaiController/getSatpartyXinDetail');//纳税信用等级
            $routeCollector->addRoute(['GET','POST'],'/getSatpartyRegDetail','/Business/Api/FaHai/FaHaiController/getSatpartyRegDetail');//税务登记
            $routeCollector->addRoute(['GET','POST'],'/getSatpartyXukeDetail','/Business/Api/FaHai/FaHaiController/getSatpartyXukeDetail');//税务许可
            $routeCollector->addRoute(['GET','POST'],'/getPbcpartyDetail','/Business/Api/FaHai/FaHaiController/getPbcpartyDetail');//央行行政处罚
            $routeCollector->addRoute(['GET','POST'],'/getPbcpartyCbrcDetail','/Business/Api/FaHai/FaHaiController/getPbcpartyCbrcDetail');//银保监会处罚公示
            $routeCollector->addRoute(['GET','POST'],'/getPbcpartyCsrcChufaDetail','/Business/Api/FaHai/FaHaiController/getPbcpartyCsrcChufaDetail');//证监处罚公示
            $routeCollector->addRoute(['GET','POST'],'/getPbcpartyCsrcXkpfDetail','/Business/Api/FaHai/FaHaiController/getPbcpartyCsrcXkpfDetail');//证监会许可批复等级
            $routeCollector->addRoute(['GET','POST'],'/getSafeChufaDetail','/Business/Api/FaHai/FaHaiController/getSafeChufaDetail');//外汇局处罚
            $routeCollector->addRoute(['GET','POST'],'/getSafeXukeDetail','/Business/Api/FaHai/FaHaiController/getSafeChufaDetail');//外汇局许可
            $routeCollector->addRoute(['GET','POST'],'/getCompanyZdwYszkdsrDetail','/Business/Api/FaHai/FaHaiController/getCompanyZdwYszkdsrDetail');//应收帐款
            $routeCollector->addRoute(['GET','POST'],'/getCompanyZdwZldjdsrDetail','/Business/Api/FaHai/FaHaiController/getCompanyZdwZldjdsrDetail');//租赁登记
            $routeCollector->addRoute(['GET','POST'],'/getCompanyZdwBzjzydsrDetail','/Business/Api/FaHai/FaHaiController/getCompanyZdwBzjzydsrDetail');//保证金质押登记
            $routeCollector->addRoute(['GET','POST'],'/getCompanyZdwCdzydsrDetail','/Business/Api/FaHai/FaHaiController/getCompanyZdwCdzydsrDetail');//仓单质押
            $routeCollector->addRoute(['GET','POST'],'/getCompanyZdwSyqbldsrDetail','/Business/Api/FaHai/FaHaiController/getCompanyZdwSyqbldsrDetail');//所有权保留
            $routeCollector->addRoute(['GET','POST'],'/getCompanyZdwQtdcdsrDetail','/Business/Api/FaHai/FaHaiController/getCompanyZdwQtdcdsrDetail');//其他动产融资

        });

        return true;
    }

    private function QianQiRouteV1(RouteCollector $routeCollector)
    {
        $prefix='/Business/Api/QianQi/QianQiController/';

        $routeCollector->addGroup('/qq',function (RouteCollector $routeCollector) use ($prefix)
        {
            $routeCollector->addRoute(['GET','POST'],'/getThreeYearsData',$prefix.'getThreeYearsData');//最近三年财务数据，不需授权
            $routeCollector->addRoute(['GET','POST'],'/getThreeYearsDataNeedAuth',$prefix.'getThreeYearsDataNeedAuth');//最近三年财务数据，需授权
        });

        return true;
    }


}
