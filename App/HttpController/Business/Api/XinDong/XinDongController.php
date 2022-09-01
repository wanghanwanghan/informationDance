<?php

namespace App\HttpController\Business\Api\XinDong;

use App\Crontab\CrontabBase;
use App\Crontab\CrontabList\RunCompleteCompanyData;
use App\Crontab\CrontabList\RunDealApiSouKe;
use App\Crontab\CrontabList\RunDealBussinessOpportunity;
use App\Crontab\CrontabList\RunDealCarInsuranceInstallment;
use App\Crontab\CrontabList\RunDealEmailReceiver;
//use App\Crontab\CrontabList\RunDealFinanceCompanyData;
use App\Crontab\CrontabList\RunDealFinanceCompanyDataNew;
use App\Crontab\CrontabList\RunDealFinanceCompanyDataNewV2;
use App\Crontab\CrontabList\RunDealToolsFile;
use App\Crontab\CrontabList\RunDealZhaoTouBiao;
use App\Csp\Service\CspService;
use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminNewUser;
use App\HttpController\Models\AdminV2\AdminUserFinanceData;
use App\HttpController\Models\AdminV2\CarInsuranceInstallment;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\AdminV2\InsuranceData;
use App\HttpController\Models\AdminV2\MailReceipt;
use App\HttpController\Models\AdminV2\NewFinanceData;
use App\HttpController\Models\AdminV2\OperatorLog;
use App\HttpController\Models\AdminV2\InvoiceTask;
use App\HttpController\Models\AdminV2\InvoiceTaskDetails;
use App\HttpController\Models\AdminV2\ToolsUploadQueue;
use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\Api\User;
use App\HttpController\Models\MRXD\InsuranceDataHuiZhong;
use App\HttpController\Models\MRXD\OnlineGoodsUser;
use App\HttpController\Models\RDS3\CompanyInvestor;
use App\HttpController\Models\RDS3\HdSaic\CodeCa16;
use App\HttpController\Models\RDS3\HdSaic\CodeEx02;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Models\RDS3\HdSaic\CompanyHistoryName;
use App\HttpController\Models\RDS3\HdSaic\CompanyInv;
use App\HttpController\Models\RDS3\HdSaic\CompanyLiquidation;
//use App\HttpController\Models\RDS3\HdSaic\ZhaoTouBiaoAll;
use App\HttpController\Models\RDS3\HdSaic\CompanyManager;
use App\HttpController\Models\RDS3\HdSaicExtension\CncaRzGltxH;
use App\HttpController\Models\RDS3\HdSaicExtension\DataplusAppAndroidH;
use App\HttpController\Models\RDS3\HdSaicExtension\DataplusAppIosH;
use App\HttpController\Models\RDS3\HdSaicExtension\MostTorchHightechH;
use App\HttpController\Service\ChuangLan\ChuangLanService;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\Export\Excel\ExportExcelService;
use App\HttpController\Service\GuoPiao\GuoPiaoService;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
//use App\HttpController\Service\LongDun\BaoYaService;
use App\HttpController\Service\LongDun\LongDunService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Mail\Email;
use App\HttpController\Service\Pay\ChargeService;
use App\HttpController\Service\Sms\SmsService;
use App\HttpController\Service\XinDong\Score\xds;
use App\HttpController\Service\XinDong\XinDongService;
// use App\HttpController\Models\RDS3\Company;
use EasySwoole\ElasticSearch\Config;
use EasySwoole\ElasticSearch\ElasticSearch;
use EasySwoole\ElasticSearch\RequestBean\Search;
use App\ElasticSearch\Service\ElasticSearchService;
use Carbon\Carbon;
use EasySwoole\ORM\DbManager;
use wanghanwanghan\someUtils\control;
use App\HttpController\Models\Api\UserSearchHistory;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\Http\Message\UploadFile;
use App\HttpController\Models\Api\UserBusinessOpportunity;
use App\HttpController\Models\Api\UserBusinessOpportunityBatch;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Service\GuangZhouYinLian\GuangZhouYinLianService;
use Vtiful\Kernel\Format;
class XinDongController extends XinDongBase
{
    private $ldUrl;

    function onRequest(?string $action): ?bool
    {
        $this->ldUrl = CreateConf::getInstance()->getConf('longdun.baseUrl');
        
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //这里放一些需要组合其他接口然后对外输出的逻辑

    private function checkResponse($res): bool
    {
        return $this->writeJson((int)$res['code'], $res['paging'], $res['result'], $res['msg'] ?? null);
    }

    //控股法人股东的司法风险
    function getCorporateShareholderRisk(): bool
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        //先看看最大的股东是不是企业，持股超过50%的
        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'ECIPartner/GetList', $postData);

        //有可能是coHttp错误
        if ($res['code'] != 200) return $this->checkResponse($res);

        $entName = '';

        //查询结果里有没有持股大于50%的企业股东
        foreach ($res['result'] as $one) {
            //持股比例
            $stockPercent = str_replace(['%'], '', trim($one['StockPercent']));
            if ($stockPercent > 50) {
                //查一下，用有没有股东判断这是自然人还是企业
                $check = (new LongDunService())->setCheckRespFlag(true)
                    ->get($this->ldUrl . 'ECIPartner/GetList', ['searchKey' => $one['StockName']]);
                //有股东，说明是企业法人
                ($check['code'] != 200 || empty($check['result'])) ?: $entName = $one['StockName'];
            }
        }

        if (empty($entName))
            return $this->checkResponse(['code' => 200, 'paging' => null, 'result' => [], 'msg' => '查询成功']);

        //如果这里的entName不是空，说明有持股大于50的，企业股东
        $res = XinDongService::getInstance()->getCorporateShareholderRisk($entName);

        $res['result']['entName'] = $entName;

