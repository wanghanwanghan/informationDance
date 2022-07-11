<?php

namespace App\HttpController\Business\Api\XinDong;

use App\Crontab\CrontabList\RunDealApiSouKe;
use App\Crontab\CrontabList\RunDealFinanceCompanyData;
use App\Crontab\CrontabList\RunDealFinanceCompanyDataNew;
use App\Crontab\CrontabList\RunDealFinanceCompanyDataNewV2;
use App\Crontab\CrontabList\RunDealToolsFile;
use App\Csp\Service\CspService;
use App\HttpController\Models\AdminV2\AdminNewUser;
use App\HttpController\Models\AdminV2\AdminUserFinanceData;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\AdminV2\NewFinanceData;
use App\HttpController\Models\AdminV2\ToolsUploadQueue;
use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\Api\User;
use App\HttpController\Models\RDS3\CompanyInvestor;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\Export\Excel\ExportExcelService;
use App\HttpController\Service\LongDun\LongDunService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Pay\ChargeService;
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

    function advancedSearchSetQueryByBasicSzjjid($es){
        // 数字经济及其核心产业 050101,050102 需要转换为四级分类 然后再搜索
        $szjjidsStr = trim($this->request()->getRequestParam('basic_szjjid'));
        $szjjidsStr && $szjjidsArr = explode(',', $szjjidsStr);
        if($szjjidsArr){
            $szjjidsStr = implode("','", $szjjidsArr); 
            $sql = "SELECT
                        nic_id 
                    FROM
                        nic_code
                    WHERE
                    nssc IN (
                        SELECT
                            nssc_id 
                        FROM
                            `szjj_nic_code` 
                        WHERE
                        szjj_id IN ( '$szjjidsStr' ) 
                    )
            ";

            $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_nic_code'));
            $nicIds = array_column($list, 'nic_id');
            
            CommonService::getInstance()->log4PHP($sql);
            CommonService::getInstance()->log4PHP($list);
            CommonService::getInstance()->log4PHP($nicIds); 

            if(!empty($nicIds)){
                foreach($nicIds as &$nicId){
                    if(
                        strlen($nicId) == 5 &&
                        substr($nicId, -1) == '0'
                    ){
                        $nicId = substr($nicId, 0, -1);
                    }
                } 
                CommonService::getInstance()->log4PHP($nicIds);
                $es->addMustShouldPhrasePrefixQuery( 'si_ji_fen_lei_code' , $nicIds) ; 
            } 
        }  

        return $this;
    }

    function advancedSearchSetQueryBySearchText($es){
        $searchText = trim($this->request()->getRequestParam('searchText'));
        if($searchText){
            $matchedCnames = [
                [ 'field'=>'name' ,'value'=> $searchText],
                [ 'field'=>'shang_pin_data.name' ,'value'=> $searchText],
                [ 'field'=>'basic_opscope' ,'value'=> $searchText] 
            ];
            $es->addMustShouldPhraseQueryV2($matchedCnames) ;  
        }
        return $this;
    }   

    function advancedSearchSetQueryByBusinessScope($es){
        // 需要按文本搜索的  
        $addMustMatchPhraseQueryMap = [ 
            // basic_opscope: 经营范围
            'business_scope' =>trim($this->request()->getRequestParam('basic_opscope')),
        ];
        foreach($addMustMatchPhraseQueryMap as $field=>$value){
            $value && $es->addMustMatchPhraseQuery( $field , $value) ; 
        } 
        return $this;
    }   


    function advancedSearchSetQueryByBasicJlxxcyid($es){
        // 需要按文本搜索的  
        $basicJlxxcyidStr = trim($this->request()->getRequestParam('basic_jlxxcyid'));
        $basicJlxxcyidStr && $basicJlxxcyidArr = explode(',',  $basicJlxxcyidStr);
        //CommonService::getInstance()->log4PHP('basicJlxxcyidArr '.json_encode($basicJlxxcyidArr));
        if(
            !empty($basicJlxxcyidArr)
        ){
            $siJiFenLeiDatas = \App\HttpController\Models\RDS3\ZlxxcyNicCode::create()
                ->where('zlxxcy_id', $basicJlxxcyidArr, 'IN') 
                ->all();
            $matchedCnames = array_column($siJiFenLeiDatas, 'nic_id');
            CommonService::getInstance()->log4PHP('matchedCnames '.json_encode($matchedCnames)); 

            $es->addMustShouldPhraseQuery( 'si_ji_fen_lei_code' , $matchedCnames) ; 
    
        }
        return $this;
    } 
    
    function advancedSearchSetQueryByShangPinData($es){
         // 搜索shang_pin_data 商品信息 appStr:五香;农庄
         $appStr =   trim($this->request()->getRequestParam('appStr')); 
         $appStr && $appStrDatas = explode(';', $appStr);
         !empty($appStrDatas) && $es->addMustShouldPhraseQuery( 'shang_pin_data.name' , $appStrDatas) ;
     
        return $this;
    } 

    function advancedSearchSetQueryByWeb($es,$searchOptionArr){
       
        $web_values = []; //官网 
        foreach($searchOptionArr as $item){  
            if($item['pid'] == 70){ 
                $web_values = $item['value']; 
            } 
        }

        //必须存在官网 
        foreach($web_values as $value){
            if($value){
                // $ElasticSearchService->addMustExistsQuery( 'web') ; 
                $es->addMustRegexpQuery( 'web', ".+") ; 
                
                break;
            }
        }
       return $this;
   } 

   function advancedSearchSetQueryByApp($es,$searchOptionArr){ 
       $app_values = []; // 
       foreach($searchOptionArr as $item){  
           if($item['pid'] == 80){ 
               $app_values = $item['value']; 
           }
       }

         //必须存在APP 
        foreach($app_values as $value){
            if($value){ 
                $es->addMustRegexpQuery( 'app', ".+") ;  
                break;
            }
        }
        return $this;
    } 

    function advancedSearchSetQueryByWuLiuQiYe($es,$searchOptionArr){ 
        $app_values = []; // 
        foreach($searchOptionArr as $item){  
            if($item['pid'] == 90){ 
                $app_values = $item['value']; 
            }
        }
 
          //必须存在APP 
         foreach($app_values as $value){
             if($value){ 
                 $es->addMustRegexpQuery( 'wu_liu_qi_ye', ".+") ;  
                 break;
             }
         }
         return $this;
     } 

    function advancedSearchSetQueryByCompanyOrgType($es,$searchOptionArr){  
        $org_type_values = [];  // 企业类型   
        foreach($searchOptionArr as $item){ 
            if($item['pid'] == 10){
                $org_type_values = $item['value'];  
            } 
        }
 
        $matchedCnames = [];
        foreach($org_type_values as $orgType){
            $orgType && $matchedCnames[] = (new XinDongService())->getCompanyOrgType()[$orgType]; 
        }
        (!empty($matchedCnames)) && $es->addMustShouldPhraseQuery( 'company_org_type' , $matchedCnames) ;
    
         return $this;
     } 
     function advancedSearchSetQueryByEstiblishTime($es,$searchOptionArr){   
       $estiblish_time_values = [];  // 成立年限   
       foreach($searchOptionArr as $item){  
           if($item['pid'] == 20){ 
               $estiblish_time_values = $item['value']; 
           }
   
       }
       $matchedCnames = [];
       $map = [
           // 2年以内
           2 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -2 year')), 'max' => date('Y-m-d')  ],
           // 2-5年
           5 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -5 year')), 'max' => date('Y-m-d', strtotime(date('Y-m-01') . ' -2 year'))  ],
           // 5-10年
           10 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -10 year')), 'max' => date('Y-m-d', strtotime(date('Y-m-01') . ' -5 year'))  ],
           // 10-15年
           15 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -15 year')), 'max' => date('Y-m-d', strtotime(date('Y-m-01') . ' -10 year'))  ],
           // 15-20年
           20 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -20 year')), 'max' => date('Y-m-d', strtotime(date('Y-m-01') . ' -15 year'))  ],
           // 20年以上
           25 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -100 year')), 'max' => date('Y-m-d', strtotime(date('Y-m-01') . ' -20 year'))  ],
       ];
       foreach($estiblish_time_values as $item){
           $item && $matchedCnames[] = $map[$item]; 
       } 
       (!empty($matchedCnames)) && $es->addMustShouldRangeQuery( 'estiblish_time' , $matchedCnames) ; 
   
         return $this;
     } 

     function advancedSearchSetQueryByRegStatus($es,$searchOptionArr){    
        $reg_status_values = [];// 营业状态  
        foreach($searchOptionArr as $item){   
            if($item['pid'] == 30){
                $reg_status_values = $item['value']; 
            } 
        } 

        $matchedCnames = [];
        foreach($reg_status_values as $item){
            $item && $matchedCnames[] = (new XinDongService())->getRegStatus()[$item]; 
        }
        (!empty($matchedCnames)) && $es->addMustShouldPhraseQuery( 'reg_status' , $matchedCnames) ; 
    
    
          return $this;
      } 

      function advancedSearchSetQueryByRegCaptial($es,$searchOptionArr){    
       
       $reg_capital_values = [];  // 注册资本
       
       foreach($searchOptionArr as $item){ 
          
           if($item['pid'] == 40){ 
               $reg_capital_values = $item['value']; 
           }
  
       }
       $map = XinDongService::getZhuCeZiBenMap();
       foreach($reg_capital_values as $item){
           $tmp = $map[$item]['epreg']; 
           foreach($tmp as $tmp_item){
               $matchedCnames[] = $tmp_item;
           } 
       } 
       (!empty($matchedCnames)) && $es->addMustShouldRegexpQuery( 
           'reg_capital' , $matchedCnames
       ) ;

          return $this;
      } 
      function advancedSearchSetQueryByTuanDuiRenShu($es,$searchOptionArr){    
      
       $tuan_dui_ren_shu_values = [];  // 团队人数
      
       foreach($searchOptionArr as $item){ 
          
           if($item['pid'] == 60){ 
               $tuan_dui_ren_shu_values = $item['value']; 
           } 
       }
       $map =  (new XinDongService())::getTuanDuiGuiMoMap();
       $matchedCnames = [];
       
       foreach($tuan_dui_ren_shu_values as $item){
           $tmp = $map[$item]['epreg']; 
           foreach($tmp as $tmp_item){
               $matchedCnames[] = $tmp_item;
           } 
       } 
       (!empty($matchedCnames)) && $es->addMustShouldRegexpQuery( 
           'tuan_dui_ren_shu' , $matchedCnames
       ) ;
 
           return $this;
       } 
       function advancedSearchSetQueryByYingShouGuiMo($es,$searchOptionArr){    
      
            $ying_shou_gui_mo_values = [];  // 营收规模

            foreach($searchOptionArr as $item){   
                if($item['pid'] == 50){ 
                    $ying_shou_gui_mo_values = $item['value']; 
                } 
            }

            $map = [ 
                5 => ['A1'], //微型
                10 => ['A2'], //小型C类
                15 => ['A3'],// 小型B类
                20 => ['A4'],// 小型A类
                25 => ['A5'],// 中型C类
                30 => ['A6'],// 中型B类
                40 => ['A7'],// 中型A类
                45 => ['A8'],// 大型C类
                50 => ['A9'],//大型B类 
                60 => ['A10'],//大型A类，一般指规模在10亿以上，50亿以下 
                65 => ['A11'],//'特大型C类，一般指规模在50亿以上，100亿以下'
                70 => ['A12'],//'特大型C类，一般指规模在50亿以上，100亿以下'
                80 => ['A13'],//'特大型C类，一般指规模在50亿以上，100亿以下' 
            ];

            $matchedCnamesRaw = [];
            foreach($ying_shou_gui_mo_values as $item){
                $item && $matchedCnamesRaw[] = $map[$item]; 
            }
            $matchedCnames = [];
            foreach($matchedCnamesRaw as $items){
                foreach($items as $item){
                    $matchedCnames[] = $item;
                }
            }

            (!empty($matchedCnames)) && $es->addMustShouldPhraseQuery( 'ying_shou_gui_mo' , $matchedCnames) ;  
 
            return $this;
        } 

        function advancedSearchSetQueryBySiJiFenLei($es){     
            $siJiFenLeiStrs = trim($this->request()->getRequestParam('basic_nicid'));
            $siJiFenLeiStrs && $siJiFenLeiArr = explode(',', $siJiFenLeiStrs); 
            if(!empty($siJiFenLeiArr)){
                $es->addMustShouldPhraseQuery( 'si_ji_fen_lei_code' , $siJiFenLeiArr) ;   
            }

            return $this;
        }  
        function advancedSearchSetQueryByBasicRegionid($es){     
            $basiRegionidStr = trim($this->request()->getRequestParam('basic_regionid')); 
            $basiRegionidStr && $basiRegionidArr = explode(',',$basiRegionidStr);
            if(!empty($basiRegionidArr)){ 
                $es->addMustShouldPrefixQuery( 'reg_number' , $basiRegionidArr) ;  
            }

            return $this;
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
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    '$areas_arr' => $areas_arr,
                ])
            );

            //区域多边形搜索：要闭合：即最后一个点要和最后一个点重合
            $first = array_shift($areas_arr);
            $last = array_pop($areas_arr);
            if(
                $first[0]!=$last[0] ||
                $first[1]!=$last[1]
            ){
                $areas_arr[] = $first;
            }
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    '$areas_arr2' => $areas_arr,
                ])
            );
        }
        $companyEsModel
            //经营范围
            ->SetQueryByBusinessScope(trim($this->request()->getRequestParam('basic_opscope')))
            ->SetAreaQuery($areas_arr,$requestData['areas_type']?:1)
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
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    '$areas_arr' => $areas_arr,
                ])
            );

            //区域多边形搜索：要闭合：即最后一个点要和最后一个点重合
            $first = array_shift($areas_arr);
            $last = array_pop($areas_arr);
            if(
                $first[0]!=$last[0] ||
                $first[1]!=$last[1]
            ){
                $areas_arr[] = $first;
            }
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    '$areas_arr2' => $areas_arr,
                ])
            );
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
            ->SetAreaQuery($areas_arr,$requestData['areas_type']?:1)
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
                    foreach ($configs['pid']['data'] as $subKey => $item){
                        if($item['cname'] == $dataItem['_source']['company_org_type']){
                            $newOptions[$key]['data'][$subKey] = $item;
                            break;
                        };
                    };
                }
                //营业状态
                if($configs['pid'] == 30){
                    foreach ($configs['pid']['data'] as $subKey => $item){
                        if($item['cname'] == $dataItem['_source']['reg_status']){
                            $newOptions[$key]['data'][$subKey] = $item;
                            break;
                        };
                    };
                }
                //官网
                if($configs['pid'] == 70){
                    foreach ($configs['pid']['data'] as $subKey => $item){
                        if($item['cname'] == $has_web){
                            $newOptions[$key]['data'][$subKey] = $item;
                            break;
                        };
                    };
                }

                //有无APP
                if($configs['pid'] == 80){
                    foreach ($configs['pid']['data'] as $subKey => $item){
                        if($item['cname'] == $has_app){
                            $newOptions[$key]['data'][$subKey] = $item;
                            break;
                        };
                    };
                }
                //是否物流企业
                if($configs['pid'] == 90){
                    foreach ($configs['pid']['data'] as $subKey => $item){
                        if($item['cname'] == $has_wu_liu_xin_xi){
                            $newOptions[$key]['data'][$subKey] = $item;
                            break;
                        };
                    };
                }

                //成立年限
                if($configs['pid'] == 20){
                    foreach ($configs['pid']['data'] as $subKey => $item){
                        if(
                            $dataItem['_source']['estiblish_year_nums'] >= $item['min'] &&
                            $dataItem['_source']['estiblish_year_nums'] <  $item['max']
                        ){
                            $newOptions[$key]['data'][$subKey] = $item;
                            break;
                        }
                    };
                }

                //注册资本
                if($configs['pid'] == 40){
                    foreach ($configs['pid']['data'] as $subKey => $item){
                        if(
                            $dataItem['_source']['reg_capital'] >= $item['min'] &&
                            $dataItem['_source']['reg_capital'] <  $item['max']
                        ){
                            $newOptions[$key]['data'][$subKey] = $item;
                            break;
                        }
                    };
                }
                //营收规模
                if($configs['pid'] == 50){
                    foreach ($configs['pid']['data'] as $subKey => $item){
                        if(
                            $dataItem['_source']['ying_shou_gui_mo'] >= $item['min'] &&
                            $dataItem['_source']['ying_shou_gui_mo'] <  $item['max']
                        ){
                            $newOptions[$key]['data'][$subKey] = $item;
                            break;
                        }
                    };
                }
                //企业规模
                if($configs['pid'] == 60){
                    foreach ($configs['pid']['data'] as $subKey => $item){
                        if(
                            $dataItem['_source']['tuan_dui_ren_shu'] >= $item['min'] &&
                            $dataItem['_source']['tuan_dui_ren_shu'] <  $item['max']
                        ){
                            $newOptions[$key]['data'][$subKey] = $item;
                            break;
                        }
                    };
                }
            }
        }

        return $this->writeJson(200,
            [  ]
            , $newOptions, '成功', true, []);
    }

