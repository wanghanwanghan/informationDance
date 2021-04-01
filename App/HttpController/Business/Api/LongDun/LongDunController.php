<?php

namespace App\HttpController\Business\Api\LongDun;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongDun\LongDunService;
use App\HttpController\Service\Pay\ChargeService;
use App\HttpController\Service\User\UserService;
use EasySwoole\Pool\Manager;
use wanghanwanghan\someUtils\control;

class LongDunController extends LongDunBase
{
    private $baseUrl;

    public $moduleNum;//扣费的id
    public $entName;//扣费用的entName

    function onRequest(?string $action): ?bool
    {
        $this->baseUrl = CreateConf::getInstance()->getConf('longdun.baseUrl');

        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //检验企查查返回值，并给客户计费
    private function checkResponse($res, $writeJson = true)
    {
        if (isset($res['Paging']) && !empty($res['Paging'])) {
            $res['Paging'] = control::changeArrKey($res['Paging'], [
                'PageSize' => 'pageSize',
                'PageIndex' => 'page',
                'TotalRecords' => 'total'
            ]);
        } else {
            $res['Paging'] = null;
        }

        if (isset($res['coHttpErr'])) return $this->writeJson(500, $res['Paging'], [], 'co请求错误');

        if (!empty($this->moduleNum) && !empty($this->entName)) {
            $charge = ChargeService::getInstance()->LongDun($this->request(), $this->moduleNum, $this->entName);

            if ($charge['code'] != 200) {
                $res['Status'] = $charge['code'];
                $res['Paging'] = $res['Result'] = null;
                $res['Message'] = $charge['msg'];
            }
        }

        return $writeJson !== true ? [
            'code' => (int)$res['Status'],
            'paging' => $res['Paging'],
            'result' => $res['Result'],
            'msg' => $res['Message']
        ] : $this->writeJson((int)$res['Status'], $res['Paging'], $res['Result'], $res['Message']);
    }

    //模糊搜索企业列表
    function getEntList()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'keyWord' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'ECIV4/Search', $postData);

        $res = $this->checkResponse($res, false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            //用户有没有监控该企业
            $superEnt = UserService::getInstance()->getUserSupervisorEnt($phone);
            foreach ($res['result'] as &$one) {
                strlen($one['StartDate'] < 10) ?: $one['StartDate'] = substr($one['StartDate'], 0, 4);
                //用户有没有监控该企业
                !empty($superEnt) ?: $superEnt = [11111];
                if (!empty($superEnt)) {
                    foreach ($superEnt as $oneEnt) {
                        $one['supervisor'] = 0;
                        if ($one['Name'] == $oneEnt['entName']) {
                            $one['supervisor'] = 1;
                            break;
                        }
                    }
                }
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //律所及其他特殊基本信息
    function getSpecialEntDetails()
    {
        $entName = $this->request()->getRequestParam('entName');

        $postData = [
            'searchKey' => $entName,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'ECIOther/GetDetails', $postData);

        return $this->checkResponse($res);
    }

    //企业类型查询
    function getEntType()
    {
        $entName = $this->request()->getRequestParam('entName');

        $postData = [
            'searchKey' => $entName,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'ECIEntType/GetEntType', $postData);

        return $this->checkResponse($res);
    }

    //实际控制人和控制路径
    function getBeneficiary()
    {
        $entName = $this->request()->getRequestParam('entName');
        $percent = $this->request()->getRequestParam('percent') ?? 0;
        $mode = $this->request()->getRequestParam('mode') ?? 0;
        $pay = $this->request()->getRequestParam('pay') ?? false;

        $this->entName = $entName;
        $this->moduleNum = 14;

        $postData = [
            'companyName' => $entName,
            'percent' => $percent,
            'mode' => $mode,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'Beneficiary/GetBeneficiary', $postData);

        $tmp = [];

        if (count($res['Result']['BreakThroughList']) > 0) {
            $total = current($res['Result']['BreakThroughList']);
            $total = substr($total['TotalStockPercent'], 0, -1);
            if ($total >= 50) {
                //如果第一个人就是大股东了，就直接返回
                $tmp = $res['Result']['BreakThroughList'][0];
            } else {
                //把返回的所有人加起来和100做减法，求出坑
                $hole = 100;
                foreach ($res['Result']['BreakThroughList'] as $key => $val) {
                    $hole -= substr($val['TotalStockPercent'], 0, -1);
                }
                //求出坑的比例，如果比第一个人大，那就是特殊机构，如果没第一个人大，那第一个人就是控制人
                if ($total > $hole) $tmp = $res['Result']['BreakThroughList'][0];
            }
        }

        $res['Result'] = $tmp;

        return $this->checkResponse($res);
    }

    //经营异常
    function getOpException()
    {
        $entName = $this->request()->getRequestParam('entName');

        $postData = [
            'keyNo' => $entName,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'ECIException/GetOpException', $postData);

        return $this->checkResponse($res);
    }

    //融资历史
    function getEntFinancing()
    {
        $entName = $this->request()->getRequestParam('entName');

        $postData = [
            'searchKey' => $entName,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'BusinessStateV4/SearchCompanyFinancings', $postData);

        return $this->checkResponse($res);
    }

    //招投标
    function tenderSearch()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'Tender/Search', $postData);

        return $this->checkResponse($res);
    }

