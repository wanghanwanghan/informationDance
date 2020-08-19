<?php

namespace App\HttpController\Business\Api\QiChaCha;

use App\HttpController\Service\QiChaCha\QiChaChaService;
use wanghanwanghan\someUtils\control;

class QiChaChaController extends QiChaChaBase
{
    private $baseUrl;

    function onRequest(?string $action): ?bool
    {
        $this->baseUrl=\Yaconf::get('qichacha.baseUrl');

        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //检验企查查返回值，并给客户计费
    private function checkResponse($res)
    {
        if (isset($res['Paging']) && !empty($res['Paging']))
        {
            $res['Paging']=control::changeArrKey($res['Paging'],[
                'PageSize'=>'pageSize',
                'PageIndex'=>'page',
                'TotalRecords'=>'total'
            ]);
        }else
        {
            $res['Paging']=null;
        }

        return $this->writeJson($res['Status'],$res['Paging'],$res['Result'],$res['Message']);
    }

    //模糊搜索企业列表
    function getEntList()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'keyWord'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'ECIV4/Search',$postData);

        return $this->checkResponse($res);
    }

    //律所及其他特殊基本信息
    function getSpecialEntDetails()
    {
        $entName=$this->request()->getRequestParam('entName');

        $postData=[
            'searchKey'=>$entName,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'ECIOther/GetDetails',$postData);

        return $this->checkResponse($res);
    }

    //企业类型查询
    function getEntType()
    {
        $entName=$this->request()->getRequestParam('entName');

        $postData=[
            'searchKey'=>$entName,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'ECIEntType/GetEntType',$postData);

        return $this->checkResponse($res);
    }

    //实际控制人和控制路径
    function getBeneficiary()
    {
        $entName=$this->request()->getRequestParam('entName');
        $percent=$this->request()->getRequestParam('percent') ?? 0;
        $mode=$this->request()->getRequestParam('mode') ?? 0;

        $postData=[
            'companyName'=>$entName,
            'percent'=>$percent,
            'mode'=>$mode,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'Beneficiary/GetBeneficiary',$postData);

        return $this->checkResponse($res);
    }

    //经营异常
    function getOpException()
    {
        $entName=$this->request()->getRequestParam('entName');

        $postData=[
            'keyNo'=>$entName,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'ECIException/GetOpException',$postData);

        return $this->checkResponse($res);
    }

    //融资历史
    function getEntFinancing()
    {
        $entName=$this->request()->getRequestParam('entName');

        $postData=[
            'searchKey'=>$entName,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'BusinessStateV4/SearchCompanyFinancings',$postData);

        return $this->checkResponse($res);
    }

    //招投标
    function tenderSearch()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'searchKey'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'Tender/Search',$postData);

        return $this->checkResponse($res);
    }

    //购地信息
    function landPurchaseList()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'searchKey'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'LandPurchase/LandPurchaseList',$postData);

        return $this->checkResponse($res);
    }

    //土地公示
    function landPublishList()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'searchKey'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'LandPublish/LandPublishList',$postData);

        return $this->checkResponse($res);
    }

    //土地转让
    function landTransferList()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'searchKey'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'LandTransfer/LandTransferList',$postData);

        return $this->checkResponse($res);
    }

    //招聘信息
    function getRecruitmentList()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'searchKey'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'Recruitment/GetList',$postData);

        return $this->checkResponse($res);
    }

    //建筑资质证书
    function getQualificationList()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'searchKey'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'Qualification/GetList',$postData);

        return $this->checkResponse($res);
    }

    //建筑工程项目
    function getBuildingProjectList()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'searchKey'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'BuildingProject/GetList',$postData);

        return $this->checkResponse($res);
    }

    //债券
    function getBondList()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'searchKey'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'Bond/BondList',$postData);

        return $this->checkResponse($res);
    }

    //行政许可
    function getAdministrativeLicenseList()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'searchKey'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'ADSTLicense/GetAdministrativeLicenseList',$postData);

        return $this->checkResponse($res);
    }

    //行政处罚
    function getAdministrativePenaltyList()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'searchKey'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'AdministrativePenalty/GetAdministrativePenaltyList',$postData);

        return $this->checkResponse($res);
    }

    //司法拍卖
    function getJudicialSaleList()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'keyWord'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'JudicialSale/GetJudicialSaleList',$postData);

        return $this->checkResponse($res);
    }

    //股权出质
    function getStockPledgeList()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'searchKey'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'StockEquityPledge/GetStockPledgeList',$postData);

        return $this->checkResponse($res);
    }

    //动产抵押
    function getChattelMortgage()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'keyWord'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'ChattelMortgage/GetChattelMortgage',$postData);

        return $this->checkResponse($res);
    }

    //土地抵押
    function getLandMortgageList()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'keyWord'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'LandMortgage/GetLandMortgageList',$postData);

        return $this->checkResponse($res);
    }

    //对外担保
    function getAnnualReport()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'keyNo'=>$entName,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'AR/GetAnnualReport',$postData);

        return $this->checkResponse($res);
    }

    //上市公司对外担保
    function getIPOGuarantee()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'stockCode'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'IPO/GetIPOGuarantee',$postData);

        return $this->checkResponse($res);
    }

    //商标
    function getTmSearch()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'keyword'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'tm/Search',$postData);

        return $this->checkResponse($res);
    }

    //专利
    function getPatentV4Search()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'searchKey'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'PatentV4/Search',$postData);

        return $this->checkResponse($res);
    }

    //软件著作权
    function getSearchSoftwareCr()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'searchKey'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'CopyRight/SearchSoftwareCr',$postData);

        return $this->checkResponse($res);
    }

    //作品著作权
    function getSearchCopyRight()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'searchKey'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'CopyRight/SearchCopyRight',$postData);

        return $this->checkResponse($res);
    }

    //企业证书查询
    function getSearchCertification()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'searchKey'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'ECICertification/SearchCertification',$postData);

        return $this->checkResponse($res);
    }

    //新闻舆情
    function getSearchNews()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'searchKey'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'CompanyNews/SearchNews',$postData);

        return $this->checkResponse($res);
    }

    //网站信息
    function getCompanyWebSite()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'searchKey'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'WebSiteV4/GetCompanyWebSite',$postData);

        return $this->checkResponse($res);
    }

    //微博
    function getMicroblogGetList()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'searchKey'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'Microblog/GetList',$postData);

        return $this->checkResponse($res);
    }













}