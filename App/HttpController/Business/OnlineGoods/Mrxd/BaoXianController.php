<?php

namespace App\HttpController\Business\OnlineGoods\Mrxd;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\AdminV2\DeliverDetailsHistory;
use App\HttpController\Models\AdminV2\DeliverHistory;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Models\RDS3\CompanyInvestor;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\XinDong\XinDongService;

class BaoXianController extends \App\HttpController\Business\OnlineGoods\Mrxd\ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    /*
     * 筛选选型
     * */
    function getSearchOption(): bool
    {
        $searchOptionArr = (new XinDongService())->getSearchOption([]);
        return $this->writeJson(200, null, $searchOptionArr, '成功', false, []);
    }

    function getProducts(): bool
    {
        return $this->writeJson(
            200,[ ] ,(new \App\HttpController\Service\BaoYa\BaoYaService())->getProducts(),
            '成功',
            true,
            []
        );
    }

    function getProductDetail(): bool
    {
        if($this->getRequestData('id')<=0){
            return $this->writeJson(
                200,[ ] ,[],
                '参数缺失',
                true,
                []
            );
        }
        return $this->writeJson(
            200,[ ] ,(new \App\HttpController\Service\BaoYa\BaoYaService())->getProductDetail(
                $this->getRequestData('id')
            ),
            '成功',
            true,
            []
        );
    }

}