//    function advancedSearchBak(): bool
//    {
//        $ElasticSearchService = new ElasticSearchService();
//        $this->advancedSearchSetQueryByBusinessScope($ElasticSearchService);
//
//        // 数字经济及其核心产业
//        $this->advancedSearchSetQueryByBasicSzjjid($ElasticSearchService);
//
//        // 搜索文案 智能搜索
//        $this->advancedSearchSetQueryBySearchText($ElasticSearchService);
//
//        // 搜索战略新兴产业
//        $this->advancedSearchSetQueryByBasicJlxxcyid($ElasticSearchService);
//
//        // 搜索shang_pin_data 商品信息 appStr:五香;农庄
//        $this->advancedSearchSetQueryByShangPinData($ElasticSearchService);
//
//        //传过来的searchOption 例子 [{"type":20,"value":["5","10","2"]},{"type":30,"value":["15","5"]}]
//        $searchOptionStr =  trim($this->request()->getRequestParam('searchOption'));
//        $searchOptionArr = json_decode($searchOptionStr, true);
//
//        //必须存在官网
//        $this->advancedSearchSetQueryByWeb($ElasticSearchService,$searchOptionArr);
//
//        //必须存在APP
//        $this->advancedSearchSetQueryByApp($ElasticSearchService,$searchOptionArr);
//
//        //必须是物流企业
//        $this->advancedSearchSetQueryByWuLiuQiYe($ElasticSearchService,$searchOptionArr);
//
//        // 企业类型 :传过来的是10 20 转换成对应文案 然后再去搜索
//        $this->advancedSearchSetQueryByCompanyOrgType($ElasticSearchService,$searchOptionArr);
//
//        // 成立年限  ：传过来的是 10  20 30 转换成最小值最大值范围后 再去搜索
//        $this->advancedSearchSetQueryByEstiblishTime($ElasticSearchService,$searchOptionArr);
//
//        // 营业状态   传过来的是 10  20  转换成文案后 去匹配
//        $this->advancedSearchSetQueryByRegStatus($ElasticSearchService,$searchOptionArr);
//
//        // 注册资本 传过来的是 10 20 转换成最大最小范围后 再去搜索
//        $this->advancedSearchSetQueryByRegCaptial($ElasticSearchService,$searchOptionArr);
//
//        // 团队人数 传过来的是 10 20 转换成最大最小范围后 再去搜索
//        $this->advancedSearchSetQueryByTuanDuiRenShu($ElasticSearchService,$searchOptionArr);
//
//        // 营收规模  传过来的是 10 20 转换成对应文案后再去匹配
//        $this->advancedSearchSetQueryByYingShouGuiMo($ElasticSearchService,$searchOptionArr);
//
//        //四级分类 basic_nicid: A0111,A0112,A0113,
//        $this->advancedSearchSetQueryBySiJiFenLei($ElasticSearchService);
//
//        // 地区 basic_regionid: 110101,110102,
//        $this->advancedSearchSetQueryByBasicRegionid($ElasticSearchService);
//
//        $size = $this->request()->getRequestParam('size')??10;
//        $page = $this->request()->getRequestParam('page')??1;
//        $offset  =  ($page-1)*$size;
//        $ElasticSearchService->addSize($size) ;
//        $ElasticSearchService->addFrom($offset) ;
//        // $ElasticSearchService->addSort('xd_id', 'desc') ;
//
//        //设置默认值 不传任何条件 搜全部
//        $ElasticSearchService->setDefault() ;
//
//        $responseJson = (new XinDongService())->advancedSearch($ElasticSearchService);
//        $responseArr = @json_decode($responseJson,true);
//        CommonService::getInstance()->log4PHP('advancedSearch-Es '.@json_encode(
//                [
//                    // 'hits' => $responseArr['hits']['hits'],
//                    'es_query' => $ElasticSearchService->query,
//                    'post_data' => $this->request()->getRequestParam(),
//                ]
//            ));
//
//        // 格式化下日期和时间
//        $hits = (new XinDongService())::formatEsDate($responseArr['hits']['hits'], [
//            'estiblish_time',
//            'from_time',
//            'to_time',
//            'approved_time'
//        ]);
//        $hits = (new XinDongService())::formatEsMoney($hits, [
//            'reg_capital',
//        ]);
//
//        foreach($hits as &$dataItem){
//            $addresAndEmailData = (new XinDongService())->getLastPostalAddressAndEmail($dataItem);
//            $dataItem['_source']['last_postal_address'] = $addresAndEmailData['last_postal_address'];
//            $dataItem['_source']['last_email'] = $addresAndEmailData['last_email'];
//
//            $dataItem['_source']['logo'] =  (new XinDongService())->getLogoByEntId($dataItem['_source']['xd_id']);
//
//            // 添加tag
//            $dataItem['_source']['tags'] = array_values(
//                (new XinDongService())::getAllTagesByData(
//                    $dataItem['_source']
//                )
//            );
//
//            // 官网
//            $webStr = trim($dataItem['_source']['web']);
//            if(!$webStr){
//                continue;
//            }
//            $webArr = explode('&&&', $webStr);
//            !empty($webArr) && $dataItem['_source']['web'] = end($webArr);
//        }
//
//        return $this->writeJson(200,
//            [
//                'page' => $page,
//                'pageSize' =>$size,
//                'total' => intval($responseArr['hits']['total']['value']),
//                'totalPage' => (int)floor(intval($responseArr['hits']['total']['value'])/
//                    ($size)),
//
//            ]
//            , $hits, '成功', true, []);
//    }

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
        
        $ElasticSearchService->addMustMatchQuery( 'xd_id' , $companyId) ;  

        $size = $this->request()->getRequestParam('size')??10;
        $page = $this->request()->getRequestParam('page')??1;
        $offset  =  ($page-1)*$size;
        $ElasticSearchService->addSize(1) ;
        $ElasticSearchService->addFrom(0) ; 

        $responseJson = (new XinDongService())->advancedSearch($ElasticSearchService);
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
    function testExport()
    {
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
                        "entName"=>"乌海市源来煤业有限公司",
                        "code"=> "",
                        "beginYear"=> 2019,
                        "dataCount"=> 1
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
