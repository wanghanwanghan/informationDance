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
use EasySwoole\ElasticSearch\Config;
use EasySwoole\ElasticSearch\ElasticSearch;
use EasySwoole\ElasticSearch\RequestBean\Search;
use App\ElasticSearch\Service\ElasticSearchService;
use Carbon\Carbon;
use EasySwoole\ORM\DbManager;
use wanghanwanghan\someUtils\control;

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
      * 高级搜索 
        https://api.meirixindong.com/api/v1/xd/advancedSearch 
      * 
      * 
     */
    function advancedSearch(): bool
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
         
        (new XinDongService())->saveSearchHistory(
            $this->loginUserinfo['id'],
            $elasticSearchService->queryArr,
            ''
        );
        return $this->writeJson(200, intval($responseArr['hits']['total'])/$postData['size'], $responseArr['hits']['hits'], '成功', true, []);
    }

    function formatRequestData($requestDataArr, $config){
        $return = [];
        foreach($config as $key => $defaultValue){
            $return[$key] = $requestDataArr[$key] ?? $defaultValue; 
        }
        return $return;
    }
}
