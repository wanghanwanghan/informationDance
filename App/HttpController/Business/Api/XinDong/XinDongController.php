<?php

namespace App\HttpController\Business\Api\XinDong;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\Api\User;
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

        return $this->writeJson(200, null, $searchOptionArr, '成功', true, []);
    }

    
     /**
      * 
      * 高级搜索 (旧的)
        https://api.meirixindong.com/api/v1/xd/advancedSearch 
      * 
      * 
     */
    function advancedSearch3(): bool
    { 
         $postData = $this->formatRequestData(
            $this->request()->getRequestParam(),
            [
                // key => 默认值值
                'name' => '' ,
                'company_org_type' => '' ,
                'estiblish_year_nums' => '' ,
                'reg_status' => '' ,
                'reg_capital' => '' ,
                'ying_shou_gui_mo' => '' , 
                'business_scope' => '' ,  
                'property1' => '' , 
                'si_ji_fen_lei_code' => '' ,
                'gao_xin_ji_shu' => '' ,
                'deng_ling_qi_ye' => '' ,
                'tuan_dui_ren_shu' => '' ,
                'tong_xun_di_zhi' => '' ,
                'web' => '' ,
                'yi_ban_ren' => '' ,
                'shang_shi_xin_xi' => '' ,
                'app' => '' ,
                'shang_pin_data' => '' ,
                'page' => 1,
                'size' => 10,
            ]
        );  
        $elasticSearchService =  (new XinDongService())->setEsSearchQuery($postData,(new ElasticSearchService())); 
       
        $responseJson = (new XinDongService())->advancedSearch($elasticSearchService);
        $responseArr = @json_decode($responseJson,true);
         
        if(
            !(new XinDongService())->saveSearchHistory(
                $this->loginUserinfo['id'],
                json_encode($elasticSearchService->query),
                ''
            )
        ){
            return $this->writeJson(201, null, null, '记录搜索历史失败！请联系管理员');
        };
        return $this->writeJson(200, 
          [
            'page' => $postData['page']??1,
            'pageSize' =>$postData['size']??20,
            'total' => intval($responseArr['hits']['total']),
            'totalPage' => (int)floor(intval($responseArr['hits']['total'])/($postData['size']??20)),
        ] 
       , $responseArr['hits']['hits'], '成功', true, []);
    }

    /**
      * 
      * 高级搜索 |着急上线 先直接写的query  有时间 需要用es service的集成方法
        https://api.meirixindong.com/api/v1/xd/advancedSearch2 
      * 
      * 
     */
    function advancedSearch(): bool
    { 
        $queryArr = [];
        
        //名称  name  全名匹配 
        $name = trim($this->request()->getRequestParam('searchText'));
        if ($name) {
            $queryArr['query']['bool']['must'][] = [
                'match_phrase' => [
                    'name' => $name,
                ]
            ];
        }

        // basic_opscope: 经营范围
        $basic_opscope = trim($this->request()->getRequestParam('basic_opscope')); 
        if($basic_opscope){
            $queryArr['query']['bool']['must'][] = [
                'match_phrase' => [
                    'business_scope' => $basic_opscope,
                ]
            ];
        }


        // [{"type":20,"value":["5","10","2"]},{"type":30,"value":["15","5"]}]
        $searchOptionStr =  trim($this->request()->getRequestParam('searchOption'));
        $searchOptionArr = json_decode($searchOptionStr, true); 
        foreach($searchOptionArr as $item){
            // 企业类型  
            if($item['pid'] == 10){
                $boolQuery = []; 
                foreach((new XinDongService())->getCompanyOrgType() as $type=>$cname){
                    if(in_array($type, $item['value'])){
                        $boolQuery['bool']['should'][] = 
                        ['match_phrase' => ['company_org_type' => $cname]]; 
                    } ;
                } 
                $queryArr['query']['bool']['must'][] = $boolQuery;
            }

            // 成立年限  
            if($item['pid'] == 20){
                $boolQuery = []; 
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
                ];
                foreach($map  as $type=>$subItem){
                    if(in_array($type, $item['value'])){
                        $boolQuery['bool']['should'][] = 
                            ['range' => ['estiblish_time' => ['lte' => $subItem['max'],'gte' => $subItem['min'] ]]];                        
                    } ;
                } 
                $queryArr['query']['bool']['must'][] = $boolQuery;
            }

            // 营业状态  
            if($item['pid'] == 30){
                $boolQuery = []; 
                foreach((new XinDongService())->getRegStatus() as $type=>$cname){
                    if(in_array($type, $item['value'])){
                        $boolQuery['bool']['should'][] = 
                        ['match_phrase' => ['reg_status' => $cname]]; 
                    } ;
                } 
                $queryArr['query']['bool']['must'][] = $boolQuery;
            }

            // 注册资本
            if($item['pid'] == 40){
                $boolQuery = [];  
                $map = [ 
                    // 100万以下
                    10 =>  ['min'=>0, 'max' => 100  ], 
                    // 100-500万
                    15 =>  ['min'=>100, 'max' => 500  ],
                    // 500-1000万
                    20 =>  ['min'=>500, 'max' => 1000  ],
                    // 1000-5000万
                    25 =>  ['min'=>1000, 'max' => 5000  ],
                    // '5000万-1亿'
                    30 =>  ['min'=>5000, 'max' => 10000  ],
                    35 =>  ['min'=>10000, 'max' => 100000  ],
                    40 =>  ['min'=>100000, 'max' => 10000000  ],
                ];
                foreach($map  as $type=>$subItem){
                    if(in_array($type, $item['value'])){
                        $boolQuery['bool']['should'][] = 
                            ['range' => ['reg_capital' => ['lte' => $subItem['max'],'gte' => $subItem['min'] ]]];
                        
                    } ;
                } 
                $queryArr['query']['bool']['must'][] = $boolQuery;
            }

            // 营收规模 
            if($item['pid'] == 50){
                $boolQuery = []; 
                $map = [
                    5 => ['A1','A2'], //微型
                    10 => ['A3','A4'], //小型C类
                    15 => ['A5'],// 小型B类
                    20 => ['A6','A7'],// 小型A类
                    25 => ['A8','A9'],// 中型C类
                    30 => ['A10','A11','A12'],// 中型B类
                    40 => ['A13','A14'],// 中型A类
                    45 => ['A15','A16','A17','A18'],// 大型C类
                    50 => ['A19','A20','A21','A22','A23'],//大型B类 
                ];
                foreach($map as $type=>$subItem){
                    if(in_array($type, $item['value'])){
                        foreach($subItem as $subValue){
                            $boolQuery['bool']['should'][] = 
                            ['match_phrase' => ['ying_shou_gui_mo' => $subValue]]; 
                        } 
                    } ;
                } 
                $queryArr['query']['bool']['must'][] = $boolQuery;
            }
        }
        
        //四级分类 basic_nicid: A0111,A0112,A0113,
        $siJiFenLeiStrs = trim($this->request()->getRequestParam('basic_nicid'));
        $siJiFenLeiStrs && $siJiFenLeiArr = explode(',', $siJiFenLeiStrs); 
        if(!empty($siJiFenLeiArr)){
            $boolQuery = [];
            foreach($siJiFenLeiArr as $item){
                $boolQuery['bool']['should'][] = 
                ['match_phrase' => ['si_ji_fen_lei_code' => $item]];
            }
            $queryArr['query']['bool']['must'][] = $boolQuery;
        }

        // 地区 basic_regionid: 110101,110102,
        $basiRegionidStr = trim($this->request()->getRequestParam('basic_regionid')); 
        $basiRegionidStr && $basiRegionidArr = explode(',',$basiRegionidStr);
        if(!empty($basiRegionidArr)){ 
            $boolQuery = [];
            foreach($basiRegionidArr as $item){
                $boolQuery['bool']['should'][] = 
                ['prefix' => ['reg_number' => $item]];
            }
            $queryArr['query']['bool']['must'][] = $boolQuery;
        }

        if(empty($queryArr)){
            $size = $this->request()->getRequestParam('size')??10;
            $page = $this->request()->getRequestParam('page')??1;
            $offset  =  ($page-1)*$size;
            $queryArr = '{"size":"'.($size).'","from":'.$offset.',"query":{"bool":{"must":[{"match_all":{}}]}}}';
        }

        UserSearchHistory::create()->data([
            'userId' => $this->loginUserinfo['id'],
            'query' => is_array($queryArr)?json_encode($queryArr):$queryArr,
            'query_cname' =>json_encode($this->request()->getRequestParam()),
        ])->save(); 

        $elasticsearch = new ElasticSearch(
            new  Config([
                'host' => "es-cn-7mz2m3tqe000cxkfn.public.elasticsearch.aliyuncs.com",
                'port' => 9200,
                'username'=>'elastic',
                'password'=>'zbxlbj@2018*()',
            ])
        ); 
        $bean = new  Search();
        $bean->setIndex('company_287_all');
        $bean->setType('_doc');
        $bean->setBody($queryArr);
        $response = $elasticsearch->client()->search($bean)->getBody(); 
        CommonService::getInstance()->log4PHP(json_encode(['re-query'=>$queryArr]), 'info', 'souke.log');
        CommonService::getInstance()->log4PHP(json_encode(['re-response'=>$response]), 'info', 'souke.log');
        
        $responseArr = @json_decode($response,true); 
       
        return $this->writeJson(200, 
          [
            'page' => $page,
            'pageSize' =>$size,
            'total' => intval($responseArr['hits']['total']['value']),
            'totalPage' => (int)floor(intval($responseArr['hits']['total']['value'])/
            ($size)),
         
        ] 
       , $responseArr['hits']['hits'], '成功', true, []);
    }

    // 新版（尚未启用|）
    function advancedSearch2(): bool
    { 
        $ElasticSearchService = new ElasticSearchService(); 
        
        // 需要按文本搜索的  
        $addMustMatchPhraseQueryMap = [
            // 名称  name  全名匹配 
            'name' =>trim($this->request()->getRequestParam('searchText')),
            // basic_opscope: 经营范围
            'basic_opscope' =>trim($this->request()->getRequestParam('basic_opscope')),
        ];
        foreach($addMustMatchPhraseQueryMap as $field=>$value){
            $ElasticSearchService->addMustMatchPhraseQuery( $field , $value) ; 
        } 

        //传过来的searchOption 例子 [{"type":20,"value":["5","10","2"]},{"type":30,"value":["15","5"]}]
        $searchOptionStr =  trim($this->request()->getRequestParam('searchOption', ''));
        $searchOptionArr = json_decode($searchOptionStr, true);
        
        // 把具体需要搜索的各项摘出来
        $org_type_values = [];  // 企业类型  
        $estiblish_time_values = [];  // 成立年限  
        $reg_status_values = [];// 营业状态 
        $reg_capital_values = [];  // 注册资本
        $ying_shou_gui_mo_values = [];  // 营收规模
        foreach($searchOptionArr as $item){ 
            if($item['pid'] == 10){
                $org_type_values = explode(',',$item['value']);  
            }
 
            if($item['pid'] == 20){ 
                $estiblish_time_values = explode(',',$item['value']); 
            }
   
            if($item['pid'] == 30){
                $reg_status_values = explode(',',$item['value']); 
            }
 
            if($item['pid'] == 40){ 
                $reg_capital_values = explode(',',$item['value']);  
            }
  
            if($item['pid'] == 50){ 
                $ying_shou_gui_mo_values = explode(',',$item['value']);
            }
        }

        // 企业类型 :传过来的是10 20 转换成对应文案 然后再去搜索  
        $matchedCnames = [];
        foreach($org_type_values as $orgType){
            $matchedCnames[] = (new XinDongService())->getCompanyOrgType()[$orgType]; 
        }
        $ElasticSearchService->addMustShouldPhraseQuery( 'company_org_type' , $matchedCnames) ;
    
        // 成立年限  ：传过来的是 10  20 30 转换成最小值最大值范围后 再去搜索
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
        ];
        foreach($estiblish_time_values as $item){
            $matchedCnames[] = $map[$item]; 
        } 
        $ElasticSearchService->addMustShouldRangeQuery( 'estiblish_time' , $matchedCnames) ; 
    
        // 营业状态   传过来的是 10  20  转换成文案后 去匹配  
        $matchedCnames = [];
        foreach($reg_status_values as $item){
            $matchedCnames[] = (new XinDongService())->getRegStatus()[$item]; 
        }
        $ElasticSearchService->addMustShouldPhraseQuery( 'reg_status' , $matchedCnames) ; 
    
        // 注册资本 传过来的是 10 20 转换成最大最小范围后 再去搜索
        $map = [
            // 50万以下 
            5 => ['min'=>0, 'max' => 50  ],
            // 50-100万
            10 =>  ['min'=>50, 'max' => 100  ], 
            // 100-200万
            20 =>  ['min'=>100, 'max' => 200  ],
            // 200-500万
            30 =>  ['min'=>200, 'max' => 500  ],
            // 500-1000万
            40 =>  ['min'=>500, 'max' => 1000  ],
            // 1000-1亿
            50 =>  ['min'=>1000, 'max' => 10000  ],
        ];
        $matchedCnames = [];
        foreach($reg_capital_values as $item){
            $matchedCnames[] = $map[$item]; 
        } 
        $ElasticSearchService->addMustShouldRangeQuery( 'reg_capital' , $matchedCnames) ;  
         
        // 营收规模  传过来的是 10 20 转换成对应文案后再去匹配
        if($item['pid'] == 50){ 
            $map = [
                5 => ['A1','A2'], //微型
                10 => ['A3','A4'], //小型C类
                15 => ['A5'],// 小型B类
                20 => ['A6','A7'],// 小型A类
                25 => ['A8','A9'],// 中型C类
                30 => ['A10','A11','A12'],// 中型B类
                40 => ['A13','A14'],// 中型A类
                45 => ['A15','A16','A17','A18'],// 大型C类
                50 => ['A19','A20','A21','A22','A23'],//大型B类 
            ];
 
            $matchedCnames = [];
            foreach($ying_shou_gui_mo_values as $item){
                $matchedCnames[] = $map[$item]; 
            }
            $ElasticSearchService->addMustShouldPhraseQuery( 'ying_shou_gui_mo' , $matchedCnames) ;  
        }
         
        
        //四级分类 basic_nicid: A0111,A0112,A0113,
        $siJiFenLeiStrs = trim($this->request()->getRequestParam('basic_nicid', ''));
        $siJiFenLeiStrs && $siJiFenLeiArr = explode(',', $siJiFenLeiStrs); 
        if(!empty($siJiFenLeiArr)){
            $ElasticSearchService->addMustShouldPhraseQuery( 'si_ji_fen_lei_code' , $siJiFenLeiArr) ;   
        }

        // 地区 basic_regionid: 110101,110102,
        $basiRegionidStr = trim($this->request()->getRequestParam('basic_regionid', '')); 
        $basiRegionidStr && $basiRegionidArr = explode(',',$basiRegionidStr);
        if(!empty($basiRegionidArr)){ 
            $ElasticSearchService->addMustShouldPrefixQuery( 'si_ji_fen_lei_code' , $siJiFenLeiArr) ;  
        }

        $size = $this->request()->getRequestParam('size')??10;
        $page = $this->request()->getRequestParam('page')??1;
        $offset  =  ($page-1)*$size;
        $ElasticSearchService->addSize($size) ;
        $ElasticSearchService->addFrom($offset) ;

        //设置默认值 不传任何条件 搜全部
        $ElasticSearchService->setDefault() ;  
       
        $responseJson = (new XinDongService())->advancedSearch($ElasticSearchService);
        $responseArr = @json_decode($responseJson,true); 
        
        // 记录搜索历史
        (new XinDongService())->saveSearchHistory(
            $this->loginUserinfo['id'], 
            is_array($ElasticSearchService->query)?json_encode($ElasticSearchService->query):$ElasticSearchService->query, 
            json_encode($this->request()->getRequestParam())
        );

        return $this->writeJson(200, 
          [
            'page' => $page,
            'pageSize' =>$size,
            'total' => intval($responseArr['hits']['total']['value']),
            'totalPage' => (int)floor(intval($responseArr['hits']['total']['value'])/
            ($size)),
         
        ] 
       , $responseArr['hits']['hits'], '成功', true, []);
    }

    function formatRequestData($requestDataArr, $config){
        $return = [];
        foreach($config as $key => $defaultValue){
            $return[$key] = $requestDataArr[$key] ?? $defaultValue; 
        }
        return $return;
    }

    /**
      * 
      * 高级搜索 
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
        
        $retData  =\App\HttpController\Models\RDS3\Company::create()->where('id', $companyId)->get();
        
        return $this->writeJson(200, 0, $retData, '成功', true, []);
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
        $postData = [
            'entName' => trim($this->getRequestData('entName')),
            'page' => $this->getRequestData('page', 1),
            'pageSize' => 10,
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
        $postData = [
            'entName' => $this->getRequestData('entName'),
            'page' => $this->getRequestData('page', 1),
            'pageSize' => 10,
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
        $page = $page>0 ?:1; 
        $size = intval($this->request()->getRequestParam('size')); 
        $size = $size>0 ?:10; 
        $offset = ($page-1)*$size;  

        $companyId = intval($this->request()->getRequestParam('xd_id')); 
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }

        //数据的总记录条数
        $total = \App\HttpController\Models\RDS3\XdHighTec::create()->where('xd_id', $companyId)->count(); 

        $retData  =\App\HttpController\Models\RDS3\XdHighTec::create()
        ->where('xd_id', $companyId)
        ->limit($offset, $size)
        ->all();
        
        return $this->writeJson(200,
         ['total' => $total,'page' => $page, 'pageSize' => $size, 'totalPage'=> floor($total/$size)],
          $retData, '成功', true, []);
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
        $page = $page>0 ?:1; 
        $size = intval($this->request()->getRequestParam('size')); 
        $size = $size>0 ?:10; 
        $offset = ($page-1)*$size;  

        $companyId = intval($this->request()->getRequestParam('xd_id')); 
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }

        $retData  =\App\HttpController\Models\RDS3\XdDl::create()
        ->where('xd_id', $companyId)
        ->limit($offset, $size)->all();
        //数据的总记录条数
        $total = \App\HttpController\Models\RDS3\XdDl::create()->where('xd_id', $companyId)->count();

        return $this->writeJson(200,
         ['total' => $total,'page' => $page, 'pageSize' => $size, 'totalPage'=> floor($total/$size)],
          $retData, '成功', true, []);
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
        $page = $page>0 ?:1; 
        $size = intval($this->request()->getRequestParam('size')); 
        $size = $size>0 ?:10; 
        $offset = ($page-1)*$size;  
        
        $companyId = intval($this->request()->getRequestParam('xd_id')); 
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }

        $retData  =\App\HttpController\Models\RDS3\XdDlRzGlTx::create()
        ->where('xd_id', $companyId)
        ->limit($offset, $size)->all();
        
        //数据的总记录条数
        $total = \App\HttpController\Models\RDS3\XdDlRzGlTx::create()->where('xd_id', $companyId)->count();

        return $this->writeJson(200,
         ['total' => $total,'page' => $page, 'pageSize' => $size, 'totalPage'=> floor($total/$size)],
          $retData, '成功', true, []);
    
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
        
        return $this->writeJson(200, ['total' => 100], $retData, '成功', true, []);
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
        
        return $this->writeJson(200, ['total' => 100], $retData, '成功', true, []);
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
        $page = $page>0 ?:1; 
        $size = intval($this->request()->getRequestParam('size')); 
        $size = $size>0 ?:10; 
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
            // $retData  =\App\HttpController\Models\RDS3\XdAppAndroid::create()->where('xd_id', $companyId)->limt(2)->all();
            $retData  =\App\HttpController\Models\RDS3\XdAppIos::create()
            ->where('xd_id', $companyId)
            ->limit($offset,$size)
            ->all();

            //数据的总记录条数
            $total = \App\HttpController\Models\RDS3\XdAppIos::create()
            ->where('xd_id', $companyId)
            ->count();
        }

        if($type == 'andoriod'){
            // $retData  =\App\HttpController\Models\RDS3\XdAppAndroid::create()->where('xd_id', $companyId)->limt(2)->all();
            $retData  =\App\HttpController\Models\RDS3\XdAppAndroid::create()
            ->where('xd_id', $companyId)
            ->limit($offset,$size)
            ->all();

            //数据的总记录条数
            $total = \App\HttpController\Models\RDS3\XdAppAndroid::create()
            ->where('xd_id', $companyId)
            ->count();
        } 
 
        return $this->writeJson(200,
         ['total' => $total,'page' => $page, 'pageSize' => $size, 'totalPage'=> floor($total/$size)],
          $retData, '成功', true, []);
    }
}