    //招投标详情
    function tenderSearchDetail()
    {
        $id = $this->request()->getRequestParam('id');

        $postData = ['id' => $id];

        $res = (new LongDunService())->get($this->baseUrl . 'Tender/Detail', $postData);

        return $this->checkResponse($res);
    }

    //购地信息
    function landPurchaseList()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'LandPurchase/LandPurchaseList', $postData);

        return $this->checkResponse($res);
    }

    //购地信息详情
    function landPurchaseListDetail()
    {
        $id = $this->request()->getRequestParam('id');

        $postData = ['id' => $id];

        $res = (new LongDunService())->get($this->baseUrl . 'LandPurchase/LandPurchaseDetail', $postData);

        return $this->checkResponse($res);
    }

    //土地公示
    function landPublishList()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'LandPublish/LandPublishList', $postData);

        return $this->checkResponse($res);
    }

    //土地公示详情
    function landPublishListDetail()
    {
        $id = $this->request()->getRequestParam('id');

        $postData = ['id' => $id];

        $res = (new LongDunService())->get($this->baseUrl . 'LandPublish/LandPublishDetail', $postData);

        return $this->checkResponse($res);
    }

    //土地转让
    function landTransferList()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'LandTransfer/LandTransferList', $postData);

        return $this->checkResponse($res);
    }

    //土地转让详情
    function landTransferListDetail()
    {
        $id = $this->request()->getRequestParam('id');

        $postData = ['id' => $id];

        $res = (new LongDunService())->get($this->baseUrl . 'LandTransfer/LandTransferDetail', $postData);

        return $this->checkResponse($res);
    }

    //招聘信息
    function getRecruitmentList()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'Recruitment/GetList', $postData);

        return $this->checkResponse($res);
    }

    //招聘信息详情
    function getRecruitmentListDetail()
    {
        $id = $this->request()->getRequestParam('id');

        $postData = ['id' => $id];

        $res = (new LongDunService())->get($this->baseUrl . 'Recruitment/GetDetail', $postData);

        return $this->checkResponse($res);
    }

    //建筑资质证书
    function getQualificationList()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'Qualification/GetList', $postData);

        return $this->checkResponse($res);
    }

    //建筑资质证书详情
    function getQualificationListDetail()
    {
        $id = $this->request()->getRequestParam('id');

        $postData = ['id' => $id];

        $res = (new LongDunService())->get($this->baseUrl . 'Qualification/GetDetail', $postData);

        return $this->checkResponse($res);
    }

    //建筑工程项目
    function getBuildingProjectList()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'BuildingProject/GetList', $postData);

        return $this->checkResponse($res);
    }

    //建筑工程项目详情
    function getBuildingProjectListDetail()
    {
        $id = $this->request()->getRequestParam('id');

        $postData = ['id' => $id];

        $res = (new LongDunService())->get($this->baseUrl . 'BuildingProject/GetDetail', $postData);

        return $this->checkResponse($res);
    }

    //债券
    function getBondList()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'Bond/BondList', $postData);

        return $this->checkResponse($res);
    }

    //债券详情
    function getBondListDetail()
    {
        $id = $this->request()->getRequestParam('id');

        $postData = ['id' => $id];

        $res = (new LongDunService())->get($this->baseUrl . 'Bond/BondDetail', $postData);

        return $this->checkResponse($res);
    }

    //行政许可
    function getAdministrativeLicenseList()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'ADSTLicense/GetAdministrativeLicenseList', $postData);

        return $this->checkResponse($res);
    }

    //行政许可详情
    function getAdministrativeLicenseListDetail()
    {
        $id = $this->request()->getRequestParam('id');

        $postData = ['id' => $id];

        $res = (new LongDunService())->get($this->baseUrl . 'ADSTLicense/GetAdministrativeLicenseDetail', $postData);

        return $this->checkResponse($res);
    }

    //行政处罚
    function getAdministrativePenaltyList()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'AdministrativePenalty/GetAdministrativePenaltyList', $postData);

        return $this->checkResponse($res);
    }

    //行政处罚详情
    function getAdministrativePenaltyListDetail()
    {
        $id = $this->request()->getRequestParam('id');

        $postData = ['id' => $id];

        $res = (new LongDunService())->get($this->baseUrl . 'AdministrativePenalty/GetAdministrativePenaltyDetail', $postData);

        return $this->checkResponse($res);
    }

    //司法拍卖
    function getJudicialSaleList()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'keyWord' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'JudicialSale/GetJudicialSaleList', $postData);

        return $this->checkResponse($res);
    }

    //司法拍卖详情
    function getJudicialSaleListDetail()
    {
        $id = $this->request()->getRequestParam('id');

        $this->entName = $this->request()->getRequestParam('entName') ?? '';

        $this->moduleNum = 7;

        $postData = ['id' => $id];

        $res = (new LongDunService())->get($this->baseUrl . 'JudicialSale/GetJudicialSaleDetail', $postData);

        return $this->checkResponse($res);
    }

    //股权出质
    function getStockPledgeList()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'StockEquityPledge/GetStockPledgeList', $postData);

        return $this->checkResponse($res);
    }

    //动产抵押
    function getChattelMortgage()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'keyWord' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'ChattelMortgage/GetChattelMortgage', $postData);

        return $this->checkResponse($res);
    }

    //土地抵押
    function getLandMortgageList()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'keyWord' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'LandMortgage/GetLandMortgageList', $postData);

        return $this->checkResponse($res);
    }

    //土地抵押详情
    function getLandMortgageListDetail()
    {
        $id = $this->request()->getRequestParam('id');

        $postData = ['id' => $id];

        $res = (new LongDunService())->get($this->baseUrl . 'LandMortgage/GetLandMortgageDetails', $postData);

        return $this->checkResponse($res);
    }

    //对外担保
    function getAnnualReport()
    {
        $entName = $this->request()->getRequestParam('entName');

        $postData = [
            'keyNo' => $entName,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'AR/GetAnnualReport', $postData);

        if (!empty($res['Result'])) {
            foreach ($res['Result'] as $key => $val) {
                if (isset($val['ProvideAssuranceList'])) {
                    if (empty($val['ProvideAssuranceList'])) {
                        $res['Result'][$key]['ProvideAssuranceList'][0] = [
                            'Creditor' => null,
                            'Debtor' => null,
                            'CreditorCategory' => null,
                            'CreditorAmount' => null,
                            'FulfillObligation' => null,
                            'AssuranceDurn' => null,
                            'AssuranceType' => null,
                            'AssuranceScope' => null,
                        ];
                    }
                } else {
                    $res['Result'][$key]['ProvideAssuranceList'][0] = [
                        'Creditor' => null,
                        'Debtor' => null,
                        'CreditorCategory' => null,
                        'CreditorAmount' => null,
                        'FulfillObligation' => null,
                        'AssuranceDurn' => null,
                        'AssuranceType' => null,
                        'AssuranceScope' => null,
                    ];
                }
            }
        }

        return $this->checkResponse($res);
    }

    //用来获取上市公司股票代码用，以后重写
    private function getBasicDetailsByName($entName)
    {
        $postData = [
            'keyword' => $entName,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'ECIV4/GetBasicDetailsByName', $postData);

        $StockNumber = '';

        if (isset($res['Result']) && !empty($res['Result']) && isset($res['Result']['StockNumber'])) {
            empty($res['Result']['StockNumber']) ? $StockNumber = '' : $StockNumber = $res['Result']['StockNumber'];
        }

        return $StockNumber;
    }

    //企业工商信息
    function getBasicDetailsByEntName()
    {
        $entName = $this->request()->getRequestParam('entName');

        $postData = [
            'keyword' => $entName,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'ECIV4/GetBasicDetailsByName', $postData);

        //2018年营业收入区间
        $mysql = CreateConf::getInstance()->getConf('env.mysqlDatabase');
        try {
            $obj = Manager::getInstance()->get($mysql)->getObj();
            $obj->queryBuilder()->where('entName', $entName)->get('qiyeyingshoufanwei');
            $range = $obj->execBuilder();
            Manager::getInstance()->get($mysql)->recycleObj($obj);
        } catch (\Throwable $e) {
            $this->writeErr($e, __FUNCTION__);
            $range = [];
        }

        $vendinc = [];

        foreach ($range as $one) {
            $vendinc[] = $one;
        }

        !empty($vendinc) ?: $vendinc = '';
        $res['Result']['VENDINC'] = $vendinc;

        (!isset($res['Result']['StartDate']) || empty($res['Result']['StartDate'])) ?: $res['Result']['StartDate'] = substr($res['Result']['StartDate'], 0, 10);
        (!isset($res['Result']['UpdatedDate']) || empty($res['Result']['UpdatedDate'])) ?: $res['Result']['UpdatedDate'] = substr($res['Result']['UpdatedDate'], 0, 10);
        (!isset($res['Result']['TermStart']) || empty($res['Result']['TermStart'])) ?: $res['Result']['TermStart'] = substr($res['Result']['TermStart'], 0, 10);
        (!isset($res['Result']['TeamEnd']) || empty($res['Result']['TeamEnd'])) ?: $res['Result']['TeamEnd'] = substr($res['Result']['TeamEnd'], 0, 10);

        $temp = $res['Result'];
        $res['Result'] = [$temp];

        return $this->checkResponse($res);
    }

    //上市公司对外担保
    function getIPOGuarantee()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $stockCode = $this->getBasicDetailsByName($entName);

        $postData = [
            'stockCode' => $stockCode,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'IPO/GetIPOGuarantee', $postData);

        return $this->checkResponse($res);
    }

    //商标
    function getTmSearch()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'keyword' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'tm/Search', $postData);

        return $this->checkResponse($res);
    }

    //商标详情
    function getTmSearchDetail()
    {
        $id = $this->request()->getRequestParam('id');

        $postData = ['id' => $id];

        $res = (new LongDunService())->get($this->baseUrl . 'tm/GetDetails', $postData);

        return $this->checkResponse($res);
    }

    //专利
    function getPatentV4Search()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'PatentV4/Search', $postData);

        return $this->checkResponse($res);
    }

    //专利详情
    function getPatentV4SearchDetail()
    {
        $id = $this->request()->getRequestParam('id');

        $postData = ['id' => $id];

        $res = (new LongDunService())->get($this->baseUrl . 'PatentV4/GetDetails', $postData);

        return $this->checkResponse($res);
    }

    //软件著作权
    function getSearchSoftwareCr()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'CopyRight/SearchSoftwareCr', $postData);

        return $this->checkResponse($res);
    }

    //作品著作权
    function getSearchCopyRight()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'CopyRight/SearchCopyRight', $postData);

        return $this->checkResponse($res);
    }

    //企业证书查询
    function getSearchCertification()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'ECICertification/SearchCertification', $postData);

        return $this->checkResponse($res);
    }

    //企业证书查询详情
    function getSearchCertificationDetail()
    {
        $id = $this->request()->getRequestParam('id');

        $postData = ['certId' => $id];

        $res = (new LongDunService())->get($this->baseUrl . 'ECICertification/GetCertificationDetailById', $postData);

        return $this->checkResponse($res);
    }

    //新闻舆情
    function getSearchNews()
    {
        $entName = $this->request()->getRequestParam('entName');
        $emotionType = $this->request()->getRequestParam('emotionType') ?? '';
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page - 0,
            'pageSize' => $pageSize - 0,
        ];

        !is_numeric($emotionType) ?: $postData['emotionType'] = $emotionType - 0;

        $res = (new LongDunService())->get($this->baseUrl . 'CompanyNews/SearchNews', $postData);

        return $this->checkResponse($res);
    }

    //新闻舆情详情
    function getSearchNewsDetail()
    {
        $id = $this->request()->getRequestParam('id');

        $postData = ['id' => $id];

        $res = (new LongDunService())->get($this->baseUrl . 'CompanyNews/GetNewsDetail', $postData);

        return $this->checkResponse($res);
    }

    //网站信息
    function getCompanyWebSite()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'WebSiteV4/GetCompanyWebSite', $postData);

        return $this->checkResponse($res);
    }

    //微博
    function getMicroblogGetList()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'Microblog/GetList', $postData);

        return $this->checkResponse($res);
    }

    //股东信息
    function getECIPartnerGetList()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'ECIPartner/GetList', $postData);

        return $this->checkResponse($res);
    }

    //失信信息
    function getCourtV4SearchShiXin()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'isExactlySame' => true,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'CourtV4/SearchShiXin', $postData);

        return $this->checkResponse($res);
    }

    //被执行人
    function getCourtV4SearchZhiXing()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'searchKey' => $entName,
            'isExactlySame' => true,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'CourtV4/SearchZhiXing', $postData);

        return $this->checkResponse($res);
    }

    //股权冻结
    function getJudicialAssistance()
    {
        $entName = $this->request()->getRequestParam('entName');

        $postData = [
            'keyWord' => $entName,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'JudicialAssistance/GetJudicialAssistance', $postData);

        return $this->checkResponse($res);
    }

    //严重违法
    function getSeriousViolationList()
    {
        $entName = $this->request()->getRequestParam('entName');

        $postData = [
            'keyWord' => $entName,
        ];

        $res = (new LongDunService())->get($this->baseUrl . 'SeriousViolation/GetSeriousViolationList', $postData);

        return $this->checkResponse($res);
    }


}