        return $this->checkResponse($res);
    }

    //产品标准
    function getProductStandard()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $res = XinDongService::getInstance()->getProductStandard($entName, $page, $pageSize);

        return $this->checkResponse($res);
    }

    //资产线索
    function getAssetLeads()
    {
        $entName = $this->request()->getRequestParam('entName');

        $res = XinDongService::getInstance()->getAssetLeads($entName);

        return $this->checkResponse($res);
    }

    //非企信息
    function getNaCaoRegisterInfo()
    {
        $entName = $this->request()->getRequestParam('entName');

        $res = XinDongService::getInstance()->getNaCaoRegisterInfo($entName);

        return $this->checkResponse($res);
    }

    //二次特征分数
    function getFeatures()
    {
        $entName = $this->request()->getRequestParam('entName');

        $charge = ChargeService::getInstance()->Features($this->request(), 52);

        if ($charge['code'] === 200) {
            $res = XinDongService::getInstance()->getFeatures($entName);
        } else {
            $res['code'] = $charge['code'];
            $res['paging'] = null;
            $res['result'] = null;
            $res['msg'] = $charge['msg'];
        }

        return $this->checkResponse($res);
    }

    //行业top
    function industryTop()
    {
        $fz_list = $this->request()->getRequestParam('fz_list');
        $fm_list = $this->request()->getRequestParam('fm_list');
        $pay = $this->request()->getRequestParam('pay') ?? 0;

        $fz_list = explode(',', $fz_list);
        $fm_list = explode(',', $fm_list);

        !is_array($fz_list) ?: $fz_list = array_unique($fz_list);
        !is_array($fm_list) ?: $fm_list = array_unique($fm_list);

        $result = ['code' => 200, 'paging' => null, 'result' => null, 'msg' => null];

        if (empty($fz_list) || empty($fm_list)) {
            return $this->checkResponse($result);
        }

        $res = XinDongService::getInstance()->industryTop($fz_list, $fm_list);

        $fz_list = $fm_list = [];

        foreach ($res['fz_list'] as $key => $val) {
            if ($val['info']['code'] === 200) {
                $fz_list[$val['entName']] = $val['info']['result'];
            }
        }

        foreach ($res['fm_list'] as $key => $val) {
            if ($val['info']['code'] === 200) {
                $fm_list[$val['entName']] = $val['info']['result'];
            }
        }

        $res = (new xds())->industryTopScore($fz_list, $fm_list);

        $result['result'] = [
            'fz_list' => $res[0],
            'fm_list' => $res[1],
        ];

        return $this->checkResponse($result);
    }

    //物流搜索
    function logisticsSearch(): bool
    {
        $pindex = $this->request()->getRequestParam('page') ?? 1;

        !empty($pindex) ?: $pindex = 1;

        $postData = [
            'pindex' => $pindex - 1,
            'basic_entname' => "any:物流",
            'jingying_vc_round' => "any:普通货运",
            'basic_nicid' => "any:G5430",
            'basic_status' => "any:1",
        ];

        //# 企业状态
        //ex02_dict = (('1', '在营'), ('2', '吊销'), ('3', '注销'), ('4', '迁出'), ('5', '撤销'), ('6', '临时(个体工商户使用)'), ('8', '停业'), ('9', '其他'), ('9_01', '撤销'), ('9_02', '待迁入'),
        //             ('9_03', '经营期限届满'), ('9_04', '清算中'), ('9_05', '停业'), ('9_06', '拟注销'), ('9_07', '非正常户'), ('21', '吊销未注销'), ('22', '吊销已注销'), ('30', '正在注销'), ('!', '-'),)
        //should 是 or   must 是 and   must_not 是not

        $res = (new LongXinService())->superSearch($postData);

        return $this->checkResponse($res);
    }

    //
    function financesGroupSearch(): bool
    {
        $phone = $this->request()->getRequestParam('phone');
        $page = $this->request()->getRequestParam('page');

        $limit = 10;
        $offset = ($page - 1) * $limit;

        $user_info = User::create()->where('phone', $phone)->get();

        if (empty($user_info)) {
            return $this->writeJson(201);
        }

        $group_name = $this->request()->getRequestParam('group_name');

        if (is_numeric($group_name) && $group_name - 0 > 100) {

            $model = FinancesSearch::create()->where([
                'userId' => $user_info->getAttr('id'),
                'group' => $group_name,
                'is_show' => 1,
            ])->page($page)->order('fengxian', 'DESC')->order('caiwu', 'DESC')->withTotalCount();

            $res = $model->all();

            $total = $model->lastQueryResult()->getTotalCount();

            if (!empty($res)) {
                $res = obj2Arr($res);
                foreach ($res as $key => $val) {
                    $res[$key]['detail'] = jsonDecode($val['detail']);
                }
            }

            $tmp = [
                'code' => 200,
                'paging' => ['total' => $total],
                'result' => $res,
            ];

            return $this->checkResponse($tmp);
        }


        $sql = <<<eof
SELECT
	userId,
	`group`,
	groupDesc,
	count( 1 ) AS num 
FROM
	`information_dance_finances_search_first` 
WHERE
	userId = {$user_info->getAttr('id')} 
	AND is_show = 1 
GROUP BY
	`group`,
	groupDesc 
ORDER BY
	`group` DESC 
	LIMIT {$offset},{$limit}
eof;

        $res = sqlRaw($sql);

        $tmp = [
            'code' => 200,
            'paging' => null,
            'result' => $res,
        ];

        $sql = <<<eof
SELECT
	userId,
	`group`,
	groupDesc,
	count( 1 ) AS num 
FROM
	`information_dance_finances_search_first` 
WHERE
	userId = {$user_info->getAttr('id')} 
	AND is_show = 1 
GROUP BY
	`group`,
	groupDesc
eof;

        $paging = sqlRaw($sql);

        $tmp['paging']['total'] = empty($paging) ? 0 : count($paging);

        return $this->checkResponse($tmp);
    }

    //金融搜索结果加入mysql
    function financesSearchResToMysql(): bool
    {
        //should 是 or   must 是 and   must_not 是not

        $basic_entname = $this->request()->getRequestParam('basic_entname') ?? '';
        if (!empty(trim($basic_entname))) {
            $basic_entname = CommonService::getInstance()->spaceTo($basic_entname);
            $postData['basic_entname'] = "any:{$basic_entname}";
        }

        $basic_person_name = $this->request()->getRequestParam('basic_person_name') ?? '';
        if (!empty(trim($basic_person_name))) {
            $basic_person_name = CommonService::getInstance()->spaceTo($basic_person_name);
            $postData['basic_person_name'] = "any:{$basic_person_name}";
        }

        $basic_dom = $this->request()->getRequestParam('basic_dom') ?? '';
        if (!empty(trim($basic_dom))) {
            $basic_dom = CommonService::getInstance()->spaceTo($basic_dom);
            $postData['basic_dom'] = "all:{$basic_dom}";
        }

        $basic_regcap = $this->request()->getRequestParam('basic_regcap') ?? '';
        if (!empty(trim($basic_regcap))) {
            $basic_regcap = str_replace(['-'], '￥', $basic_regcap);
            $postData['basic_regcap'] = $basic_regcap;
        }

        $basic_nicid = $this->request()->getRequestParam('basic_nicid') ?? '';
        if (!empty(trim($basic_nicid))) {
            $basic_nicid = trim($basic_nicid, ',');
            $postData['basic_nicid'] = "any:{$basic_nicid}";
        }

        $basic_esdate = $this->request()->getRequestParam('basic_esdate') ?? '';
        if (!empty($basic_esdate)) {
            $basic_esdate = substr($basic_esdate[0], 0, 10) . '￥' . substr($basic_esdate[1], 0, 10);
            $postData['basic_esdate'] = $basic_esdate;
        }

        $basic_enttype = $this->request()->getRequestParam('basic_enttype') ?? '';
        if (!empty(trim($basic_enttype))) {
            $basic_enttype = trim($basic_enttype, ',');
            $postData['basic_enttype'] = "any:{$basic_enttype}";
        }

        $basic_uniscid = $this->request()->getRequestParam('basic_uniscid') ?? '';
        if (!empty(trim($basic_uniscid))) {
            $basic_uniscid = trim($basic_uniscid, ',');
            $postData['basic_uniscid'] = "any:{$basic_uniscid}";
        }

        $basic_ygrs = $this->request()->getRequestParam('basic_ygrs') ?? '';
        if (!empty(trim($basic_ygrs))) {
            $basic_ygrs = str_replace(['-'], '￥', $basic_ygrs);
            $postData['basic_ygrs'] = $basic_ygrs;
        }

        $basic_regionid = $this->request()->getRequestParam('basic_regionid') ?? '';
        if (!empty(trim($basic_regionid))) {
            $basic_regionid = trim($basic_regionid, ',');
            $postData['basic_regionid'] = "any:{$basic_regionid}";
        }

        $basic_opscope = $this->request()->getRequestParam('basic_opscope') ?? '';
        if (!empty(trim($basic_opscope))) {
            $basic_opscope = CommonService::getInstance()->spaceTo($basic_opscope);
            $postData['basic_opscope'] = "any:{$basic_opscope}";
        }

        $jingying_vc_round = $this->request()->getRequestParam('jingying_vc_round') ?? '';
        if (!empty(trim($jingying_vc_round))) {
            $jingying_vc_round = trim($jingying_vc_round, ',');
            $postData['jingying_vc_round'] = "any:{$jingying_vc_round}";
        }

        $basic_status = $this->request()->getRequestParam('basic_status') ?? '';
        if (!empty(trim($basic_status))) {
            $basic_status = trim($basic_status, ',');
            $postData['basic_status'] = "any:{$basic_status}";
        }

        $user_id = User::create()
            ->where('phone', $this->request()->getRequestParam('phone'))
            ->get()->getAttr('id');

        if (!is_numeric($user_id)) {
            return $this->writeJson(201);
        }

        $postData['pindex'] = 0;

        $group = time();

        while (true) {

            $res = (new LongXinService())->superSearch($postData);

            if ($res['total'] - 0 > 0 && $res['code'] - 0 === 200 && !empty($res['data'])) {

                foreach ($res['data'] as $one) {

                    try {
                        FinancesSearch::create()->data([
                            'group' => $group,
                            'userId' => $user_id,
                            'entName' => trim($one['ENTNAME']),
                            'historyEntname' => trim($one['history_entname']),
                            'code' => trim($one['UNISCID']),
                            'ESDATE' => trim($one['ESDATE']),
                            'ENTSTATUS' => trim($one['ENTSTATUS']),
                            'detail' => jsonEncode($one, false),
                        ])->save();
                    } catch (\Throwable $e) {
                        continue;
                    }

                }

            } else {
                break;
            }

            $postData['pindex']++;

        }

        return $this->writeJson(200);
    }

    //处理一批名单的风险标签
    function financesSearchHandleFengXianLabel(): bool
    {
        $group_name = $this->request()->getRequestParam('group_name') ?? '';
        $phone = $this->request()->getRequestParam('phone') ?? '';

        $user_info = User::create()->where('phone', $phone)->get();

        if (empty($user_info)) {
            return $this->writeJson(201);
        }

        FinancesSearch::create()->where([
            'userId' => $user_info->getAttr('id'),
            'group' => $group_name,
            'fengxian' => '',
            'is_show' => 1,
        ])->update([
            'fengxian' => '等待处理'
        ]);

        $tmp = [
            'code' => 200,
            'paging' => null,
            'result' => null,
            'msg' => '',
        ];

        return $this->checkResponse($tmp);
    }

    //处理一批名单的财务标签
    function financesSearchHandleCaiWuLabel(): bool
    {
        $group_name = $this->request()->getRequestParam('group_name') ?? '';
        $phone = $this->request()->getRequestParam('phone') ?? '';

        $user_info = User::create()->where('phone', $phone)->get();

        if (empty($user_info)) {
            return $this->writeJson(201);//
        }

        FinancesSearch::create()->where([
            'userId' => $user_info->getAttr('id'),
            'group' => $group_name,
            'caiwu' => '',
            'is_show' => 1,
        ])->update([
            'caiwu' => '等待处理'
        ]);

        $tmp = [
            'code' => 200,
            'paging' => null,
            'result' => null,
            'msg' => '',
        ];

        return $this->checkResponse($tmp);
    }

    //处理一批名单的联系方式标签
    function financesSearchHandleLianJieLabel(): bool
    {
        $group_name = $this->request()->getRequestParam('group_name') ?? '';
        $phone = $this->request()->getRequestParam('phone') ?? '';

        $user_info = User::create()->where('phone', $phone)->get();

        if (empty($user_info)) {
            return $this->writeJson(201);
        }

        FinancesSearch::create()->where([
            'userId' => $user_info->getAttr('id'),
            'group' => $group_name,
            'lianjie' => '',
            'is_show' => 1,
        ])->update([
            'lianjie' => '等待处理'
        ]);

        $tmp = [
            'code' => 200,
            'paging' => null,
            'result' => null,
            'msg' => '',
        ];

        return $this->checkResponse($tmp);
    }

    //修改组描述
    function financesSearchEditGroupDesc(): bool
    {
        $group_desc = $this->request()->getRequestParam('groupDesc') ?? '';
        $group = $this->request()->getRequestParam('group') ?? '';
        $phone = $this->request()->getRequestParam('phone') ?? '';

        $user_info = User::create()->where('phone', $phone)->get();

        if (empty($user_info)) {
            return $this->writeJson(201);
        }

        FinancesSearch::create()->where([
            'userId' => $user_info->getAttr('id'),
            'group' => $group,
        ])->update([
            'groupDesc' => trim($group_desc)
        ]);

        return $this->checkResponse([
            'code' => 200,
            'paging' => null,
            'result' => null,
            'msg' => '',
        ]);
    }

    //导出详情列表
    function financesSearchExportDetail(): bool
    {
        $group = $this->request()->getRequestParam('group') ?? '';
        $phone = $this->request()->getRequestParam('phone') ?? '';

        $user_info = User::create()->where('phone', $phone)->get();

        if (empty($user_info)) {
            return $this->writeJson(201);
        }

        $data = [];
        $page = 1;

        while (true) {

            $list = FinancesSearch::create()->where([
                'userId' => $user_info->getAttr('id'),
                'group' => $group,
            ])->page($page, 500)->all();

            if (empty($list)) break;

            foreach ($list as $one) {
                $tmp = [];
                $tmp[] = $one->entName;
                $tmp[] = $one->historyEntname;
                $tmp[] = $one->code;
                $tmp[] = $one->ESDATE;
                $tmp[] = $one->ENTSTATUS;
                $tmp[] = $one->fengxian;
                $tmp[] = $one->caiwu;
                $tmp[] = $one->lianjie;
                $tmp[] = $one->remarks;
                $data[] = $tmp;
            }

            $page++;
        }

        $path = (new ExportExcelService())->setExcelHeader([
            '企业名称', '曾用名', '统一社会信用代码', '成立时间', '状态', '风险个数', '财务标签', '链接个数', '备注'
        ])->setExcelAllData($data)->store();

        return $this->checkResponse([
            'code' => 200,
            'paging' => null,
            'result' => $path,
            'msg' => '',
        ]);
    }

    //删除组名称
    function delUserGroupList(): bool
    {
        $group = $this->request()->getRequestParam('group') ?? '';
        $userId = $this->request()->getRequestParam('userId') ?? '';
        $phone = $this->request()->getRequestParam('phone') ?? '';

        try {
            FinancesSearch::create()->where([
                'group' => $group,
                'userId' => $userId,
            ])->update(['is_show' => 0]);
        } catch (\Throwable $e) {

        }

        return $this->checkResponse([
            'code' => 200,
            'paging' => null,
            'result' => null,
            'msg' => '1',
        ]);
    }

    //
    function editGroupRemarks(): bool
    {
        $id = $this->request()->getRequestParam('id') ?? '';
        $remarks = $this->request()->getRequestParam('remarks') ?? '';
        $phone = $this->request()->getRequestParam('phone') ?? '';

        try {
            FinancesSearch::create()->get($id)->update(['remarks' => trim($remarks)]);
        } catch (\Throwable $e) {

        }

        return $this->checkResponse([
            'code' => 200,
            'paging' => null,
            'result' => null,
            'msg' => '1',
        ]);
    }

    //
    function getVendincScale(): bool
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $entName = explode(',', $entName);
        if (empty($entName)) {
            return $this->writeJson(201, null, null, '公司名称不能是空');
        }

        $res = [];

        foreach ($entName as $ent) {
            $label = (new XinDongService())->getVendincScale($ent, 2020);
            $tmp = (new XinDongService())->vendincScaleLabelChange($label);
            array_unshift($tmp, $ent);
            $res[] = ['entname' => $tmp[0], 'label' => $tmp[1], 'desc' => $tmp[2]];
        }

        return $this->checkResponse([
            'code' => 200,
            'paging' => null,
            'result' => $res,
        ]);
    }

    /**
      * 
      * 支持的搜索条件 
       https://api.meirixindong.com/api/v1/xd/getSearchOption?phone=18201611816
      * 
      * 
     */
    function getSearchOption(): bool
    { 
        $searchOptionArr = (new XinDongService())->getSearchOption([]);

        return $this->writeJson(200, null, $searchOptionArr, '成功', false, []);
    }

    /**
      * 
      * 高级搜索 | 
        https://api.meirixindong.com/api/v1/xd/advancedSearch 
      * 
      * 
     */
    function advancedSearch(): bool
    {
        $requestData =  $this->getRequestData();
        if(substr($requestData['basic_nicid'], -1) == ','){
            $requestData['basic_nicid'] = rtrim($requestData['basic_nicid'], ",");
        }

        if(substr($requestData['basic_regionid'], -1) == ','){
            $requestData['basic_regionid'] = rtrim($requestData['basic_regionid'], ",");
        }

        if(substr($requestData['basic_jlxxcyid'], -1) == ','){
            $requestData['basic_jlxxcyid'] = rtrim($requestData['basic_jlxxcyid'], ",");
        }


        $companyEsModel = new \App\ElasticSearch\Model\Company();

        //传过来的searchOption 例子 [{"type":20,"value":["5","10","2"]},{"type":30,"value":["15","5"]}]
        $searchOptionStr =  trim($this->request()->getRequestParam('searchOption'));
        $searchOptionArr = json_decode($searchOptionStr, true);

        $size = $this->request()->getRequestParam('size')??20;
        $page = $this->request()->getRequestParam('page')??1;
        $offset  =  ($page-1)*$size;

        //区域搜索
        $areas_arr  = json_decode($requestData['areas'],true) ;
        if(!empty($areas_arr)){

            //区域多边形搜索：要闭合：即最后一个点要和最后一个点重合
            $first = $areas_arr[0];;
            $last =  end($areas_arr);
            if(
                strval($first[0])!= strval($last[0]) ||
                strval($first[1])!= strval($last[1])
            ){
                $areas_arr[] = $first;
            }
        }
        $companyEsModel
            //经营范围
            ->SetQueryByBusinessScope(trim($this->request()->getRequestParam('OPSCOPE'),"OPSCOPE"))
            //数字经济及其核心产业
            ->SetQueryByBasicSzjjid(trim($this->request()->getRequestParam('basic_szjjid')))
            // 搜索文案 智能搜索
            ->SetQueryBySearchTextV2( trim($this->request()->getRequestParam('searchText')))
            // 搜索战略新兴产业
            ->SetQueryByBasicJlxxcyid(trim($this->request()->getRequestParam('basic_jlxxcyid')))
            // 搜索shang_pin_data 商品信息 appStr:五香;农庄
            ->SetQueryByShangPinData( trim($this->request()->getRequestParam('appStr')))
            //必须存在官网
            ->SetQueryByWeb($searchOptionArr)
            ->SetAreaQueryV5($areas_arr,$requestData['areas_type']?:1)
            //必须存在APP
            ->SetQueryByApp($searchOptionArr)
            //必须是物流企业
            ->SetQueryByWuLiuQiYe($searchOptionArr)
            // 企业类型 :传过来的是10 20 转换成对应文案 然后再去搜索
            ->SetQueryByCompanyOrgType($searchOptionArr)
            // 成立年限  ：传过来的是 10  20 30 转换成最小值最大值范围后 再去搜索
            ->SetQueryByEstiblishTime($searchOptionArr)
            // 营业状态   传过来的是 10  20  转换成文案后 去匹配
            ->SetQueryByRegStatusV2($searchOptionArr)
            // 注册资本 传过来的是 10 20 转换成最大最小范围后 再去搜索
            ->SetQueryByRegCaptial($searchOptionArr)
            // 团队人数 传过来的是 10 20 转换成最大最小范围后 再去搜索
            ->SetQueryByTuanDuiRenShu($searchOptionArr)
            // 营收规模  传过来的是 10 20 转换成对应文案后再去匹配
            ->SetQueryByYingShouGuiMo($searchOptionArr)
            //四级分类 basic_nicid: A0111,A0112,A0113,
            ->SetQueryBySiJiFenLei(trim($this->request()->getRequestParam('basic_nicid')))
            //公司类型
            ->SetQueryByCompanyType(trim($this->request()->getRequestParam('ENTTYPE')))
            //公司状态
            ->SetQueryByCompanyStatus(trim($this->request()->getRequestParam('ENTSTATUS')))
            // 地区 basic_regionid: 110101,110102,
            ->SetQueryByBasicRegionid(trim($this->request()->getRequestParam('basic_regionid')))
            ->addSize($size)
            ->addFrom($offset)
            //设置默认值 不传任何条件 搜全部
            ->setDefault()
            ->searchFromEs('company_202208')
            // 格式化下日期和时间
            ->formatEsDate()
            // 格式化下金额
            ->formatEsMoney('REGCAP')
        ;
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'hits_count' =>  count($companyEsModel->return_data['hits']['hits'])
            ])
        );


        foreach($companyEsModel->return_data['hits']['hits'] as &$dataItem){
            $dataItem['_source']['short_name'] =  CompanyBasic::findBriefName($dataItem['_source']['ENTNAME']);
            $addresAndEmailData = (new XinDongService())->getLastPostalAddressAndEmailV2($dataItem);
            $dataItem['_source']['LAST_DOM'] = $addresAndEmailData['LAST_DOM'];
            $dataItem['_source']['LAST_EMAIL'] = $addresAndEmailData['LAST_EMAIL'];
            $dataItem['_source']['logo'] =  (new XinDongService())->getLogoByEntIdV2($dataItem['_source']['companyid']);

            // 添加tag
            $dataItem['_source']['tags'] = array_values(
                (new XinDongService())::getAllTagesByData(
                    $dataItem['_source']
                )
            );

            $dataItem['_source']['ENTTYPE_CNAME'] =   '';
            $dataItem['_source']['ENTSTATUS_CNAME'] =  '';
            if($dataItem['_source']['ENTTYPE']){
                $dataItem['_source']['ENTTYPE_CNAME'] =   CodeCa16::findByCode($dataItem['_source']['ENTTYPE']);
            }
            if($dataItem['_source']['ENTSTATUS']){
                $dataItem['_source']['ENTSTATUS_CNAME'] =   CodeEx02::findByCode($dataItem['_source']['ENTSTATUS']);
            }


            // 公司简介
            $tmpArr = explode('&&&', trim($dataItem['_source']['gong_si_jian_jie']));
            array_pop($tmpArr);
            $dataItem['_source']['gong_si_jian_jie_data_arr'] = [];
            foreach($tmpArr as $tmpItem_){
                // $dataItem['_source']['gong_si_jian_jie_data_arr'][] = [$tmpItem_];
                $dataItem['_source']['gong_si_jian_jie_data_arr'][] = $tmpItem_;
            }


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
    function advancedSearchOld(): bool
    {
        $companyEsModel = new \App\ElasticSearch\Model\Company();
        $requestData =  $this->getRequestData();

        //传过来的searchOption 例子 [{"type":20,"value":["5","10","2"]},{"type":30,"value":["15","5"]}]
        $searchOptionStr =  trim($this->request()->getRequestParam('searchOption'));
        $searchOptionArr = json_decode($searchOptionStr, true);

        $size = $this->request()->getRequestParam('size')??10;
        $page = $this->request()->getRequestParam('page')??1;
        $offset  =  ($page-1)*$size;
//区域搜索
        $areas_arr  = json_decode($requestData['areas'],true) ;
        if(!empty($areas_arr)){
//            CommonService::getInstance()->log4PHP(
//                json_encode([
//                    __CLASS__.__FUNCTION__ .__LINE__,
//                    '$areas_arr' => $areas_arr,
//                ])
//            );

            //区域多边形搜索：要闭合：即最后一个点要和最后一个点重合
            $first = $areas_arr[0];
            $last =  end($areas_arr);
            if(
                strval($first[0])!= strval($last[0]) ||
                strval($first[1])!= strval($last[1])
            ){
                $areas_arr[] = $first;
//                CommonService::getInstance()->log4PHP(
//                    json_encode([
//                        __CLASS__.__FUNCTION__ .__LINE__,
//                        'add_new_first' => true,
//                        '$areas_arr' => $areas_arr,
//                        'strval($first[0])' => strval($first[0]),
//                        'strval($last[0])'=>strval($last[0]),
//                    ])
//                );
            }else{
//                CommonService::getInstance()->log4PHP(
//                    json_encode([
//                        __CLASS__.__FUNCTION__ .__LINE__,
//                        'add_new_first' => false,
//                        '$areas_arr' => $areas_arr,
//                        'strval($first[0])' => strval($first[0]),
//                        'strval($last[0])'=>strval($last[0]),
//                    ])
//                );
            }

        }
        $companyEsModel
            //经营范围
            ->SetQueryByBusinessScope(trim($this->request()->getRequestParam('basic_opscope')))
            ->SetAreaQueryV3($areas_arr,$requestData['areas_type']?:1)
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

    function advancedSearchOption(): bool
    {
        return $this->writeJson(200,
            [  ]
            , (new XinDongService())->getSearchOption(), '成功', true, []);
        $requestData =  $this->getRequestData();

        if(substr($requestData['basic_nicid'], -1) == ','){
            $requestData['basic_nicid'] = rtrim($requestData['basic_nicid'], ",");
        }

        if(substr($requestData['basic_regionid'], -1) == ','){
            $requestData['basic_regionid'] = rtrim($requestData['basic_regionid'], ",");
        }

        if(substr($requestData['basic_jlxxcyid'], -1) == ','){
            $requestData['basic_jlxxcyid'] = rtrim($requestData['basic_jlxxcyid'], ",");
        }


        $companyEsModel = new \App\ElasticSearch\Model\Company();

        //传过来的searchOption 例子 [{"type":20,"value":["5","10","2"]},{"type":30,"value":["15","5"]}]
        $searchOptionStr =  trim($this->request()->getRequestParam('searchOption'));
        $searchOptionArr = json_decode($searchOptionStr, true);

        $size = $this->request()->getRequestParam('size')??10;
        $page = $this->request()->getRequestParam('page')??1;
        $offset  =  ($page-1)*$size;
        $size = 500;
        //区域搜索
        $areas_arr  = json_decode($requestData['areas'],true) ;
        if(!empty($areas_arr)){


            //区域多边形搜索：要闭合：即最后一个点要和最后一个点重合
            $first = $areas_arr[0];;
            $last =  end($areas_arr);
            if(
                strval($first[0])!= strval($last[0]) ||
                strval($first[1])!= strval($last[1])
            ){
                $areas_arr[] = $first;

            }else{

            }
        }

        $companyEsModel
            //经营范围
            ->SetQueryByBusinessScope(trim($this->request()->getRequestParam('OPSCOPE'),"OPSCOPE"))
            //数字经济及其核心产业
            ->SetQueryByBasicSzjjid(trim($this->request()->getRequestParam('basic_szjjid')))
            // 搜索文案 智能搜索
            ->SetQueryBySearchTextV2( trim($this->request()->getRequestParam('searchText')))
            // 搜索战略新兴产业
            ->SetQueryByBasicJlxxcyid(trim($this->request()->getRequestParam('basic_jlxxcyid')))
            // 搜索shang_pin_data 商品信息 appStr:五香;农庄
            ->SetQueryByShangPinData( trim($this->request()->getRequestParam('appStr')))
            //必须存在官网
            ->SetQueryByWeb($searchOptionArr)
            ->SetAreaQueryV5($areas_arr,$requestData['areas_type']?:1)
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
            //公司类型
            ->SetQueryByCompanyType(trim($this->request()->getRequestParam('ENTTYPE')))
            //公司状态
            ->SetQueryByCompanyStatus(trim($this->request()->getRequestParam('ENTSTATUS')))
            // 地区 basic_regionid: 110101,110102,
            ->SetQueryByBasicRegionid(trim($this->request()->getRequestParam('basic_regionid')))
            ->addSize($size)
            //->addFrom($offset)
            //设置默认值 不传任何条件 搜全部
            ->setDefault()
            ->searchFromEs('company_202208')
            // 格式化下日期和时间
            ->formatEsDate()
            // 格式化下金额
            ->formatEsMoney('REGCAP')
        ;

//        $companyEsModel
//            //经营范围
//            ->SetQueryByBusinessScope(trim($this->request()->getRequestParam('OPSCOPE'),"OPSCOPE"))
//            //数字经济及其核心产业
//            ->SetQueryByBasicSzjjid(trim($this->request()->getRequestParam('basic_szjjid')))
//            // 搜索文案 智能搜索
//            ->SetQueryBySearchTextV2( trim($this->request()->getRequestParam('searchText')))
//            // 搜索战略新兴产业
//            ->SetQueryByBasicJlxxcyid(trim($this->request()->getRequestParam('basic_jlxxcyid')))
//            // 搜索shang_pin_data 商品信息 appStr:五香;农庄
//            ->SetQueryByShangPinData( trim($this->request()->getRequestParam('appStr')))
//            //必须存在官网
//            ->SetQueryByWeb($searchOptionArr)
//            ->SetAreaQueryV5($areas_arr,$requestData['areas_type']?:1)
//            //必须存在APP
//            ->SetQueryByApp($searchOptionArr)
//            //必须是物流企业
//            ->SetQueryByWuLiuQiYe($searchOptionArr)
//            // 企业类型 :传过来的是10 20 转换成对应文案 然后再去搜索
//            ->SetQueryByCompanyOrgType($searchOptionArr)
//            // 成立年限  ：传过来的是 10  20 30 转换成最小值最大值范围后 再去搜索
//            ->SetQueryByEstiblishTime($searchOptionArr)
//            // 营业状态   传过来的是 10  20  转换成文案后 去匹配
//            ->SetQueryByRegStatus($searchOptionArr)
//            // 注册资本 传过来的是 10 20 转换成最大最小范围后 再去搜索
//            ->SetQueryByRegCaptial($searchOptionArr)
//            // 团队人数 传过来的是 10 20 转换成最大最小范围后 再去搜索
//            ->SetQueryByTuanDuiRenShu($searchOptionArr)
//            // 营收规模  传过来的是 10 20 转换成对应文案后再去匹配
//            ->SetQueryByYingShouGuiMo($searchOptionArr)
//            //四级分类 basic_nicid: A0111,A0112,A0113,
//            ->SetQueryBySiJiFenLei(trim($this->request()->getRequestParam('basic_nicid')))
//            // 地区 basic_regionid: 110101,110102,
//            ->SetQueryByBasicRegionid( trim($this->request()->getRequestParam('basic_regionid')))
//            //->addSize($size)
//            //->addFrom($offset)
//            //设置默认值 不传任何条件 搜全部
//            ->setDefault()
//            ->searchFromEs('company_202208')
//            // 格式化下日期和时间
//            ->formatEsDate()
//            // 格式化下金额
//            ->formatEsMoney()
//        ;


        $rawOptions = (new XinDongService())->getSearchOption();
        $newOptions = [];

        foreach($companyEsModel->return_data['hits']['hits'] as $dataItem){
            $has_web = $dataItem['_source']['web']?'有':'无';

            $has_app = $dataItem['_source']['app']?'有':'无';

            $has_wu_liu_xin_xi = $dataItem['_source']['wu_liu_xin_xi']?'是':'否';

            foreach ($rawOptions as $key => $configs){
                $newOptions[$key]['pid'] = $configs['pid']; //
                $newOptions[$key]['desc'] = $configs['desc']; //
                $newOptions[$key]['detail'] = $configs['detail']; //
                $newOptions[$key]['key'] = $configs['key']; //
                $newOptions[$key]['type'] = $configs['type']; //
                // 企业类型
                if($configs['pid'] == 10){
                    foreach ($configs['data'] as $subKey => $item){
                        if($item['cname'] == $dataItem['_source']['company_org_type']){
                            $newOptions[$key]['data'][$subKey] = $item;

                        }
                        else{

                        }
                    };
                }
                //营业状态
                if($configs['pid'] == 30){
                    foreach ($configs['data'] as $subKey => $item){

                        if(strpos($dataItem['_source']['reg_status'],$item['cname']) !== false ){
                            $newOptions[$key]['data'][$subKey] = $item;

                        }
                        else{

                        }
                    };
                }
                //官网
                if($configs['pid'] == 70){
                    foreach ($configs['data'] as $subKey => $item){
                        if($item['cname'] == $has_web){
                            $newOptions[$key]['data'][$subKey] = $item;

                        }
                        else{

                        }
                    };
                }

                //有无APP
                if($configs['pid'] == 80){
                    foreach ($configs['data'] as $subKey => $item){
                        if($item['cname'] == $has_app){
                            $newOptions[$key]['data'][$subKey] = $item;

                        }else{

                        }

                    };
                }
                //是否物流企业
                if($configs['pid'] == 90){
                    foreach ($configs['data'] as $subKey => $item){
                        if($item['cname'] == $has_wu_liu_xin_xi){
                            $newOptions[$key]['data'][$subKey] = $item;
                            CommonService::getInstance()->log4PHP(
                                json_encode([
                                    __CLASS__.__FUNCTION__ .__LINE__,
                                    'wu_liu_xin_xi matched' => true,
                                    '$subKey' => $subKey,
                                    '$item' => $item,
                                    'cname'=>$item['cname'],
                                    'wu_liu_xin_xi'=>$dataItem['_source']['wu_liu_xin_xi'],
                                    'name'=>$dataItem['_source']['name'],
                                ])
                            );
                            // break;
                        }
                        else{

                        }
                    }
                }

                //成立年限
                if($configs['pid'] == 20){
                    foreach ($configs['data'] as $subKey => $item){
                        if($dataItem['_source']['estiblish_time'] <= 1){
                            continue;
                        }

                        $yearsNums = date('Y') - date('Y',strtotime($dataItem['_source']['estiblish_time']));
                        if(
                            $yearsNums >= $item['min'] &&
                            $yearsNums <  $item['max']
                        ){
                            $newOptions[$key]['data'][$subKey] = $item;

                        }
                        else{

                        }
                    };
                }

                //注册资本
                if($configs['pid'] == 40){
                    foreach ($configs['data'] as $subKey => $item){
                        if(
                            $dataItem['_source']['reg_capital'] >= $item['min'] &&
                            $dataItem['_source']['reg_capital'] <  $item['max']
                        ){
                            $newOptions[$key]['data'][$subKey] = $item;

                        }
                        else{

                        }
                    };
                }
                //营收规模
                if($configs['pid'] == 50){
                    foreach ($configs['data'] as $subKey => $item){
                        if( !$dataItem['_source']['ying_shou_gui_mo']){
                            continue;
                        }
                        $yingshouguimomap = XinDongService::getYingShouGuiMoMapV2();
                        $yingshouguimoItem = $yingshouguimomap[$dataItem['_source']['ying_shou_gui_mo']];
                        if(
                            $yingshouguimoItem['min'] >= $item['min'] &&
                            $yingshouguimoItem['max'] <  $item['max']
                        ){
                            $newOptions[$key]['data'][$subKey] = $item;

                        }
                        else{

                        }
                    };
                }
                //企业规模
                if($configs['pid'] == 60){
                    foreach ($configs['data'] as $subKey => $item){
                        if(
                            $dataItem['_source']['tuan_dui_ren_shu'] >= $item['min'] &&
                            $dataItem['_source']['tuan_dui_ren_shu'] <  $item['max']
                        ){
                            $newOptions[$key]['data'][$subKey] = $item;

                        }
                        else{

                        }
                    };
                }
            }
        }

        $newOptionsV2 = [];
        foreach ($newOptions as $option){
            if(empty($option['data'])){
                continue;
            }
            $newOptionsV2[] = $option;
        }
        return $this->writeJson(200,
            [  ]
            , $newOptionsV2, '成功', true, []);
    }
    function advancedSearchOptionOld(): bool
    {
        $requestData =  $this->getRequestData();

        if(substr($requestData['basic_nicid'], -1) == ','){
            $requestData['basic_nicid'] = rtrim($requestData['basic_nicid'], ",");
        }

        if(substr($requestData['basic_regionid'], -1) == ','){
            $requestData['basic_regionid'] = rtrim($requestData['basic_regionid'], ",");
        }

        if(substr($requestData['basic_jlxxcyid'], -1) == ','){
            $requestData['basic_jlxxcyid'] = rtrim($requestData['basic_jlxxcyid'], ",");
        }


        $companyEsModel = new \App\ElasticSearch\Model\Company();

        //传过来的searchOption 例子 [{"type":20,"value":["5","10","2"]},{"type":30,"value":["15","5"]}]
        $searchOptionStr =  trim($this->request()->getRequestParam('searchOption'));
        $searchOptionArr = json_decode($searchOptionStr, true);

        $size = $this->request()->getRequestParam('size')??10;
        $page = $this->request()->getRequestParam('page')??1;
        $offset  =  ($page-1)*$size;
        //区域搜索
        $areas_arr  = json_decode($requestData['areas'],true) ;
        if(!empty($areas_arr)){
//            CommonService::getInstance()->log4PHP(
//                json_encode([
//                    __CLASS__.__FUNCTION__ .__LINE__,
//                    '$areas_arr' => $areas_arr,
//                ])
//            );

            //区域多边形搜索：要闭合：即最后一个点要和最后一个点重合
            $first = $areas_arr[0];;
            $last =  end($areas_arr);
            if(
                strval($first[0])!= strval($last[0]) ||
                strval($first[1])!= strval($last[1])
            ){
                $areas_arr[] = $first;
//                CommonService::getInstance()->log4PHP(
//                    json_encode([
//                        __CLASS__.__FUNCTION__ .__LINE__,
//                        'add_new_first' => true,
//                        '$areas_arr' => $areas_arr,
//                        'strval($first[0])' => strval($first[0]),
//                        'strval($last[0])'=>strval($last[0]),
//                    ])
//                );
            }else{
//                CommonService::getInstance()->log4PHP(
//                    json_encode([
//                        __CLASS__.__FUNCTION__ .__LINE__,
//                        'add_new_first' => false,
//                        '$areas_arr' => $areas_arr,
//                        'strval($first[0])' => strval($first[0]),
//                        'strval($last[0])'=>strval($last[0]),
//                    ])
//                );
            }
        }
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
            ->SetAreaQueryV3($areas_arr,$requestData['areas_type']?:1)
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
            //->addSize($size)
            //->addFrom($offset)
            //设置默认值 不传任何条件 搜全部
            ->setDefault()
            ->searchFromEs()
            // 格式化下日期和时间
            ->formatEsDate()
            // 格式化下金额
            ->formatEsMoney()
        ;

        $rawOptions = (new XinDongService())->getSearchOption();
        $newOptions = [];
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                __CLASS__.__FUNCTION__ .__LINE__,
//                'search_optionXXX' => $companyEsModel->return_data
//            ])
//        );
        foreach($companyEsModel->return_data['hits']['hits'] as $dataItem){
            $has_web = $dataItem['_source']['web']?'有':'无';

            $has_app = $dataItem['_source']['app']?'有':'无';

            $has_wu_liu_xin_xi = $dataItem['_source']['wu_liu_xin_xi']?'是':'否';

            foreach ($rawOptions as $key => $configs){
                $newOptions[$key]['pid'] = $configs['pid']; //
                $newOptions[$key]['desc'] = $configs['desc']; //
                $newOptions[$key]['detail'] = $configs['detail']; //
                $newOptions[$key]['key'] = $configs['key']; //
                $newOptions[$key]['type'] = $configs['type']; //
                // 企业类型
                if($configs['pid'] == 10){
                    foreach ($configs['data'] as $subKey => $item){
                        if($item['cname'] == $dataItem['_source']['company_org_type']){
                            $newOptions[$key]['data'][$subKey] = $item;
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'company_org_type_matched' => true,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'cname'=>$item['cname'],
//                                    'company_org_type'=>$dataItem['_source']['company_org_type'],
//                                ])
//                            );
                            //break;
                        }
                        else{
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'company_org_type_matched' => false,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'cname'=>$item['cname'],
//                                    'company_org_type'=>$dataItem['_source']['company_org_type'],
//                                ])
//                            );
                        }
                    };
                }
                //营业状态
                if($configs['pid'] == 30){
                    foreach ($configs['data'] as $subKey => $item){
                        if($item['cname'] == $dataItem['_source']['reg_status']){
                            $newOptions[$key]['data'][$subKey] = $item;
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'reg_status_matched' => true,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'cname'=>$item['cname'],
//                                    'company_org_type'=>$dataItem['_source']['reg_status'],
//                                ])
//                            );
                            //break;
                        }
                        else{
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'reg_status_matched' => false,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'cname'=>$item['cname'],
//                                    'company_org_type'=>$dataItem['_source']['reg_status'],
//                                ])
//                            );
                        }
                    };
                }
                //官网
                if($configs['pid'] == 70){
                    foreach ($configs['data'] as $subKey => $item){
                        if($item['cname'] == $has_web){
                            $newOptions[$key]['data'][$subKey] = $item;
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'web matched' => true,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'cname'=>$item['cname'],
//                                    'web'=>$dataItem['_source']['web'],
//                                ])
//                            );
                            //break;
                        }
                        else{
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'web matched' => false,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'cname'=>$item['cname'],
//                                    'web'=>$dataItem['_source']['web'],
//                                ])
//                            );
                        }
                    };
                }

                //有无APP
                if($configs['pid'] == 80){
                    foreach ($configs['data'] as $subKey => $item){
                        if($item['cname'] == $has_app){
                            $newOptions[$key]['data'][$subKey] = $item;
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'app matched' => true,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'cname'=>$item['cname'],
//                                    'app'=>$dataItem['_source']['app'],
//                                ])
//                            );
                            //break;
                        }else{
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'app matched' => false,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'cname'=>$item['cname'],
//                                    'app'=>$dataItem['_source']['app'],
//                                ])
//                            );
                        }

                    };
                }
                //是否物流企业
                if($configs['pid'] == 90){
                    foreach ($configs['data'] as $subKey => $item){
                        if($item['cname'] == $has_wu_liu_xin_xi){
                            $newOptions[$key]['data'][$subKey] = $item;
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'wu_liu_xin_xi matched' => true,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'cname'=>$item['cname'],
//                                    'wu_liu_xin_xi'=>$dataItem['_source']['wu_liu_xin_xi'],
//                                ])
//                            );
                            // break;
                        }
                        else{
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'wu_liu_xin_xi matched' => false,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'cname'=>$item['cname'],
//                                    'wu_liu_xin_xi'=>$dataItem['_source']['wu_liu_xin_xi'],
//                                ])
//                            );
                        }
                    }
                }

                //成立年限
                if($configs['pid'] == 20){
                    foreach ($configs['data'] as $subKey => $item){
                        if(
                            $dataItem['_source']['estiblish_year_nums'] >= $item['min'] &&
                            $dataItem['_source']['estiblish_year_nums'] <  $item['max']
                        ){
                            $newOptions[$key]['data'][$subKey] = $item;
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'estiblish_year_nums matched' => true,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'estiblish_year_nums'=>$dataItem['_source']['estiblish_year_nums'],
//                                ])
//                            );
                            //break;
                        }
                        else{
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'estiblish_year_nums matched' => false,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'estiblish_year_nums'=>$dataItem['_source']['estiblish_year_nums'],
//                                ])
//                            );
                        }
                    };
                }

                //注册资本
                if($configs['pid'] == 40){
                    foreach ($configs['data'] as $subKey => $item){
                        if(
                            $dataItem['_source']['reg_capital'] >= $item['min'] &&
                            $dataItem['_source']['reg_capital'] <  $item['max']
                        ){
                            $newOptions[$key]['data'][$subKey] = $item;
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'reg_capital matched' => true,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'reg_capital'=>$dataItem['_source']['reg_capital'],
//                                ])
//                            );
                            //break;
                        }
                        else{
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'reg_capital matched' => false,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'reg_capital'=>$dataItem['_source']['reg_capital'],
//                                ])
//                            );
                        }
                    };
                }
                //营收规模
                if($configs['pid'] == 50){
                    foreach ($configs['data'] as $subKey => $item){
                        if(
                            $dataItem['_source']['ying_shou_gui_mo'] >= $item['min'] &&
                            $dataItem['_source']['ying_shou_gui_mo'] <  $item['max']
                        ){
                            $newOptions[$key]['data'][$subKey] = $item;
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'ying_shou_gui_mo matched' => true,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'reg_capital'=>$dataItem['_source']['ying_shou_gui_mo'],
//                                ])
//                            );
                            //break;
                        }
                        else{
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'ying_shou_gui_mo matched' => false,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'reg_capital'=>$dataItem['_source']['ying_shou_gui_mo'],
//                                ])
//                            );
                        }
                    };
                }
                //企业规模
                if($configs['pid'] == 60){
                    foreach ($configs['data'] as $subKey => $item){
                        if(
                            $dataItem['_source']['tuan_dui_ren_shu'] >= $item['min'] &&
                            $dataItem['_source']['tuan_dui_ren_shu'] <  $item['max']
                        ){
                            $newOptions[$key]['data'][$subKey] = $item;
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'tuan_dui_ren_shu matched' => true,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'tuan_dui_ren_shu'=>$dataItem['_source']['tuan_dui_ren_shu'],
//                                ])
//                            );
                            //break;
                        }
                        else{
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'tuan_dui_ren_shu matched' => false,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'tuan_dui_ren_shu'=>$dataItem['_source']['tuan_dui_ren_shu'],
//                                ])
//                            );
                        }
                    };
                }
            }
        }

        $newOptionsV2 = [];
        foreach ($newOptions as $option){
            if(empty($option['data'])){
                continue;
            }
            $newOptionsV2[] = $option;
        }
        return $this->writeJson(200,
            [  ]
            , $newOptionsV2, '成功', true, []);
    }


    // 保存搜索历史
     function saveSearchHistroy(): bool
     { 
        $queryName = trim($this->request()->getRequestParam('query_name'));
        if(!$queryName){
            return  $this->writeJson(201, null, null, '参数缺失（搜索历史名称）');
        }
         // 记录搜索历史
         $res = (new XinDongService())->saveSearchHistory(
             $this->loginUserinfo['id'],  
             json_encode($this->request()->getRequestParam()),
             $queryName
         );

         if(!$res){
            return  $this->writeJson(201, null, null, '保存失败，请联系管理员');
         }

         return $this->writeJson(200, ['total' => 1], [], '成功', true, []);
     }
    //  function advancedSearchOld(): bool
    // { 
    //     $ElasticSearchService = new ElasticSearchService(); 

    //     // 数字经济及其核心产业 050101,050102 需要转换为四级分类 然后再搜索
    //     $szjjidsStr = trim($this->request()->getRequestParam('basic_szjjid'));
    //     $szjjidsStr && $szjjidsArr = explode(',', $szjjidsStr);
    //     if($szjjidsArr){
    //         $szjjidsStr = implode("','", $szjjidsArr); 
    //         $sql = "SELECT
    //                     nic_id 
    //                 FROM
    //                     nic_code
    //                 WHERE
    //                 nssc IN (
    //                     SELECT
    //                         id 
    //                     FROM
    //                         `szjj_nic_code` 
    //                     WHERE
    //                     szjj_id IN ( '$szjjidsStr' ) 
    //                 )
    //         ";

    //         $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_nic_code'));
    //         $nicIds = array_column($list, 'nic_id');
            
    //         CommonService::getInstance()->log4PHP($sql);
    //         CommonService::getInstance()->log4PHP($list);
    //         CommonService::getInstance()->log4PHP($nicIds); 

    //         if(!empty($nicIds)){
    //             foreach($nicIds as &$nicId){
    //                 if(
    //                     strlen($nicId) == 5 &&
    //                     substr($nicId, -1) == '0'
    //                 ){
    //                     $nicId = substr($nicId, 0, -1);
    //                 }
    //             } 
    //             CommonService::getInstance()->log4PHP($nicIds);
    //             $ElasticSearchService->addMustShouldPhrasePrefixQuery( 'si_ji_fen_lei_code' , $nicIds) ; 
    //         } 
    //     }  

    //     $searchText = trim($this->request()->getRequestParam('searchText'));
    //     if($searchText){
    //         $matchedCnames = [
    //             [ 'field'=>'name' ,'value'=> $searchText],
    //             [ 'field'=>'shang_pin_data.name' ,'value'=> $searchText],
    //             [ 'field'=>'basic_opscope' ,'value'=> $searchText] 
    //         ];
    //         $ElasticSearchService->addMustShouldPhraseQueryV2($matchedCnames) ;  
    //     }
       


    //     // 需要按文本搜索的  
    //     $addMustMatchPhraseQueryMap = [
    //         // 名称  name  全名匹配 
    //         // 'name' =>trim($this->request()->getRequestParam('searchText')),
    //         // basic_opscope: 经营范围
    //         'business_scope' =>trim($this->request()->getRequestParam('basic_opscope')),
    //     ];
    //     foreach($addMustMatchPhraseQueryMap as $field=>$value){
    //         $value && $ElasticSearchService->addMustMatchPhraseQuery( $field , $value) ; 
    //     } 

    //     // 搜索战略新兴产业
    //     $basicJlxxcyidStr = trim($this->request()->getRequestParam('basic_jlxxcyid'));
    //     $basicJlxxcyidStr && $basicJlxxcyidArr = explode(',',  $basicJlxxcyidStr);
    //     if(
    //         !empty($basicJlxxcyidArr)
    //     ){
    //         $siJiFenLeiDatas = \App\HttpController\Models\RDS3\ZlxxcyNicCode::create()
    //             ->where('zlxxcy_id', $basicJlxxcyidArr, 'IN') 
    //             ->all();
    //         $matchedCnames = array_column($siJiFenLeiDatas, 'nic_id');
    //        $ElasticSearchService
    //             ->addMustShouldPhraseQuery( 'si_ji_fen_lei_code' , $matchedCnames) ; 
    
    //     }

    //     // 搜索shang_pin_data 商品信息 appStr:五香;农庄
    //     $appStr =   trim($this->request()->getRequestParam('appStr')); 
    //     $appStr && $appStrDatas = explode(';', $appStr);
    //     !empty($appStrDatas) && $ElasticSearchService->addMustShouldPhraseQuery( 'shang_pin_data.name' , $appStrDatas) ;
    
    //     //传过来的searchOption 例子 [{"type":20,"value":["5","10","2"]},{"type":30,"value":["15","5"]}]
    //     $searchOptionStr =  trim($this->request()->getRequestParam('searchOption'));
    //     $searchOptionArr = json_decode($searchOptionStr, true);
        
    //     // 把具体需要搜索的各项摘出来
    //     $org_type_values = [];  // 企业类型  
    //     $estiblish_time_values = [];  // 成立年限  
    //     $reg_status_values = [];// 营业状态 
    //     $reg_capital_values = [];  // 注册资本
    //     $ying_shou_gui_mo_values = [];  // 营收规模
    //     $tuan_dui_ren_shu_values = [];  // 团队人数
    //     $web_values = []; //官网
    //     $app_values = []; //官网
    //     foreach($searchOptionArr as $item){ 
    //         if($item['pid'] == 10){
    //             $org_type_values = $item['value'];  
    //         }
 
    //         if($item['pid'] == 20){ 
    //             $estiblish_time_values = $item['value']; 
    //         }
   
    //         if($item['pid'] == 30){
    //             $reg_status_values = $item['value']; 
    //         }
 
    //         if($item['pid'] == 40){ 
    //             $reg_capital_values = $item['value']; 
    //         }
  
    //         if($item['pid'] == 50){ 
    //             $ying_shou_gui_mo_values = $item['value']; 
    //         }
    //         if($item['pid'] == 60){ 
    //             $tuan_dui_ren_shu_values = $item['value']; 
    //         }
    //         if($item['pid'] == 70){ 
    //             $web_values = $item['value']; 
    //         }
    //         if($item['pid'] == 80){ 
    //             $app_values = $item['value']; 
    //         }
    //     }

    //     //必须存在官网 
    //     foreach($web_values as $value){
    //         if($value){
    //             // $ElasticSearchService->addMustExistsQuery( 'web') ; 
    //             $ElasticSearchService->addMustRegexpQuery( 'web', ".+") ; 
                
    //             break;
    //         }
    //     }

    //     //必须存在APP 
    //     foreach($app_values as $value){
    //         if($value){ 
    //             $ElasticSearchService->addMustRegexpQuery( 'app', ".+") ;  
    //             break;
    //         }
    //     }

    //     // 企业类型 :传过来的是10 20 转换成对应文案 然后再去搜索  
    //     $matchedCnames = [];
    //     foreach($org_type_values as $orgType){
    //         $orgType && $matchedCnames[] = (new XinDongService())->getCompanyOrgType()[$orgType]; 
    //     }
    //     (!empty($matchedCnames)) && $ElasticSearchService->addMustShouldPhraseQuery( 'company_org_type' , $matchedCnames) ;
    
    //     // 成立年限  ：传过来的是 10  20 30 转换成最小值最大值范围后 再去搜索
    //     $matchedCnames = [];
    //     $map = [
    //         // 2年以内
    //         2 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -2 year')), 'max' => date('Y-m-d')  ],
    //         // 2-5年
    //         5 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -5 year')), 'max' => date('Y-m-d', strtotime(date('Y-m-01') . ' -2 year'))  ],
    //         // 5-10年
    //         10 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -10 year')), 'max' => date('Y-m-d', strtotime(date('Y-m-01') . ' -5 year'))  ],
    //         // 10-15年
    //         15 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -15 year')), 'max' => date('Y-m-d', strtotime(date('Y-m-01') . ' -10 year'))  ],
    //         // 15-20年
    //         20 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -20 year')), 'max' => date('Y-m-d', strtotime(date('Y-m-01') . ' -15 year'))  ],
    //         // 20年以上
    //         25 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -100 year')), 'max' => date('Y-m-d', strtotime(date('Y-m-01') . ' -20 year'))  ],
    //     ];
    //     foreach($estiblish_time_values as $item){
    //         $item && $matchedCnames[] = $map[$item]; 
    //     } 
    //     (!empty($matchedCnames)) && $ElasticSearchService->addMustShouldRangeQuery( 'estiblish_time' , $matchedCnames) ; 
    
    //     // 营业状态   传过来的是 10  20  转换成文案后 去匹配  
    //     $matchedCnames = [];
    //     foreach($reg_status_values as $item){
    //         $item && $matchedCnames[] = (new XinDongService())->getRegStatus()[$item]; 
    //     }
    //     (!empty($matchedCnames)) && $ElasticSearchService->addMustShouldPhraseQuery( 'reg_status' , $matchedCnames) ; 
    
    //     // 注册资本 传过来的是 10 20 转换成最大最小范围后 再去搜索
        
    //     $map = XinDongService::getZhuCeZiBenMap();
    //     foreach($reg_capital_values as $item){
    //         $tmp = $map[$item]['epreg']; 
    //         foreach($tmp as $tmp_item){
    //             $matchedCnames[] = $tmp_item;
    //         } 
    //     } 
    //     (!empty($matchedCnames)) && $ElasticSearchService->addMustShouldRegexpQuery( 
    //         'reg_capital' , $matchedCnames
    //     ) ;


    //     // 团队人数 传过来的是 10 20 转换成最大最小范围后 再去搜索
    //     $map =  (new XinDongService())::getTuanDuiGuiMoMap();
    //     $matchedCnames = [];
        
    //     foreach($tuan_dui_ren_shu_values as $item){
    //         $tmp = $map[$item]['epreg']; 
    //         foreach($tmp as $tmp_item){
    //             $matchedCnames[] = $tmp_item;
    //         } 
    //     } 
    //     (!empty($matchedCnames)) && $ElasticSearchService->addMustShouldRegexpQuery( 
    //         'tuan_dui_ren_shu' , $matchedCnames
    //     ) ;
       


    //     // 营收规模  传过来的是 10 20 转换成对应文案后再去匹配
    //     $map = [ 
    //         5 => ['A1'], //微型
    //         10 => ['A2'], //小型C类
    //         15 => ['A3'],// 小型B类
    //         20 => ['A4'],// 小型A类
    //         25 => ['A5'],// 中型C类
    //         30 => ['A6'],// 中型B类
    //         40 => ['A7'],// 中型A类
    //         45 => ['A8'],// 大型C类
    //         50 => ['A9'],//大型B类 
    //         60 => ['A10'],//大型A类，一般指规模在10亿以上，50亿以下 
    //         65 => ['A11'],//'特大型C类，一般指规模在50亿以上，100亿以下'
    //         70 => ['A12'],//'特大型C类，一般指规模在50亿以上，100亿以下'
    //         80 => ['A13'],//'特大型C类，一般指规模在50亿以上，100亿以下' 
    //     ];

    //     $matchedCnamesRaw = [];
    //     foreach($ying_shou_gui_mo_values as $item){
    //         $item && $matchedCnamesRaw[] = $map[$item]; 
    //     }
    //     $matchedCnames = [];
    //     foreach($matchedCnamesRaw as $items){
    //         foreach($items as $item){
    //             $matchedCnames[] = $item;
    //         }
    //     }

    //     (!empty($matchedCnames)) && $ElasticSearchService->addMustShouldPhraseQuery( 'ying_shou_gui_mo' , $matchedCnames) ;  
        


    //     //四级分类 basic_nicid: A0111,A0112,A0113,
    //     $siJiFenLeiStrs = trim($this->request()->getRequestParam('basic_nicid'));
    //     $siJiFenLeiStrs && $siJiFenLeiArr = explode(',', $siJiFenLeiStrs); 
    //     if(!empty($siJiFenLeiArr)){
    //         $ElasticSearchService->addMustShouldPhraseQuery( 'si_ji_fen_lei_code' , $siJiFenLeiArr) ;   
    //     }

    //     // 地区 basic_regionid: 110101,110102,
    //     $basiRegionidStr = trim($this->request()->getRequestParam('basic_regionid')); 
    //     $basiRegionidStr && $basiRegionidArr = explode(',',$basiRegionidStr);
    //     if(!empty($basiRegionidArr)){ 
    //         $ElasticSearchService->addMustShouldPrefixQuery( 'reg_number' , $basiRegionidArr) ;  
    //     }

    //     $size = $this->request()->getRequestParam('size')??10;
    //     $page = $this->request()->getRequestParam('page')??1;
    //     $offset  =  ($page-1)*$size;
    //     $ElasticSearchService->addSize($size) ;
    //     $ElasticSearchService->addFrom($offset) ;
    //     $ElasticSearchService->addSort('xd_id', 'desc') ;

    //     //设置默认值 不传任何条件 搜全部
    //     $ElasticSearchService->setDefault() ;  

    //     $responseJson = (new XinDongService())->advancedSearch($ElasticSearchService);
    //     $responseArr = @json_decode($responseJson,true); 
    //     CommonService::getInstance()->log4PHP('advancedSearch-Es '.@json_encode(
    //         [
    //             // 'hits' => $responseArr['hits']['hits'],
    //             'es_query' => $ElasticSearchService->query,
    //             'post_data' => $this->request()->getRequestParam(),
    //         ]
    //     )); 

    //     // 格式化下日期和时间
    //     $hits = (new XinDongService())::formatEsDate($responseArr['hits']['hits'], [
    //         'estiblish_time',
    //         'from_time',
    //         'to_time',
    //         'approved_time'
    //     ]);
    //     $hits = (new XinDongService())::formatEsMoney($hits, [
    //         'reg_capital', 
    //     ]);

    //     foreach($hits as &$dataItem){ 
    //         // 添加tag  
    //         $dataItem['_source']['tags'] = array_values(
    //             (new XinDongService())::getAllTagesByData(
    //                 $dataItem['_source'] 
    //             )
    //         );

    //         // 官网
    //         $webStr = trim($dataItem['_source']['web']);
    //         if(!$webStr){
    //             continue; 
    //         } 
    //         $webArr = explode('&&&', $webStr);
    //         !empty($webArr) && $dataItem['_source']['web'] = end($webArr); 
    //     }
    
    //     return $this->writeJson(200, 
    //       [
    //         'page' => $page,
    //         'pageSize' =>$size,
    //         'total' => intval($responseArr['hits']['total']['value']),
    //         'totalPage' => (int)floor(intval($responseArr['hits']['total']['value'])/
    //         ($size)),
         
    //     ] 
    //    , $hits, '成功', true, []);
    // }

    /**
      * 
      * 基本信息 
        https://api.meirixindong.com/api/v1/xd/getCompanyBasicInfo 
      * 
      * 
     */
    function getCompanyBasicInfo(): bool
    {
        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业ID)');
        }

        $res = (new XinDongService())->getEsBasicInfoV2($companyId);
        $res['ENTTYPE_CNAME'] =   '';
        $res['ENTTYPE'] && $res['ENTTYPE_CNAME'] =   CodeCa16::findByCode($res['ENTTYPE']);
        $res['ENTSTATUS_CNAME'] =   '';
        $res['ENTSTATUS'] && $res['ENTSTATUS_CNAME'] =   CodeEx02::findByCode($res['ENTSTATUS']);
//        $retData['LAST_DOM'] = $res['LAST_DOM'];
//        $retData['LAST_EMAIL'] = $res['LAST_EMAIL'];
        return $this->writeJson(200, ['total' => 1], $res, '成功', true, []);
    }
    function getCompanyBasicInfoOld(): bool
    {  
        $companyId = intval($this->request()->getRequestParam('xd_id')); 
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业ID)');
        }
        
        $retData  = Company::create()->where('id', $companyId)->get();
        $retData = (new XinDongService())::formatObjDate(
            $retData,
            [
                'estiblish_time',
                'from_time',
                'to_time',
                'approved_time',
            ]
        );
        $retData = (new XinDongService())::formatObjMoney(
            $retData,
            [
                'reg_capital',
                'actual_capital', 
            ]
        );
         
        $retData['logo'] =  (new XinDongService())->getLogoByEntId($retData['id']);
        $res = (new XinDongService())->getEsBasicInfo($companyId); 
        $retData['last_postal_address'] = $res['last_postal_address'];
        $retData['last_email'] = $res['last_email'];
        return $this->writeJson(200, ['total' => 1], $retData, '成功', true, []);
    }

     /**
      * 
      * 高级搜索 
        https://api.meirixindong.com/api/v1/xd/getCpwsList 
      * 
      * 
     */
    function getCpwsList(): bool
    {
        $page = $this->getRequestData('page');
        $page = $page > 0? $page :1;
        $pageSize = $this->getRequestData('size');
        $pageSize = $pageSize > 0? $pageSize :10;
        $postData = [
            'entName' => trim($this->getRequestData('entName')),
            'page' => $page,
            'pageSize' => $pageSize,
        ];

        if (!$postData['entName']) {
            return $this->writeJson(201, null, null, '参数缺失(企业名称)');
        }

        $res = (new LongXinService())->setCheckRespFlag(true)->getCpwsList($postData);
        return   $this->writeJson(200,  $res['paging'],  $res['result'], '成功', true, []);  
    }

     /**
      * 
      *  
        https://api.meirixindong.com/api/v1/xd/getCpwsDetail 
      * 
      * 
     */
    function getCpwsDetail(): bool
    {
        $postData = [
            'mid' => $this->getRequestData('mid'),
        ];

        $res = (new LongXinService())->setCheckRespFlag(true)->getCpwsDetail($postData); 

        return   $this->writeJson(200,  ['total' => 1],  $res['result'], '成功', true, []);  
        // return $this->checkResponse($res);
    }

    /**
      * 
      *  
        https://api.meirixindong.com/api/v1/xd/getKtggList 
      * 
      * 
     */
    function getKtggList(): bool
    {
        $page = $this->getRequestData('page');
        $page = $page > 0? $page :1;
        $pageSize = $this->getRequestData('size');
        $pageSize = $pageSize > 0? $pageSize :10;

        $postData = [
            'entName' => $this->getRequestData('entName'),
            'page' => $page,
            'pageSize' => $pageSize,
        ];

         $res = (new LongXinService())->setCheckRespFlag(true)->getKtggList($postData);

         return   $this->writeJson(200,  $res['paging'],  $res['result'], '成功', true, []);  
        // return $this->checkResponse($res); 
    }

    function getKtggDetail(): ?bool
    {
        $postData = [
            'mid' => $this->getRequestData('mid'),
        ];

        $res = (new LongXinService())->setCheckRespFlag(true)->getKtggDetail($postData);
        
        return   $this->writeJson(200,   ['total' => 1], $res['result'], '成功', true, []);  
        // return $this->checkResponse($res); 
    }

    /**
      * 
      * 专业资质 荣誉称号  
        https://api.meirixindong.com/api/v1/xd/getHighTecQualifications 
      * 
      * 
     */
    function getHighTecQualifications(): bool
    {
        $page = intval($this->request()->getRequestParam('page'));
        $page = $page>0 ?$page:1;
        $size = intval($this->request()->getRequestParam('size'));
        $size = $size>0 ?$size:10;
        $offset = ($page-1)*$size;

        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }

        $res = MostTorchHightechH::findByConditionV3(
            [
                ['field'=>'companyid','value'=>$companyId,'operate'=>'=']
            ]
        );
        return $this->writeJson(200,
            ['total' => $res['total'],'page' => $page, 'pageSize' => $size, 'totalPage'=> floor($res['total']/$size)],
            $res['data'], '成功', true, []
        );
    }
    function getHighTecQualificationsOld(): bool
    {  
        $page = intval($this->request()->getRequestParam('page'));
        $page = $page>0 ?$page:1; 
        $size = intval($this->request()->getRequestParam('size')); 
        $size = $size>0 ?$size:10; 
        $offset = ($page-1)*$size;  

        $companyId = intval($this->request()->getRequestParam('xd_id')); 
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }
 
        $model = \App\HttpController\Models\RDS3\XdHighTec::create()
            ->where('xd_id', $companyId)->page($page)->withTotalCount();
        $retData = $model->all(); 
        $total = $model->lastQueryResult()->getTotalCount(); 
        return $this->writeJson(200, ['total' => $total,'page' => $page, 'pageSize' => $size, 'totalPage'=> floor($total/$size)], $retData, '成功', true, []);
    }

    /**
      * 
      * 专业资质 荣誉称号 （瞪羚） 
        https://api.meirixindong.com/api/v1/xd/getDengLingQualifications 
      * 
      * 
     */
    function getDengLingQualifications(): bool
    {  
        $page = intval($this->request()->getRequestParam('page'));
        $page = $page>0 ?$page:1; 
        $size = intval($this->request()->getRequestParam('size')); 
        $size = $size>0 ?$size:10; 
        $offset = ($page-1)*$size;  

        $companyId = intval($this->request()->getRequestParam('xd_id')); 
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }
 
        $model = \App\HttpController\Models\RDS3\XdDl::create()
            ->where('xd_id', $companyId)->page($page)->withTotalCount();
        $retData = $model->all();
        $total = $model->lastQueryResult()->getTotalCount(); 
        return $this->writeJson(200, ['total' => $total,'page' => $page, 'pageSize' => $size, 'totalPage'=> floor($total/$size)], $retData, '成功', true, []);
    }

    /**
      * 
      * 专业资质 荣誉称号 (Iso) 
        https://api.meirixindong.com/api/v1/xd/getIsoQualifications 
      * 
      * 
     */
    function getIsoQualifications(): bool
    {
        $page = intval($this->request()->getRequestParam('page'));
        $page = $page>0 ?$page:1;
        $size = intval($this->request()->getRequestParam('size'));
        $size = $size>0 ?$size:10;
        $offset = ($page-1)*$size;

        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }

        $res = CncaRzGltxH::findByConditionV3(
            [
                [
                    'field' => 'companyid',
                    'value' => $companyId,
                    'operate' => '=',
                ]
            ]
        );

        $total = $res['total'];

        return $this->writeJson(200,
            ['total' => $total,'page' => $page, 'pageSize' => $size, 'totalPage'=> floor($total/$size)],
            $res['data'], '成功', true, []
        );

    }
    function getIsoQualificationsOld(): bool
    {  
        $page = intval($this->request()->getRequestParam('page'));
        $page = $page>0 ?$page:1; 
        $size = intval($this->request()->getRequestParam('size')); 
        $size = $size>0 ?$size:10; 
        $offset = ($page-1)*$size;  
        
        $companyId = intval($this->request()->getRequestParam('xd_id')); 
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }
 
        $model = \App\HttpController\Models\RDS3\XdDlRzGlTx::create()
            ->where('xd_id', $companyId)->page($page)->withTotalCount();
        $retData = $model->all();
        $total = $model->lastQueryResult()->getTotalCount(); 
        
        return $this->writeJson(200, ['total' => $total,'page' => $page, 'pageSize' => $size, 'totalPage'=> floor($total/$size)], $retData, '成功', true, []);
    
    }

    /**
      * 
      * 获取企业的人员规模信息  
        https://api.meirixindong.com/api/v1/xd/getEmploymenInfo 
      * 
      * 
     */
    function getEmploymenInfo(): bool
    {  
        $companyId = intval($this->request()->getRequestParam('xd_id')); 
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }
        
        $retData  =\App\HttpController\Models\RDS3\TuanDuiGuiMo::create()->where('xd_id', $companyId)->get();
        
        return $this->writeJson(200, ['total' => 1], $retData, '成功', true, []);
    }

     /**
      * 
      * 获取企业的营收规模  
        https://api.meirixindong.com/api/v1/xd/getBusinessScaleInfo 
      * 
      * 
     */
    function getBusinessScaleInfo(): bool
    {  
        $entname = trim($this->request()->getRequestParam('entname')); 
        if (!$entname) {
            return  $this->writeJson(201, null, null, '参数缺失(企业名称)');
        }
        
        $retData  =\App\HttpController\Models\RDS3\ArLable::create()->where('entname', $entname)->get();
        
        return $this->writeJson(200, ['total' => 1], $retData, '成功', true, []);
    }

    /**
      * 
      * 获取主营产品
        https://api.meirixindong.com/api/v1/xd/getMainProducts 
      * 
      * 
     */
    function getMainProductsOld(): bool
    {  
        $page = intval($this->request()->getRequestParam('page'));
        $page = $page>0 ?$page:1; 
        $size = intval($this->request()->getRequestParam('size')); 
        $size = $size>0 ?$size:10; 
        $offset = ($page-1)*$size;  

        $type = trim($this->request()->getRequestParam('type')); 
        if (!in_array($type,['ios', 'andoriod'])) {
            return  $this->writeJson(201, null, null, '参数缺失(类型)');
        }

        $companyId = intval($this->request()->getRequestParam('xd_id')); 
        if (!$companyId) {
            return $this->writeJson(201, null, null, '参数缺失(企业id)');
        }

        if($type == 'ios'){  
            $model = \App\HttpController\Models\RDS3\XdAppIos::create()
            ->where('xd_id', $companyId)->page($page)->withTotalCount();
            $retData = $model->all();
            $total = $model->lastQueryResult()->getTotalCount(); 
        }

        if($type == 'andoriod'){
            $model = \App\HttpController\Models\RDS3\XdAppAndroid::create()
            ->where('xd_id', $companyId)->page($page)->withTotalCount();
            $retData = $model->all();
            $total = $model->lastQueryResult()->getTotalCount();  
        } 
 
        return $this->writeJson(200,  ['total' => $total,'page' => $page, 'pageSize' => $size, 'totalPage'=> floor($total/$size)], $retData, '成功', true, []);
    }
    function getMainProducts(): bool
    {
        $page = intval($this->request()->getRequestParam('page'));
        $page = $page>0 ?$page:1;
        $size = intval($this->request()->getRequestParam('size'));
        $size = $size>0 ?$size:10;
        $offset = ($page-1)*$size;

        $type = trim($this->request()->getRequestParam('type'));
        if (!in_array($type,['ios', 'andoriod'])) {
            return  $this->writeJson(201, null, null, '参数缺失(类型)');
        }

        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return $this->writeJson(201, null, null, '参数缺失(企业id)');
        }

        if($type == 'ios'){
            $res = DataplusAppIosH::findByConditionV3(
                [
                    [
                        'value'=>$companyId,
                        'field'=>'companyid',
                        'operate'=>'=',
                    ]
                ]
            );
            $total = $res['total'];
        }

        if($type == 'andoriod'){
            $res = DataplusAppAndroidH::findByConditionV3(
                [
                    [
                        'value'=>$companyId,
                        'field'=>'companyid',
                        'operate'=>'=',
                    ]
                ]
            );
            $total = $res['total'];
        }

        return $this->writeJson(200,
            ['total' => $total,'page' => $page, 'pageSize' => $size, 'totalPage'=> floor($total/$size)],
            $res['data'], '成功', true, []);
    }
    function getCountInfoOld(): bool
    {  
        $page = intval($this->request()->getRequestParam('page'));
        $page = $page>0 ?$page:1; 
        $size = intval($this->request()->getRequestParam('size')); 
        $size = $size>0 ?$size:10; 
        $offset = ($page-1)*$size;  

        $companyId = intval($this->request()->getRequestParam('xd_id')); 
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(类型)');
        }

       
        $highTecCount = \App\HttpController\Models\RDS3\XdHighTec::create()
                ->where('xd_id', $companyId)->count();

        $isoCount = \App\HttpController\Models\RDS3\XdDlRzGlTx::create()
                ->where('xd_id', $companyId)->count();


        $iosCount = \App\HttpController\Models\RDS3\XdAppIos::create()
                ->where('xd_id', $companyId)->count();
        $andoriodCount = \App\HttpController\Models\RDS3\XdAppAndroid::create()
                ->where('xd_id', $companyId)->count();      

        $guDongCount = \App\HttpController\Models\RDS3\CompanyInvestor::create()
                ->where('company_id', $companyId)->count();
        // 没有工商股东信息 从企业自发查
        if(!$guDongCount){
                $guDongCount = \App\HttpController\Models\RDS3\CompanyInvestorEntPub::create()
                    ->where('company_id', $companyId)->count();
        } 

        $employeeCount = \App\HttpController\Models\RDS3\CompanyStaff::create()
                    ->where('company_id', $companyId)->count();   


        // 商品信息
        $ElasticSearchService = new ElasticSearchService();  
        $ElasticSearchService->addMustMatchQuery( 'xd_id' , $companyId) ;   
        $ElasticSearchService->addSize(1) ;
        $ElasticSearchService->addFrom(0) ; 
            
        $responseJson = (new XinDongService())->advancedSearch($ElasticSearchService);
        $responseArr = @json_decode($responseJson,true);  
        // 格式化下日期和时间
        $hits = $responseArr['hits']['hits'];
        $hits = (new XinDongService())::formatEsMoney($hits, [
            'reg_capital', 
        ]); 
            
        foreach($hits as $dataItem){
            $retData = $dataItem['_source']['shang_pin_data'];
            break;
        }           
        $shangPinTotal =  count($retData); //total items in array     
        
        $retData = [
            // 股东+人员
            'gong_shang' => intval($employeeCount + $guDongCount),
            // 商品
            'shang_pin' => $shangPinTotal,
            //专业资质 iso+高新
            'rong_yu' =>  intval($highTecCount + $isoCount),
            //ios +andoriod
            'app' => intval($iosCount+$andoriodCount),
        ];    
 
        return $this->writeJson(200,  [  ], $retData, '成功', true, []);
    }
    function getCountInfo(): bool
    {
        $page = intval($this->request()->getRequestParam('page'));
        $page = $page>0 ?$page:1;
        $size = intval($this->request()->getRequestParam('size'));
        $size = $size>0 ?$size:10;
        $offset = ($page-1)*$size;

        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(类型)');
        }


        $highTecCount = MostTorchHightechH::create()
            ->where('companyid', $companyId)->count();

        $isoCount = CncaRzGltxH::create()
            ->where('companyid', $companyId)->count();


        $iosCount = DataplusAppIosH::create()
            ->where('companyid', $companyId)->count();
        $andoriodCount = DataplusAppAndroidH::create()
            ->where('companyid', $companyId)->count();

        $guDongCount = CompanyInv::create()
            ->where('companyid', $companyId)->count();

        $employeeCount = CompanyManager::create()
            ->where('companyid', $companyId)->count();


        // 商品信息
        $ElasticSearchService = new ElasticSearchService();
        $ElasticSearchService->addMustMatchQuery( 'companyid' , $companyId) ;
        $ElasticSearchService->addSize(1) ;
        $ElasticSearchService->addFrom(0) ;

        $responseJson = (new XinDongService())->advancedSearch($ElasticSearchService);
        $responseArr = @json_decode($responseJson,true);
        // 格式化下日期和时间
        $hits = $responseArr['hits']['hits'];
        $hits = (new XinDongService())::formatEsMoney($hits, [
            'reg_capital',
        ]);

        foreach($hits as $dataItem){
            $retData = $dataItem['_source']['shang_pin_data'];
            break;
        }
        $shangPinTotal =  count($retData); //total items in array

        $retData = [
            // 股东+人员
            'gong_shang' => intval($employeeCount + $guDongCount),
            // 商品
            'shang_pin' => $shangPinTotal,
            //专业资质 iso+高新
            'rong_yu' =>  intval($highTecCount + $isoCount),
            //ios +andoriod
            'app' => intval($iosCount+$andoriodCount),
        ];

        return $this->writeJson(200,  [  ], $retData, '成功', true, []);
    }
     /**
      * 
      * 获取企业标签
        https://api.meirixindong.com/api/v1/xd/getTagInfo 
      * 
      * 
     */
    function getTagInfo(): bool
    {   
        $companyId = intval($this->request()->getRequestParam('xd_id')); 
        if (!$companyId) {
            return $this->writeJson(201, null, null, '参数缺失(企业id)');
        } 

        $companyData  =\App\HttpController\Models\RDS3\Company::create()->where('id', $companyId)->get();
        if(!$companyData){
            return $this->writeJson(201, null, null, '没有该企业');
        }

        $ElasticSearchService = new ElasticSearchService(); 
        
        $ElasticSearchService->addMustMatchQuery( 'xd_id' , $companyId) ;  
 
        $ElasticSearchService->addSize(1) ;
        $ElasticSearchService->addFrom(0) ; 

        $responseJson = (new XinDongService())->advancedSearch($ElasticSearchService);
        $responseArr = @json_decode($responseJson,true); 

        return $this->writeJson(200, ['total' => 1], 
        XinDongService::getAllTagesByData($responseArr['hits']['hits'][0]['_source']), 
        '成功', true, []);
    }

     /**
      * 
      * 获取主营产品
        https://api.meirixindong.com/api/v1/xd/getSearchHistory 
      * 
      * 
     */
    function getSearchHistory(): bool
    {  
        $page = intval($this->request()->getRequestParam('page'));
        $page = $page>0 ?$page:1; 
        $size = intval($this->request()->getRequestParam('size')); 
        $size = $size>0 ?$size:10; 
        $offset = ($page-1)*$size;  

        $model =  UserSearchHistory::create()
            ->where('userId', $this->loginUserinfo['id'])
            ->order('id', 'DESC')
            ->page($page)->withTotalCount();
        $retData = $model->all();
        $total = $model->lastQueryResult()->getTotalCount(); 
        
        foreach($retData as &$dataitem){
           $dataitem['post_data_arr'] = json_decode($dataitem['post_data'], true); 
        }
 
        return $this->writeJson(200,  ['total' => $total,'page' => $page, 'pageSize' => $size, 'totalPage'=> floor($total/$size)], $retData, '成功', true, []);
    }

    /**
      * 
      * 删除搜索历史
        https://api.meirixindong.com/api/v1/xd/delSearchHistory 
      * 
      * 
     */
    function delSearchHistory(): bool
    {  
        $id = intval($this->request()->getRequestParam('id')); 
        if (!$id) {
            return $this->writeJson(201, null, null, '参数缺失');
        }   
        
        if(
           !UserSearchHistory::create()->where('id', $id)->where('userId' , $this->loginUserinfo['id'])->get()
        ){
            return $this->writeJson(203, null, null, '没有该数据');
        } 

        try {
            $res = UserSearchHistory::create()->destroy(function (QueryBuilder $builder) use ($id) {
                $builder->where('id', $id)->where('userId' , $this->loginUserinfo['id']);
            }); 
        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP($e->getMessage());
        }

        if(!$res){
            return $this->writeJson(204, null, null, '删除失败');
        }

        return $this->writeJson(200,  [], [], '成功', true, []);
    }

    /**
      * 
      * 股东信息
        https://api.meirixindong.com/api/v1/xd/getInvestorInfo 
      * 
      * 
     */
    function getInvestorInfo(): bool
    {
        $page = intval($this->request()->getRequestParam('page'));
        $page = $page>0 ?$page:1;
        $size = intval($this->request()->getRequestParam('size'));
        $size = $size>0 ?$size:10;
        $offset = ($page-1)*$size;

        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }

        $res = CompanyInv::findByCompanyId($companyId);

        return $this->writeJson(200,
            ['total' => count($res),'page' => $page, 'pageSize' => $size, 'totalPage'=> floor(count($res)/$size)],
            $res, '成功', true, []
        );
    }
    function getInvestorInfoOld(): bool
    {  
        $page = intval($this->request()->getRequestParam('page'));
        $page = $page>0 ?$page:1; 
        $size = intval($this->request()->getRequestParam('size')); 
        $size = $size>0 ?$size:10; 
        $offset = ($page-1)*$size;  
        
        $companyId = intval($this->request()->getRequestParam('xd_id')); 
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }
        
        //优先从工商股东信息取
        $model = \App\HttpController\Models\RDS3\CompanyInvestor::create()
            ->where('company_id', $companyId)->page($page)->withTotalCount();
        // 没有工商股东信息 从企业自发查
        if(!$model){
            $model = \App\HttpController\Models\RDS3\CompanyInvestorEntPub::create()
                ->where('company_id', $companyId)->page($page)->withTotalCount();
        }
        $retData = $model->all();
        $total = $model->lastQueryResult()->getTotalCount(); 
        
        foreach($retData as &$dataItem){
            if(
                $dataItem['investor_type'] == 2 
            ){
                $companyModel = \App\HttpController\Models\RDS3\Company::create()
                    ->where('id', $dataItem['investor_id'])->get();
                $dataItem['name'] = $companyModel->name; 
                if(XinDongService::isJson($dataItem['capital'])){
                    $dataItem['capitalData'] = @json_decode($dataItem['capital'],true);
                }else{
                    $dataItem['capitalData'] = [['amomon'=>$dataItem['capital'],'time'=>'','paymet'=>'']];
                }
                if(XinDongService::isJson($dataItem['capitalActl'])){
                    $dataItem['capitalActlData'] = @json_decode($dataItem['capitalActl'],true);
                }else{
                    $dataItem['capitalActlData'] = [['amomon'=>$dataItem['capitalActl'],'time'=>'','paymet'=>'']];
                } 
                
            }

            if(
                $dataItem['investor_type'] == 1 
            ){
                $humanModel = \App\HttpController\Models\RDS3\Human::create()
                    ->where('id', $dataItem['investor_id'])->get();
                $dataItem['name'] = $humanModel->name;
                if(XinDongService::isJson($dataItem['capital'])){
                    $dataItem['capitalData'] = @json_decode($dataItem['capital'],true);
                }else{
                    $dataItem['capitalData'] = [['amomon'=>$dataItem['capital'],'time'=>'','paymet'=>'']];
                }
                if(XinDongService::isJson($dataItem['capitalActl'])){
                    $dataItem['capitalActlData'] = @json_decode($dataItem['capitalActl'],true);
                }else{
                    $dataItem['capitalActlData'] = [['amomon'=>$dataItem['capitalActl'],'time'=>'','paymet'=>'']];
                }
            } 
        }

        return $this->writeJson(200, ['total' => $total,'page' => $page, 'pageSize' => $size, 'totalPage'=> floor($total/$size)], $retData, '成功', true, []);
    
    }

    /**
      * 
      * 人员信息
        https://api.meirixindong.com/api/v1/xd/getStaffInfo 
      * 
      * 
     */
    function getStaffInfo(): bool
    {
        $page = intval($this->request()->getRequestParam('page'));
        $page = $page>0 ?$page:1;
        $size = intval($this->request()->getRequestParam('size'));
        $size = $size>0 ?$size:10;
        $offset = ($page-1)*$size;

        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }
        $dataRes = CompanyManager::findByConditionV2(
            [
                ['field' => 'companyid', 'value' => $companyId ,'operate'=> '=']
            ],
            $page
        );
        return $this->writeJson(200, [
            'total' => $dataRes['total'],
            'page' => $page,
            'pageSize' => $size,
            'totalPage'=> floor($dataRes['total']/$size)],
            $dataRes['data'], '成功', true, []);

    }
    function getStaffInfoOld(): bool
    {  
        $page = intval($this->request()->getRequestParam('page'));
        $page = $page>0 ?$page:1; 
        $size = intval($this->request()->getRequestParam('size')); 
        $size = $size>0 ?$size:10; 
        $offset = ($page-1)*$size;  
        
        $companyId = intval($this->request()->getRequestParam('xd_id')); 
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }
        
        $model = \App\HttpController\Models\RDS3\CompanyStaff::create()
            ->where('company_id', $companyId)->page($page)->withTotalCount(); 
        $retData = $model->all();
        $total = $model->lastQueryResult()->getTotalCount(); 

        foreach($retData as &$dataItem){
            $humanModel = \App\HttpController\Models\RDS3\Human::create()
                ->where('id', $dataItem['staff_id'])->get();
            $dataItem['name'] = $humanModel->name;
        }
        return $this->writeJson(200, ['total' => $total,'page' => $page, 'pageSize' => $size, 'totalPage'=> floor($total/$size)], $retData, '成功', true, []);
    
    }

    /**
      * 
      * 曾用名
        https://api.meirixindong.com/api/v1/xd/getNamesInfo 
      * 
      * 
     */
    function getNamesInfo(): bool
    {
        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }

        $names = CompanyHistoryName::findByCompanyId($companyId);

        return $this->writeJson(200, [], $names, '成功', true, []);

    }
    function getNamesInfoOld(): bool
    {  
        // $page = intval($this->request()->getRequestParam('page'));
        // $page = $page>0 ?$page:1; 
        // $size = intval($this->request()->getRequestParam('size')); 
        // $size = $size>0 ?$size:10; 
        // $offset = ($page-1)*$size;  
        
        $companyId = intval($this->request()->getRequestParam('xd_id')); 
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }
        
        $model = Company::create()
                    // ->field(['id','name','property2'])
                ->where('id', $companyId)
                ->get(); 
        if(!$model){
            return  $this->writeJson(201, null, null, '数据缺失(企业id)');
        }

        $names = (new XinDongService())::getAllUsedNames(
            [
                'id' => $model->id,
                'name' => $model->name,
                'property2' => $model->property2,
            ]
        );
       
        return $this->writeJson(200, [], $names, '成功', true, []);
    
    } 
    
    // 上传商机
    function uploadBusinessOpportunity(): bool
    {
        $files = $this->request()->getUploadedFiles();
        CommonService::getInstance()->log4PHP(
            '[souKe]-uploadEntList files['.json_encode($files).']'
        );
        $y = Carbon::now()->format('Y');
        $m = Carbon::now()->format('m');

        $path = ROOT_PATH . "/TempWork/SouKe/Work/{$y}{$m}/";
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        foreach ($files as $key => $oneFile) {
            if ($oneFile instanceof UploadFile) {
                try {
                    $fileName = $path .  $this->loginUserinfo['id'] . '_' . $oneFile->getClientFilename();
                    if (!file_exists($fileName)){
                        $oneFile->moveTo($fileName);
                    } else
                    {
                        CommonService::getInstance()->log4PHP(
                            '[souKe]-uploadEntList 文件已存在['.$fileName.']'
                        );
                    }
                } catch (\Throwable $e) {
                    return $this->writeJson(202);
                }
            }
            else{
                CommonService::getInstance()->log4PHP(
                    '[souKe]-uploadEntList 不是实例['.json_encode($oneFile).']'
                );
            }
        }

        return $this->writeJson(200);
    }

    //
    function getEsBasicInfo(): bool
    {
        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }

        $res = (new XinDongService())->getEsBasicInfoV2($companyId);
        return $this->writeJson(200,
            [ ]
            , $res, '成功', true, []);
    }
    function getEsBasicInfoOld(): bool
    {
        $companyId = intval($this->request()->getRequestParam('xd_id')); 
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }

        $res = (new XinDongService())->getEsBasicInfo($companyId); 
    
        return $this->writeJson(200, 
          [ ] 
       , $res, '成功', true, []);
    }

    //
    function getShangPinInfo(): bool
    {
        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }

        $ElasticSearchService = new ElasticSearchService();
        $ElasticSearchService->addMustMatchQuery( 'companyid' , $companyId) ;

        $size = $this->request()->getRequestParam('size')??10;
        $page = $this->request()->getRequestParam('page')??1;
        $offset  =  ($page-1)*$size;
        $ElasticSearchService->addSize(1) ;
        $ElasticSearchService->addFrom(0) ;

        $responseJson = (new XinDongService())->advancedSearch($ElasticSearchService,'company_202208');
        $responseArr = @json_decode($responseJson,true);
        CommonService::getInstance()->log4PHP('advancedSearch-Es '.@json_encode(
                [
                    'es_query' => $ElasticSearchService->query,
                    'post_data' => $this->request()->getRequestParam(),
                ]
            ));

        // 格式化下日期和时间
        $hits = (new XinDongService())::formatEsDate($responseArr['hits']['hits'], [
            'estiblish_time',
            'from_time',
            'to_time',
            'approved_time'
        ]);
        $hits = (new XinDongService())::formatEsMoney($hits, [
            'reg_capital',
        ]);


        foreach($hits as $dataItem){
            $retData = $dataItem['_source']['shang_pin_data'];
            break;
        }


        $total =  count($retData); //total items in array
        $totalPages = ceil( $total/ $size ); //calculate total pages
        $page = max($page, 1); //get 1 page when $_GET['page'] <= 0
        // $page = min($page, $totalPages); //get last page when $_GET['page'] > $totalPages
        $offset = ($page - 1) * $size;
        if( $offset < 0 ) $offset = 0;

        $retData = array_slice( $retData, $offset, $size );


        return $this->writeJson(200,
            [
                'page' => $page,
                'pageSize' =>$size,
                'total' => $total,
                'totalPage' => $totalPages,
            ]
            , $retData, '成功', true, []);
    }

    // 导出
    function getUserBusinessOpportunityExcel()
    {
        $phone = $this->request()->getRequestParam('phone');
        $entNameList = $this->request()->getRequestParam('entNameList') ?? '';
        $entNameList = array_filter(explode(',', $entNameList));

        $config = [
            'path' => TEMP_FILE_PATH // xlsx文件保存路径
        ];

        $excel = new \Vtiful\Kernel\Excel($config);

        $filename = 'souke_'.control::getUuid(8) . '.xlsx';

        $header = [
            '序号',
            '企业名称',
            '监控类别', 
        ];

        try {
            $list = UserBusinessOpportunity::create()
                // ->where('phone', $phone)
                // ->where('entName', $entNameList, 'IN')
                ->limit(2)
                ->all();
            $data = [];
            $i = 1;
            foreach ($list as $one) { 
                
                array_push($data, [
                    $one['name'],
                    $one['code'],
                ]);
                $i++;
            }
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        $fileObject = $excel->fileName($filename, '汇总');
        $fileHandle = $fileObject->getHandle();

        //==========================================================================================================
        $format = new Format($fileHandle);

        $colorStyle = $format
            ->fontColor(Format::COLOR_ORANGE)
            ->border(Format::BORDER_DASH_DOT)
            ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->toResource();

        $format = new Format($fileHandle);

        $alignStyle = $format
            ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->toResource();
        //==========================================================================================================

        $fileObject
            ->defaultFormat($colorStyle)
            ->header($header)
            ->defaultFormat($alignStyle)
            ->data($data)
            ->setColumn('B:B', 50)
        ;

        $format = new Format($fileHandle);
        //单元格有\n解析成换行
        $wrapStyle = $format
            ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->wrap()
            ->toResource(); 

        $res = $fileObject->output();

        return $this->writeJson(200, null, 'Static/Temp/' . $filename, null, true, [$res]);
    }

    // 获取上传列表
    function getUploadOpportunityLists(): bool
    {  
        $page = intval($this->request()->getRequestParam('page'));
        $page = $page>0 ?$page:1; 
        $size = intval($this->request()->getRequestParam('size')); 
        $size = $size>0 ?$size:10; 
        $offset = ($page-1)*$size;   
        
        $model = UserBusinessOpportunityBatch::create()
            ->where('userId', $this->loginUserinfo['id'])->page($page)->withTotalCount(); 
        $retData = $model->all();
        $total = $model->lastQueryResult()->getTotalCount(); 

        // foreach($retData as &$dataItem){
        //     $humanModel = \App\HttpController\Models\RDS3\Human::create()
        //         ->where('id', $dataItem['staff_id'])->get();
        //     $dataItem['name'] = $humanModel->name;
        // }
        return $this->writeJson(200, ['total' => $total,'page' => $page, 'pageSize' => $size, 'totalPage'=> floor($total/$size)], $retData, '成功', true, []);
    
    }

     // 领取商机
     function saveOpportunity(): bool
     {  
        $xdIdsStr = $this->request()->getRequestParam('xd_ids');
        if(!$xdIdsStr){
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }
        $xdIdsArr = explode(',', $xdIdsStr);
        $companyDatas = Company::create()
            ->where('id', $xdIdsArr, 'IN')
            ->all();
        foreach($companyDatas as $companyDataItem){
            XinDongService::saveOpportunity(
                [
                    'userId' => $this->loginUserinfo['id'], 
                    'name' => $companyDataItem['name'],
                    'code' => $companyDataItem['property1'],
                    'batchId' => 0,
                    'source' => UserBusinessOpportunity::$sourceFromSave,
                ]
            );
        }
        
        
         return $this->writeJson(200, [], [], '成功', true, []);
     
     }

     //企业联系方式
    function getEntLianXi(): bool
    {
        $postData = [
            'entName' => $this->getRequestData('entName', ''),
        ]; 

        $retData =  (new LongXinService())
        ->setCheckRespFlag(true)
        ->getEntLianXi($postData);

        $size = $this->request()->getRequestParam('size')??10;
        $page = $this->request()->getRequestParam('page')??1;
        $offset  =  ($page-1)*$size;  
         
        $retData = $retData['result'];
        $total =  count($retData); //total items in array       
        $totalPages = ceil( $total/ $size ); //calculate total pages
        $page = max($page, 1); //get 1 page when $_GET['page'] <= 0
        // $page = min($page, $totalPages); //get last page when $_GET['page'] > $totalPages
        $offset = ($page - 1) * $size;
        if( $offset < 0 ) $offset = 0;

        $retData = array_slice( $retData, $offset, $size ); 
        // CommonService::getInstance()->log4PHP(
        //     'getEntLianXi '.json_encode(
        //         $retData
        //     )
        // );
        $retData = LongXinService::complementEntLianXiMobileState($retData);
        $retData = LongXinService::complementEntLianXiPosition($retData, $postData['entName']); 
        
        return $this->writeJson(200, 
          [
            'page' => $page,
            'pageSize' =>$size,
            'total' => $total,
            'totalPage' => $totalPages, 
        ] 
       , $retData, '成功', true, []); 
    } 

    function matchFuzzyNameByLanguageMode(): bool
    {
        $timeStart = microtime(true);  
        $entName = $this->getRequestData('entName', '');

        $retData =  (new XinDongService())
                    ->matchAainstEntName($entName ); 

        $timeEnd = microtime(true); 
        $execution_time1 = ($timeEnd - $timeStart); 
        CommonService::getInstance()->log4PHP('matchFuzzyNameByLanguageMode Total Execution Time'.$execution_time1.'秒'); 

        return $this->writeJson(200, [ ] , 
            [
                'Time' => 'Total Execution Time:'.$execution_time1.' 秒  |'.$execution_time2. '分',
                'data' => $retData,
            ], '成功', true, []
        ); 
    } 

    function matchFuzzyNameByBooleanMode(): bool
    {
        $timeStart = microtime(true);  
        $entName = $this->getRequestData('entName', '');
        $matchStr = (new XinDongService())->splitChineseNameForMatchAgainst($entName);
        $retData =  (new XinDongService())
                    ->matchAainstEntName($matchStr," IN BOOLEAN MODE " ); 

        $timeEnd = microtime(true); 
        $execution_time1 = ($timeEnd - $timeStart);
        CommonService::getInstance()->log4PHP('matchFuzzyNameByBooleanMode Total Execution Time'.$execution_time1.'秒'); 
        return $this->writeJson(200, [] , 
        [
           'Time' => 'Total Execution Time:'.$execution_time1.' 秒  |',
           'data' => $retData,
       ], '成功', true, []); 
    }
    
    function matchEntByName(): bool
    {
        
        $entName = $this->getRequestData('entName', '');
        $type = $this->getRequestData('type', '1');
        $timeout = $this->getRequestData('timeout', '3');
        if($this->getRequestData('new')){
            $retData = (new XinDongService())->matchEntByName2($entName,$type,$timeout);
        }
        else{
            $retData = (new XinDongService())->matchEntByName($entName,$type,$timeout);
        }

        // CommonService::getInstance()->log4PHP('matchEntByName '.$execution_time1.'秒'); 
        return $this->writeJson(200, [] ,   $retData, '成功', true, []); 
    }

    function matchNames(): bool
    {
        
        $toBeMatch = $this->getRequestData('toBeMatch', '');
        $target = $this->getRequestData('target', ''); 
        $percentage1 = $this->getRequestData('percentage1', '60'); 
        $percentage2 = $this->getRequestData('percentage2', '60'); 
       
        $retData = (new XinDongService())->matchNames($toBeMatch,$target,
        [
            'matchNamesByEqual' => true,
            'matchNamesByContain' => true,
            'matchNamesByToBeContain' => true,
            'matchNamesBySimilarPercentage' => true,
            'matchNamesBySimilarPercentageValue' => $percentage1,
            'matchNamesByPinYinSimilarPercentage' => true,
            'matchNamesByPinYinSimilarPercentageValue' => $percentage2,
        ]);
        $retData2 = (new XinDongService())->matchNamesV2($toBeMatch,$target);

        // CommonService::getInstance()->log4PHP('matchEntByName '.$execution_time1.'秒'); 
        return $this->writeJson(200, [] ,  [ $retData,$retData2], '成功', true, []);
    }

    //添加车险授权书认证书信息
    function addCarInsuranceInfo(){  
        $entId = $this->getRequestData('entId');
        if($entId <= 0){
            return $this->writeJson(206, [] ,   [], '缺少必要参数', true, []); 
        } 

        $files = $this->request()->getUploadedFiles();
        $path = $fileName = '';

        $succeedNums = 0;
        foreach ($files as $key => $oneFile) {
            if (!$oneFile instanceof UploadFile) {
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        'not instanceof UploadFile ',
                    ])
                ); 
                    continue;
            }

            try {
                $fileName = $oneFile->getClientFilename();
                $path = TEMP_FILE_PATH . $fileName;
                $oneFile->moveTo($path); 
                
                $excel_read = new \Vtiful\Kernel\Excel(
                    [
                        'path' => TEMP_FILE_PATH // xlsx文件保存路径
                    ]
                );
                $excel_read->openFile($fileName)->openSheet();
                $excel_read->nextRow([]);

                $data = []; 
                $batchNum = control::getUuid();
                while ($one = $excel_read->nextRow([])) { 
                    $vin = trim($one['0']);
                    $legalPerson = trim($one['1']);
                    $idCard = trim($one['2']);
                    
                    if(
                        !$vin ||
                        !$legalPerson ||
                        !$idCard 
                    ){
                        CommonService::getInstance()->log4PHP(
                            json_encode([
                                'addCarInsuranceInfo 该行缺数据 continue',
                                'entId' => $entId,
                                'vin' => $vin, 
                                'legalPerson' => $legalPerson,
                                'idCard' => $idCard,
                            ])
                        ); 
                        continue;
                    }

                    // 企业车辆信息
                    $carInsuranceInfoId = (new XinDongService())->addCarInsuranceInfo(
                        [
                            'entId' => $entId,
                            'vin' => $vin, 
                            'legalPerson' => $legalPerson,
                            'idCard' => $idCard,
                        ]
                    );
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            'addCarInsuranceInfo carInsuranceInfo ',
                            'entId' => $entId,
                            'vin' => $vin, 
                            'legalPerson' => $legalPerson,
                            'idCard' => $idCard,
                            'carInsuranceInfo' => $carInsuranceInfoId,
                        ])
                    ); 
                    if(!$carInsuranceInfoId){ 
                        CommonService::getInstance()->log4PHP(
                            json_encode([
                                'addCarInsuranceInfo carInsuranceInfo continue',
                                'entId' => $entId,
                                'vin' => $vin, 
                                'legalPerson' => $legalPerson,
                                'idCard' => $idCard,
                                'carInsuranceInfo' => $carInsuranceInfoId,
                            ])
                        ); 
                        continue;
                    }

                    // 用户-车辆关系
                    $userCarsRelationId = (new XinDongService())->addUserCarsRelation(
                        [
                            'user_id' => $this->loginUserinfo['id'],
                            'car_insurance_id' => $carInsuranceInfoId, 
                            'legalPerson' => $legalPerson,
                            'idCard' => $idCard,
                        ]
                    );
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            'addCarInsuranceInfo userCarsRelation ',
                            'user_id' => $this->loginUserinfo['id'],
                            'car_insurance_id' => $carInsuranceInfoId, 
                            'legalPerson' => $legalPerson,
                            'idCard' => $idCard,
                            'userCarsRelation' => $userCarsRelationId,
                        ])
                    ); 
                    if(!$userCarsRelationId){
                        CommonService::getInstance()->log4PHP(
                            json_encode([
                                'addCarInsuranceInfo userCarsRelation continue',
                                'user_id' => $this->loginUserinfo['id'],
                                'car_insurance_id' => $carInsuranceInfoId, 
                                'legalPerson' => $legalPerson,
                                'idCard' => $idCard,
                            ])
                        ); 
                        continue;
                    }
                    $succeedNums ++;
                }                
                
            } catch (\Throwable $e) {
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        'addCarInsuranceInfo Throwable continue',
                        $e->getMessage(),
                    ])
                ); 
                return $this->writeErr($e, __FUNCTION__);
            } 
        } 

        if($succeedNums>0){
            // 企业车险状态
            $companyCarInsuranceStatusInfoId =(new XinDongService())->addCompanyCarInsuranceStatusInfo(
                [
                    'entId' => $entId,
                ]
            );
        }

        return $this->writeJson(200, null, $batchNum,'导入成功 入库数量:'.$succeedNums);
    }

    function getCarsInsurance(){
        $entId             = $this->getRequestData('entId');
        $page             = $this->getRequestData('page');
        $postData         = [
            'entId' => $entId,
            'page' => $page
        ];

        list($paging,$tmp) = (new GuangZhouYinLianService())
            ->setCheckRespFlag(true)
            ->getCarsInsuranceV2($postData);
        CommonService::getInstance()->log4PHP('getCarsInsurance '.json_encode($tmp));

        return $this->writeJson(200, $paging, $tmp, '查询成功');
    }

    function queryVehicleCount(): bool
    {
        $postData = [];
        $csp = new \EasySwoole\Component\Csp(); 
        $csp->add('queryVehicleCount', function () use ($postData) {
            return (new GuangZhouYinLianService())
            ->setCheckRespFlag(true)
            ->queryVehicleCount($postData);
        });  
        $res = ($csp->exec(5)); 
        CommonService::getInstance()->log4PHP('  res'.
            json_encode( $res) 
        ); 

        return $this->writeJson(200, [], $res, '查询成功'); 

    }

    function queryUsedVehicleInfo(): bool
    {
        $userNo           = $this->getRequestData('userNo');
        $licenseNoType    = $this->getRequestData('licenseNoType');
        $vin              = $this->getRequestData('vin');
        $licenseNo        = $this->getRequestData('licenseNo');
        $postData         = [
            'userNo'           => $userNo,
            'vin'              => $vin,
            'licenseNo'        => $licenseNo,
            'licenseNoType'    => $licenseNoType,
        ];
        $csp = new \EasySwoole\Component\Csp(); 
        $csp->add('queryVehicleCount', function () use ($postData) {
            return 
                (new GuangZhouYinLianService())
                ->setCheckRespFlag(true)
                ->queryUsedVehicleInfo($postData);

        });  
        $res = ($csp->exec(5));  

        return $this->writeJson(200, [], $res, '查询成功');
    }

    function queryInancialBank(): bool
    {
        $name             = $this->getRequestData('name');
        $userNo           = $this->getRequestData('userNo');
        $certType         = $this->getRequestData('certType');
        $certNo           = $this->getRequestData('certNo');
        $vin              = $this->getRequestData('vin');
        $licenseNo        = $this->getRequestData('licenseNo');
        $bizFunc          = $this->getRequestData('bizFunc');
        $firstBeneficiary = $this->getRequestData('firstBeneficiary');
        $areaNo           = $this->getRequestData('areaNo'); 


        $postData         = [
            'name'             => $name,
            'userNo'           => $userNo,
            'certType'         => $certType,
            'certNo'           => $certNo,
            'vin'              => $vin,
            'licenseNo'        => $licenseNo,
            'areaNo'           => $areaNo,
            'firstBeneficiary' => $firstBeneficiary,
            'bizFunc'          => $bizFunc
        ];
        
        $csp = new \EasySwoole\Component\Csp(); 
        $csp->add('queryVehicleCount', function () use ($postData) {
            return (new GuangZhouYinLianService())
                ->setCheckRespFlag(true)
                ->queryInancialBank($postData);
        });  
 
        $res = ($csp->exec(5)); 
        return $this->writeJson(200, [], $res, '查询成功');
    }

    function getYieldData(){
        $data = [];
        for($i=1; $i<=10 ; $i++){
            yield $data[] = [
               '福建裕兴果蔬食品开发有限公司',
               '福建裕兴果蔬食品开发有限公司', 
            ];
        }
    }

