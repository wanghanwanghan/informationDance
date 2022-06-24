<?php

namespace App\HttpController\Business\AdminV2\Mrxd\SouKe;

use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\AdminNewUser;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminMenuItems;
use App\HttpController\Models\AdminV2\AdminNewMenu;
use App\HttpController\Models\AdminV2\AdminUserChargeConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceChargeInfo;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataQueue;
use App\HttpController\Models\AdminV2\FinanceLog;
use App\HttpController\Models\Api\CompanyCarInsuranceStatusInfo;
use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Service\AdminRole\AdminPrivilegedUser;
use App\HttpController\Models\AdminV2\AdminRoles;
use App\HttpController\Models\AdminV2\AdminUserFinanceConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceData;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadDataRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use App\HttpController\Models\AdminV2\NewFinanceData;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\XinDong\XinDongService;
use Vtiful\Kernel\Format;

class SouKeController extends ControllerBase
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

    function advancedSearch(): bool
    {
        $companyEsModel = new \App\ElasticSearch\Model\Company();

        //传过来的searchOption 例子 [{"type":20,"value":["5","10","2"]},{"type":30,"value":["15","5"]}]
        $searchOptionStr =  trim($this->request()->getRequestParam('searchOption'));
        $searchOptionArr = json_decode($searchOptionStr, true);

        $size = $this->request()->getRequestParam('size')??10;
        $page = $this->request()->getRequestParam('page')??1;
        $offset  =  ($page-1)*$size;

        $companyEsModel
            //经营范围
            ->SetQueryByBusinessScope(trim($this->request()->getRequestParam('basic_opscope')))
            //数字经济及其核心产业
            ->SetQueryByBasicSzjjid(trim($this->request()->getRequestParam('basic_szjjid')))
            // 搜索文案 智能搜索
            ->SetQueryBySearchText( trim($this->request()->getRequestParam('searchText')))
            // 搜索战略新兴产业
            ->SetQueryByBasicJlxxcyid(trim($this->request()->getRequestParam('basic_jlxxcyid')))
            // 搜索shang_pin_data 商品信息 appStr:五香;农庄
            ->SetQueryByShangPinData( trim($this->request()->getRequestParam('appStr')))
            //必须存在官网
            ->SetQueryByWeb($searchOptionArr)
            //必须存在APP
            ->SetQueryByApp($searchOptionArr)
            //必须是物流企业
            ->SetQueryByWuLiuQiYe($searchOptionArr)
            // 企业类型 :传过来的是10 20 转换成对应文案 然后再去搜索
            ->SetQueryByCompanyOrgType($searchOptionArr)
            // 成立年限  ：传过来的是 10  20 30 转换成最小值最大值范围后 再去搜索
            ->SetQueryByEstiblishTime($searchOptionArr)
            // 营业状态   传过来的是 10  20  转换成文案后 去匹配
            ->SetQueryByRegStatus($searchOptionArr)
            // 注册资本 传过来的是 10 20 转换成最大最小范围后 再去搜索
            ->SetQueryByRegCaptial($searchOptionArr)
            // 团队人数 传过来的是 10 20 转换成最大最小范围后 再去搜索
            ->SetQueryByTuanDuiRenShu($searchOptionArr)
            // 营收规模  传过来的是 10 20 转换成对应文案后再去匹配
            ->SetQueryByYingShouGuiMo($searchOptionArr)
            //四级分类 basic_nicid: A0111,A0112,A0113,
            ->SetQueryBySiJiFenLei(trim($this->request()->getRequestParam('basic_nicid')))
            // 地区 basic_regionid: 110101,110102,
            ->SetQueryByBasicRegionid( trim($this->request()->getRequestParam('basic_regionid')))
            ->addSize($size)
            ->addFrom($offset)
            //设置默认值 不传任何条件 搜全部
            ->setDefault()
            ->searchFromEs()
            // 格式化下日期和时间
            ->formatEsDate()
            // 格式化下金额
            ->formatEsMoney()
        ;

        foreach($companyEsModel->return_data['hits']['hits'] as &$dataItem){
            $addresAndEmailData = (new XinDongService())->getLastPostalAddressAndEmail($dataItem);
            $dataItem['_source']['last_postal_address'] = $addresAndEmailData['last_postal_address'];
            $dataItem['_source']['last_email'] = $addresAndEmailData['last_email'];

            $dataItem['_source']['logo'] =  (new XinDongService())->getLogoByEntId($dataItem['_source']['xd_id']);

            // 添加tag
            $dataItem['_source']['tags'] = array_values(
                (new XinDongService())::getAllTagesByData(
                    $dataItem['_source']
                )
            );

            // 官网
            $webStr = trim($dataItem['_source']['web']);
            if(!$webStr){
                continue;
            }
            $webArr = explode('&&&', $webStr);
            !empty($webArr) && $dataItem['_source']['web'] = end($webArr);
        }

        return $this->writeJson(200,
            [
                'page' => $page,
                'pageSize' =>$size,
                'total' => intval($companyEsModel->return_data['hits']['total']['value']),
                'totalPage' => (int)floor(intval($companyEsModel->return_data['hits']['total']['value'])/
                    ($size)),

            ]
            , $companyEsModel->return_data['hits']['hits'], '成功', true, []);
    }



}