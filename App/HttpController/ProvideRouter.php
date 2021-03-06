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
    }

    private function LongDunRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/LongDun/LongDunController/';

        $routeCollector->addGroup('/qcc', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getIPOGuarantee', $prefix . 'getIPOGuarantee');
        });

        return true;
    }

    private function TaoShuRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/TaoShu/TaoShuController/';

        $routeCollector->addGroup('/ts', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/lawPersonInvestmentInfo', $prefix . 'lawPersonInvestmentInfo');
            $routeCollector->addRoute(['GET', 'POST'], '/getRegisterInfo', $prefix . 'getRegisterInfo');
            $routeCollector->addRoute(['GET', 'POST'], '/getGoodsInfo', $prefix . 'getGoodsInfo');//企业生产的流通性产品信息
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
            $routeCollector->addRoute(['GET', 'POST'], '/getProductStandard', $prefix . 'getProductStandard');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBaseData', $prefix . 'getFinanceBaseData');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceBaseMergeData', $prefix . 'getFinanceBaseMergeData');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceCalData', $prefix . 'getFinanceCalData');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceCalMergeData', $prefix . 'getFinanceCalMergeData');
            $routeCollector->addRoute(['GET', 'POST'], '/superSearch', $prefix . 'superSearch');
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

            $routeCollector->addRoute(['GET', 'POST'], '/getKtggDetail', $prefix . 'getKtggDetail');//开庭公告
            $routeCollector->addRoute(['GET', 'POST'], '/getFyggDetail', $prefix . 'getFyggDetail');//法院公告
        });

        return true;
    }


}