// Comparison function
    function date_compare($element1, $element2) {
        $datetime1 = strtotime($element1['datetime']);
        $datetime2 = strtotime($element2['datetime']);
        return $datetime1 - $datetime2;
    }

    function testExport()
    {
        if(
            $this->getRequestData('export_wechat')
        ){

            $fileName = date('YmdHis').'_'.'export_wechat.csv';
            $f = fopen(TEMP_FILE_PATH.$fileName, "w");
            fwrite($f,chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($f, [
                '企业名',
                '税号',
                '手机号',
                '微信',
                '姓名',
                '职位',
                '微信匹配方式',
                '微信匹配详情',
                '微信匹配得分',
            ]);

            $Sql = " select *  from     `wechat_info`  WHERE `code` LIKE  '9144%'  limit 2000  " ;
            $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3'));
            foreach ($data as $dataItem){
                if($dataItem['code']){
                    $companyRes = CompanyBasic::findByCode($dataItem['code']);
                    $companyRes = $companyRes?$companyRes->toArray():[];
                }
                //$dataItem['phone_md5'];
                //$dataItem['phone'];
                $phone_res = \wanghanwanghan\someUtils\control::aesDecode($dataItem['phone'], $dataItem['created_at']);
                $tmpRes = (new XinDongService())->matchContactNameByWeiXinNameV2($companyRes['ENTNAME'],$dataItem['nickname']);

                fputcsv($f, [
                    $companyRes['ENTNAME'],
                    $dataItem['code'],
                    $phone_res,
                    $dataItem['nick_name'],
                    $tmpRes['data']['stff_name'],
                    $tmpRes['data']['staff_type_name'],
                    $tmpRes['match_res']['type'],
                    $tmpRes['match_res']['details'],
                    $tmpRes['match_res']['percentage'],
                ]);
            }
            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
                $fileName,
                '成功',
                true,
                []
            );
        }

        if(
            $this->getRequestData('jieba')
        ){

//            $jieba0 = jieba($this->getRequestData('jieba'), 0);
//            $jieba1 = jieba($this->getRequestData('jieba'), 1);
//            $jieba2 = jieba($this->getRequestData('jieba'), 2);
            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
               [
                   CompanyBasic::findBriefName($this->getRequestData('jieba')),
                   jieba($this->getRequestData('jieba'), 0),
                   jieba($this->getRequestData('jieba'), 1),
               ],
                '成功',
                true,
                []
            );
        }

        if(
            $this->getRequestData('code_region')
        ){

            $Sql = "SELECT * FROM code_region  WHERE `code` = '110000' " ;
            $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_hd_saic'));
            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
                $data,
                '成功',
                true,
                []
            );
        }

        if(
            $this->getRequestData('generateNewFile')
        ){
            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
                RunDealBussinessOpportunity::generateNewFile(),
                '成功',
                true,
                []
            );
        }


        //将微信信息入库
        if(
            $this->getRequestData('addWeChatInfo')
        ){
            return $this->writeJson(
                200,[] ,
                RunDealBussinessOpportunity::addWeChatInfo(),
                '成功',
                true,
                []
            );
        }

        if(
            $this->getRequestData('delEmptyMobile')
        ){

            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
                RunDealBussinessOpportunity::delEmptyMobile(),
                '成功',
                true,
                []
            );
        }

        if(
            $this->getRequestData('withoutOverlappingV2')
        ){

            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
                CrontabBase::withoutOverlappingV2($this->getRequestData('withoutOverlappingV2')),
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('removeOverlappingKeyV2')
        ){
            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
                CrontabBase::removeOverlappingKeyV2($this->getRequestData('removeOverlappingKeyV2')),
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('testNewSms')
        ){
            SmsService::getInstance()->sendByTemplete(
                $this->getRequestData('testNewSms'), 'SMS_249280572',[]);

            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
                [],
                '成功',
                true,
                []
            );
        }


        // 测试添加sheet
        if(
            $this->getRequestData('addNewSheet2')
        ){
           RunDealBussinessOpportunity::splitByMobile();
            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
               [],
                '成功',
                true,
                []
            );
        }


        if(
            $this->getRequestData('resetMatchRes')
        ){
            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
                RunDealCarInsuranceInstallment::resetMatchRes($this->getRequestData('resetMatchRes')),
                '成功',
                true,
                []
            );
        }

        if(
            $this->getRequestData('runMatchXXX')
        ){
            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
               RunDealCarInsuranceInstallment::runMatch(),
                '成功',
                true,
                []
            );
        }

        if(
            $this->getRequestData('online_goods_user')
        ){
            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
                OnlineGoodsUser::findBySql(""),
                '成功',
                true,
                []
            );
        }

        if(
            $this->getRequestData('insurance_datas')
        ){
            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
                InsuranceData::findBySql(" ORDER BY id  desc  limit 5"),
                '成功',
                true,
                []
            );
        }
            if(
            $this->getRequestData('getFinanceIncomeStatement')
        ) {
            $res = (new GuoPiaoService())->setCheckRespFlag(true)->getFinanceIncomeStatement(
                $this->getRequestData('getFinanceIncomeStatement')
            );
            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
                $res,
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('getFinanceBalanceSheet')
        ) {
            $res = (new GuoPiaoService())->setCheckRespFlag(true)->getFinanceBalanceSheet(
                $this->getRequestData('getFinanceBalanceSheet')
            );
            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
                $res,
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('getFinanceBalanceSheetAnnual')
        ){
            $res = (new GuoPiaoService())->setCheckRespFlag(true)->getFinanceBalanceSheetAnnual(
                $this->getRequestData('getFinanceBalanceSheetAnnual')
            );
            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
                $res,
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('yhzwsc')
        ){
            $res = (new ChuangLanService())->yhzwsc($this->getRequestData('yhzwsc'));
            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
                $res,
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('getEssential')
        ) {
            $res = (new GuoPiaoService())->getEssential($this->getRequestData('getEssential'));
            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
                $res,
                '成功',
                true,
                []
            );
        }

        if(
            $this->getRequestData('getQuarterTaxInfo')
        ){
            $res = (new CarInsuranceInstallment())
                    ->getQuarterTaxInfo($this->getRequestData('getQuarterTaxInfo'));
            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
               // [$res,$length],
                $res,
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('getVatReturn')
        ){
            $res = (new GuoPiaoService())->getVatReturn(
                $this->getRequestData('getVatReturn')
            );
            $data = jsonDecode($res['data']);
            $returnArr = [];
            foreach ($data as $dataItem){
                if(in_array($dataItem['columnSequence'],[34,39,40,41]) ){
                    $retrunData['所得税'][] =  $dataItem;
                }
            }
            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
                $retrunData,
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('runMatchSuNing')
        ){

            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
                CarInsuranceInstallment::runMatchSuNing( $this->getRequestData('runMatchSuNing')),
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('runMatchPuFa')
        ){

            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
                CarInsuranceInstallment::runMatchPuFa( $this->getRequestData('runMatchPuFa')),
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('runMatchJinCheng')
        ){

            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
                CarInsuranceInstallment::runMatchJinCheng( $this->getRequestData('runMatchJinCheng')),
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('getIncometaxMonthlyDeclaration')
        ){
            $res = (new GuoPiaoService())->getIncometaxMonthlyDeclaration(
                $this->getRequestData('getIncometaxMonthlyDeclaration')
            );
            $data = jsonDecode($res['data']);
            $tmpData =[];
            foreach ($data as $dataItem){
                if($dataItem['columnSequence'] == 16){
                    $tmpData[] = $dataItem;
                }
            }
            // Sort the array
            usort($tmpData, 'beginDate');

            //最长连续
            $lastDate = '';
            $i = 1;
            $length = 1;
            foreach ($tmpData as $tmpDataItem) {

                $beginDate = date('Y-m-d', strtotime($tmpDataItem['beginDate']));
                // 第一次
                if ($i == 1) {
                    $lastDate = $beginDate;
                    CommonService::getInstance()->log4PHP(json_encode(
                        [
                            __CLASS__ ,
                            'times'=>$i,
                            'item date'=>$beginDate,
                            'last cal date'=>$lastDate,
                        ]
                    ));
                    $i ++;
                    continue;
                }

                $nextDate = date("Y-m-d", strtotime("-3 months", strtotime($lastDate)));
                CommonService::getInstance()->log4PHP(json_encode(
                    [
                        __CLASS__ ,
                        'times'=>$i,
                        'item date'=>$beginDate,
                        'last cal date'=>$lastDate,
                        'next cal date'=>$nextDate,
                    ]
                ));
                //如果连续了
                if (
                    $beginDate == $nextDate
                ) {
                    //连续长度加1
                    $length++;
                    CommonService::getInstance()->log4PHP(json_encode(
                        [
                            __CLASS__ ,
                            'times'=>$i,
                            'item date'=>$beginDate,
                            'last cal date'=>$lastDate,
                            'next cal date'=>$nextDate,
                            'ok'=>1,
                            '$length'=>$length,
                        ]
                    ));
                } else {
                    $length = 1;
                    CommonService::getInstance()->log4PHP(json_encode(
                        [
                            __CLASS__ ,
                            'times'=>$i,
                            'item date'=>$beginDate,
                            'last cal date'=>$lastDate,
                            'next cal date'=>$nextDate,
                            'ok'=>0,
                            '$length'=>$length,
                        ]
                    ));
                }

                //重置上次连续时间
                $lastDate = $beginDate;
                $i++;
            }

            return $this->writeJson(
                200,[] ,
                //CommonService::ClearHtml($res['body']),
                $length,
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('getAuthentication1')
        ){


            $callback = $this->getRequestData('callback', 'https://pc.meirixindong.com/');

            $orderNo = control::getUuid(20);

            $res = (new GuoPiaoService())->getAuthentication($this->getRequestData('getAuthentication1'), $callback, $orderNo);

            $res = jsonDecode($res);
            return $this->writeJson(
                200,[  ] ,
                //CommonService::ClearHtml($res['body']),
                $res,
                '成功',
                true,
                []
            );
        }
        //
        if(
            $this->getRequestData('addRecordV3')
        ){
            $res = NewFinanceData::addRecordV3(
                [
                    'entName'=>'测试一下',
                    'year'=>2000,
                    'VENDINC'=>null,
                    'C_ASSGROL'=>'',
                    'A_ASSGROL'=>'0',
                    'CA_ASSGRO'=>0,
                ]
            );
            return $this->writeJson(
                200,[  ] ,
                //CommonService::ClearHtml($res['body']),
                $res,
                '成功',
                true,
                []
            );
        }
            if(
            $this->getRequestData('gteLists22')
        ) {
            $res =  InsuranceDataHuiZhong::gteLists(
                [
                    ['field'=>'user_id','value'=>11,'operate'=>'=']
                ],1
            );

            return $this->writeJson(
                200,[
                'page' => 1,
                'pageSize' => 10,
                'total' => $res['total'],
                'totalPage' => ceil($res['total']/10) ,
            ] ,
                //CommonService::ClearHtml($res['body']),
                $res['data'],
                '成功',
                true,
                []
            );
        }

        if(
            $this->getRequestData('getBaoYaProducts')
        ) {
            $allProducts = (new \App\HttpController\Service\BaoYa\BaoYaService())->getProducts();

            return $this->writeJson(
                200,[ ] ,$allProducts,
                '成功',
                true,
                []
            );
        }


        if(
            $this->getRequestData('testSheet')
        ) {
            $res = InsuranceData::getDataLists(
                [
                    ['field'=>'user_id','value'=>1,'operate'=>'=']
                ],
                1
            );

            return $this->writeJson(
                200,[ ] ,
                $res,
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('testReadMultiSheet')
        ){
            $excel = new \Vtiful\Kernel\Excel(['path' =>  TEMP_FILE_PATH]);
            // 打开示例文件
            $sheetList = $excel->openFile('测试多sheet.xlsx')
                ->sheetList();
            $datas = [];
            foreach ($sheetList as $sheetName) {
                // 通过工作表名称获取工作表数据
                $excel = $excel
                    ->openSheet($sheetName);// ->getSheetData();

                $tmpRes = RunDealBussinessOpportunity::getYieldDataBySheet($excel);
                foreach ($tmpRes as $tmp){
                    $datas[$sheetName][] = $tmp;
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            __CLASS__.__FUNCTION__ .__LINE__,
                            '$sheetName' => $sheetName,
                            '$tmp' => $tmp,
                        ])
                    );
                }
            }
            return $this->writeJson(
                200,[ ] ,
                $sheetName,
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('fixNewFinancdeData')
        ){

            $all = NewFinanceData::findBySql(" WHERE VENDINC = '' 
	AND ASSGRO = '' 
	AND MAIBUSINC = '' 
	AND TOTEQU = '' 
	AND RATGRO = '' 
	AND PROGRO = '' 
	AND NETINC = '' 
	AND LIAGRO = '' 
	AND SOCNUM = 0 
	AND EMPNUM = 0  
	AND `year` <> 2022
	ORDER BY id  desc  ");
            $nums = 0;
            foreach ($all as $item){
                if(
                    (
                        $item['ASSGRO'] === '' ||
                        $item['ASSGRO'] === NULL
                    ) &&
                    (
                        $item['MAIBUSINC'] === '' ||
                        $item['MAIBUSINC'] === NULL
                    ) &&
                    (
                        $item['TOTEQU'] === '' ||
                        $item['TOTEQU'] === NULL
                    ) &&
                    (
                        $item['RATGRO'] === '' ||
                        $item['RATGRO'] === NULL
                    ) &&
                    (
                        $item['PROGRO'] === '' ||
                        $item['PROGRO'] === NULL
                    ) &&
                    (
                        $item['NETINC'] === '' ||
                        $item['NETINC'] === NULL
                    ) &&
                    (
                        $item['LIAGRO'] === '' ||
                        $item['LIAGRO'] === NULL
                    ) &&
                    (
                        $item['SOCNUM'] === '0' ||
                        $item['SOCNUM'] === 0
                    ) &&
                    (
                        $item['EMPNUM'] === '0' ||
                        $item['EMPNUM'] === 0
                    )
                ){
                    NewFinanceData::changeById(
                        $item['id'],
                        [
                            'SOCNUM' =>'',
                            'EMPNUM' =>'',
                        ]
                    );
//                    return $this->writeJson(
//                        200,[ ] ,
//                        $item['id'],
//                        '成功',
//                        true,
//                        []
//                    );
                    $nums  ++;
                }
            }
            return $this->writeJson(
                200,[ ] ,
                $nums,
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('sRemNeedCheck')
        ){

            $sdd1 = ConfigInfo::sRem($this->getRequestData('sRemNeedCheck'),'online_needs_login');
            return $this->writeJson(
                200,[ ] ,
                $sdd1,
                '成功',
                true,
                []
            );
        }

        if(
            $this->getRequestData('sMembers')
        ){

            $sdd1 = ConfigInfo::sMembers('online_needs_login');
            return $this->writeJson(
                200,[ ] ,
                $sdd1,
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('sAddNeedCheck')
        ){

            $sdd1 = ConfigInfo::sAdd($this->getRequestData('sAddNeedCheck'),'online_needs_login');
            return $this->writeJson(
                200,[ ] ,
                $sdd1,
                '成功',
                true,
                []
            );
        }

        if(
            $this->getRequestData('addRedisSet')
        ){

            $set1 = ConfigInfo::sMembers('online_needs_login');

            $sdd1 = ConfigInfo::sAdd('consultProduct','online_needs_login');
            $set2 =  ConfigInfo::sMembers('online_needs_login');
            $exists1 = ConfigInfo::Sismember('login','online_needs_login');
            $exists2 = ConfigInfo::Sismember('consultProduct','online_needs_login');
            return $this->writeJson(
                200,[ ] ,
                 [
                     $set1,
                     $sdd1,
                     $set2,
                     $exists1,
                     $exists2
                 ],
                '成功',
                true,
                []
            );
        }

        if(
            $this->getRequestData('collectInvoice4')
        ){
            //
            $code = '911101143355687304';
            return $this->writeJson(
                200,[ ] ,
                XinDongService::exportInvoiceV2($code),
                '成功',
                true,
                []
            );
        }

        if(
            $this->getRequestData('collectInvoice3')
        ){
            //
            $code = '911101143355687304';
            return $this->writeJson(
                200,[ ] ,
                XinDongService::exportInvoice($code),
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('collectInvoice2')
        )
        {
            $code = '911101143355687304';
            $res = XinDongService::pullInvoice($code);

            return $this->writeJson(
                200,[ ] ,
                $res,
                '成功',
                true,
                []
            );
        }

        if(
            $this->getRequestData('collectInvoice')
        )
        {
            $date ="2022-07";
            $monthsNums = 24;
            $code = '911101143355687304';
            XinDongService::collectInvoice($date,$monthsNums,$code);

            return $this->writeJson(
                200,[ ] ,
                [

                ],
                '成功',
                true,
                []
            );
        }

        if(
            $this->getRequestData('sendXunJiaEmail')
        )
        {
            RunDealEmailReceiver::sendEmail();
            return $this->writeJson(
                200,[ ] ,
                [

                ],
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('sendEmailHuiZhong')
        )
        {
            RunDealEmailReceiver::sendEmailHuiZhong();
            return $this->writeJson(
                200,[ ] ,
                [

                ],
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('testToken')
        )
        {
            $params = $this->request()->getRequestParam();
            $token  = CommonService::generateTokenByParam($params);
            return $this->writeJson(
                200,[ ] ,
                [
                    '$params'=>$params,
                    '$token' =>$token
                ],
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('testMenu')
        ){
            //营业状态
            $Sql = "SET @pv = 'A'" ;
            $data = sqlRawV2($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_hd_saic'));
            $Sql = "select id,`code`,name,parent,`level` from code_ca16
                    where FIND_IN_SET(parent,@pv) and !isnull(@pv:= concat(@pv, ',', code));" ;
            $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_hd_saic'));
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    '$Sql' => $Sql,
                    '$data '=> $data
                ])
            );
            return $this->writeJson(
                200,[ ] ,
                $data,
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('baoya2')
        ){
//            new BaoYaService();
            return $this->writeJson(
                200,[ ] ,(new \App\HttpController\Service\BaoYa\BaoYaService())->getProductDetail($this->getRequestData('baoya2')),
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('baoya1')
        ){

            return $this->writeJson(
                200,[ ] ,(new \App\HttpController\Service\BaoYa\BaoYaService())->getProducts(),
                '成功',
                true,
                []
            );
        }

        if(
            $this->getRequestData('testEmailReceiver')
        ){
            RunDealEmailReceiver::pullEmail(1);
            RunDealEmailReceiver::dealMail($this->getRequestData('testEmailReceiver'));
            return $this->writeJson(
                200,[ ] ,[],
                '成功',
                true,
                []
            );
        }
        if(
            $this->getRequestData('testTransaction')
        ){

//            \App\HttpController\Models\AdminV2\AdminNewUser::updateMoney2(100,1);
//


            try {

                DbManager::getInstance()->startTransaction('mrxd');
                \App\HttpController\Models\AdminV2\AdminNewUser::updateMoney(
                    1,
                    \App\HttpController\Models\AdminV2\AdminNewUser::aesEncode(
                        \App\HttpController\Models\AdminV2\AdminNewUser::getAccountBalance(
                            1
                        ) - 10
                    )
                );

                //\App\HttpController\Models\AdminV2\AdminNewUser::updateMoney2(100,1);

                OperatorLog::addRecord(
                    [
                        'user_id' => 1,
                        'msg' => "测试扣费10元",
                        'details' =>json_encode( XinDongService::trace()),
                        'type_cname' => '测试扣费',
                    ]
                );

                DbManager::getInstance()->commit('mrxd');

            }catch (\Throwable $e) {
                DbManager::getInstance()->rollback('mrxd');
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ .__LINE__,
                        '$e getMessage' => $e->getMessage()
                    ])
                );
            }

            return $this->writeJson(
                200,
                [ ] ,[] ,
                '成功',
                true,
                []
            );
        }

        if(
            $this->getRequestData('getAllBySiji')
        ){

            $allSijiFenLeis = RunDealApiSouKe::testYield(
                $this->getRequestData('getAllBySiji')
            );

            return $this->writeJson(
                200,
                [ ] ,$allSijiFenLeis ,
                '成功',
                true,
                []
            );
        }

        if(
            $this->getRequestData('CompanyLiquidation')
        ){

            return $this->writeJson(
                200,
                [ ] ,
                CompanyLiquidation::findByName($this->getRequestData('CompanyLiquidation')),
                '成功',
                true,
                []
            );
        }

        if(
            $this->getRequestData('findCancelDateByCode')
        ){

            return $this->writeJson(
                200,
                [ ] ,
                CompanyBasic::findCancelDateByCode($this->getRequestData('findCancelDateByCode')),
                '成功',
                true,
                []
            );
        }


        //失信被执行人
        if(
            $this->getRequestData('C1')
        ){

            $postData = [
                'entName' => trim($this->getRequestData('C1')),
                'version' => 'C1' ,
            ];


            $res = (new LongXinService())
                ->setCheckRespFlag(true)
                ->getEntDetail($postData);

            return $this->writeJson(200, [ ] ,  $res, '成功', true, []);

        }

        if(
            $this->getRequestData('C10')
        ){

            $postData = [
                'entName' => trim($this->getRequestData('C10')),
                'version' => 'C10' ,
            ];


            $res = (new LongXinService())
                ->setCheckRespFlag(true)
                ->getEntDetail($postData);

            return $this->writeJson(200, [ ] ,  $res, '成功', true, []);

        }

        if(
            $this->getRequestData('getBankruptcyCheck')
        ){

            return $this->writeJson(200, [ ] ,  [
                (new XinDongService())->getBankruptcyCheck(
                    $this->getRequestData('getBankruptcyCheck')
                )
            ], '成功', true, []);

        }
        if(
            $this->getRequestData('getBankruptcyTs')
        ){

            return $this->writeJson(200, [ ] ,  [
                (new XinDongService())->getBankruptcyTs(
                    $this->getRequestData('getBankruptcyTs')
                )
            ], '成功', true, []);

        }

        if(
            $this->getRequestData('generateFileV2')
        ){

            return $this->writeJson(200, [ ] ,  [
                RunDealZhaoTouBiao::sendEmail(
                    $this->getRequestData('generateFileV2')
                )
            ], '成功', true, []);

        }

        if(
            $this->getRequestData('generateFile')
        ){
            $config = [
                'path' => TEMP_FILE_PATH // xlsx文件保存路径
            ];
            $filename = 'XXXX'.date('YmdHis').'.xlsx';
            $excel = new \Vtiful\Kernel\Excel($config);

            $exportData = [['XXXXX','XXXXXXXXXXXX','XXXXXXXXXXX']];
            //=======================================================
            $fileObject = $excel->fileName($filename, '1');
            $fileHandle = $fileObject->getHandle();

            $format = new Format($fileHandle);
            $colorStyle = $format
                ->fontColor(Format::COLOR_ORANGE)
                ->border(Format::BORDER_DASH_DOT)
                ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
                ->toResource();

            $format = new Format($fileHandle);

            $alignStyle = $format
                ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
                ->toResource();

            $file =  $fileObject
                ->defaultFormat($colorStyle)
                ->header(['XXX','XXXXX'])
                ->defaultFormat($alignStyle)
                ->data($exportData)
                // ->setColumn('B:B', 50)
            ;

            //==============================================
            $file->addSheet('sheet_two')
                ->defaultFormat($colorStyle)
                ->header(['name', 'age'])
                ->defaultFormat($alignStyle)
                ->data([
                    ['james', 33],
                    ['king', 33]
                ]);

            //==============================================
            $format = new Format($fileHandle);
            //单元格有\n解析成换行
            $wrapStyle = $format
                ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
                ->wrap()
                ->toResource();

            $file->output();

            return $this->writeJson(200, [ ] ,  [$filename], '成功', true, []);
        }


        if(
            $this->getRequestData('sendEmail')
        ){

            //ZhaoTouBiaoAll::findBySql(" WHERE ");

            return $this->writeJson(200, [ ] ,  CommonService::getInstance()->sendEmailV2(
                'tianyongshan@meirixindong.com',
                '测试下发送邮件',
                '<h1>xxx</h1>',
                [TEMP_FILE_PATH . '搜客导出_20220707155131.xlsx']
                //'01',
                //['entName' => '测试公司']
            ), '成功', true, []);
        }


        if(
            $this->getRequestData('CompanyBasic')
        ){
             $res = CompanyBasic::findById(16);
            $res = $res->toArray();
            return $this->writeJson(200, [ ] ,$res, '成功', true, []);
        }

        if(
            $this->getRequestData('getMarjetShare')
        ){
            XinDongService::getMarjetShare($this->getRequestData('getMarjetShare'));
            return $this->writeJson(200, [ ] ,XinDongService::getMarjetShare($this->getRequestData('getMarjetShare')), '成功', true, []);
        }

        if(
            $this->getRequestData('lastSql')
        ){

            $model = AdminUserFinanceData::create()
                ->where(['id' => 22])
                ->page(1,2)
                ->order('id', 'DESC') ;
            $res = $model->all();
            return $this->writeJson(200, null, $model->builder->getLastPrepareQuery(), null, true, []);
        }
        if(
            $this->getRequestData('encode')
        ){

            return $this->writeJson(200, null, AdminNewUser::aesEncode(
                $this->getRequestData('encode')
            ), null, true, []);

        }

        if(
            $this->getRequestData('decode')
        ){
            return $this->writeJson(200, null, AdminNewUser::aesDecode($this->getRequestData('decode')), null, true, []);

        }


        if(
            $this->getRequestData('ToolsGenerateFile')
        ){
            RunDealToolsFile::generateFile(1);
        }
        //
        if(
            $this->getRequestData('generateFileCsv')
        ){
            RunDealApiSouKe::generateFileCsvV2(1);
        }


        if(
            $this->getRequestData('generateFileExcelV2')
        ){
            RunDealApiSouKe::generateFileExcelV2(1);
        }


        //确认交付
        if(
            $this->getRequestData('deliver')
        ){
            RunDealApiSouKe::deliver(1);
        }

        if(
            $this->getRequestData('parseDataToDb')
        ){
            RunDealFinanceCompanyDataNewV2::parseCompanyDataToDb(1);
        }
        if(
            $this->getRequestData('sendSmsWhenBalanceIsNotEnough')
        ){
            RunDealFinanceCompanyDataNewV2::sendSmsWhenBalanceIsNotEnough(1);
        }
        if(
            $this->getRequestData('calculatePrice')
        ){
            RunDealFinanceCompanyDataNewV2::calcluteFinancePrice(1);
        }

        if(
            $this->getRequestData('pullFinanceDataV2')
        ){
            RunDealFinanceCompanyDataNewV2::pullFinanceDataV2(1);
        }

        if(
            $this->getRequestData('pullFinanceDataV3')
        ){
            RunDealFinanceCompanyDataNewV2::pullFinanceDataV3(1);
        }

        if(
            $this->getRequestData('checkConfirmV2')
        ){
            RunDealFinanceCompanyDataNewV2::checkConfirmV2(1);
        }

        if(
            $this->getRequestData('exportFinanceDataV4')
        ){
            RunDealFinanceCompanyDataNewV2::exportFinanceDataV4(1);
        }
        if(
            $this->getRequestData('getFinanceDataXX')
        ){
            $res = (new LongXinService())->getFinanceData([
                        "entName"=>$this->getRequestData('getFinanceDataXX'),
                        "code"=> "",
                        'beginYear' => date('Y'),
                        'dataCount' => 10,//取最近几年的
            ], false);
            return $this->writeJson(200, null, $res, null, true, []);
        }


        if(
            $this->getRequestData('matchByName')
        ){
            $datas = XinDongService::fuzzyMatchEntName($this->getRequestData('matchByName'));

            return $this->writeJson(200, null, $datas, null, true, []);
        }

        return $this->writeJson(200, null, [], null, true, []);
    }


    //股东关系图
    function getCompanyInvestor(): bool
    {
        //
        $requestData =  $this->getRequestData();
        $res = CompanyInv::findByCompanyId(
            $requestData['company_id']
        );
        foreach ($res as &$data){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'getCompanyInvestor_data_item'=>$data
                ])
            );
        }
        return $this->writeJson(200, null, $res, '成功', false, []);

    }
    function getCompanyInvestorOld(): bool
    {
        //
        $requestData =  $this->getRequestData();
        CommonService::getInstance()->log4PHP(
            json_encode([
                'getCompanyInvestor $requestData '=>$requestData
            ])
        );

        $res = CompanyInvestor::findByCompanyId(
            $requestData['company_id']
        );
        foreach ($res as &$data){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'getCompanyInvestor_data_item'=>$data
                ])
            );
            $name = CompanyInvestor::getInvestorName( $data['investor_id'], $data['investor_type']);
            $data['name'] = $name;
        }

        return $this->writeJson(200, null, $res, '成功', false, []);
    }

    //市场占有率查询
    function calMarketShare(): bool
    {
        $requestData =  $this->getRequestData();
        $checkRes = DataModelExample::checkField(
            [

                'xd_id' => [
                    'bigger_than' => 0,
                    'field_name' => 'xd_id',
                    'err_msg' => '参数错误',
                ]
            ],
            $requestData
        );
        if(
            !$checkRes['res']
        ){
            return $this->writeJson(203,[ ] , [], $checkRes['msgs'], true, []);
        }

        //XinDongService::getMarjetShare($requestData['xd_id']);
        return $this->writeJson(200, [ ] ,XinDongService::getMarjetShare($requestData['xd_id']), '成功', true, []);
    }



}
