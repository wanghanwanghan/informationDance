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
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\Http\Message\UploadFile;
use App\HttpController\Models\Api\UserBusinessOpportunity;
use App\HttpController\Models\Api\UserBusinessOpportunityBatch;
use App\HttpController\Models\RDS3\Company;
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

        return $this->writeJson(200, null, $searchOptionArr, '成功', true, []);
    }
 

    /**
      * 
      * 高级搜索 |着急上线 先直接写的query  有时间 需要用es service的集成方法
        https://api.meirixindong.com/api/v1/xd/advancedSearch 
      * 
      * 
     */  
    // 新版 
    function advancedSearch(): bool
    { 
        $ElasticSearchService = new ElasticSearchService(); 
        
        // 需要按文本搜索的  
        $addMustMatchPhraseQueryMap = [
            // 名称  name  全名匹配 
            'name' =>trim($this->request()->getRequestParam('searchText')),
            // basic_opscope: 经营范围
            'business_scope' =>trim($this->request()->getRequestParam('basic_opscope')),
        ];
        foreach($addMustMatchPhraseQueryMap as $field=>$value){
            $value && $ElasticSearchService->addMustMatchPhraseQuery( $field , $value) ; 
        } 

        // 搜索战略新兴产业
        $basicJlxxcyidStr = trim($this->request()->getRequestParam('basic_jlxxcyid'));
        $basicJlxxcyidStr && $basicJlxxcyidArr = explode(',',  $basicJlxxcyidStr);
        if(
            !empty($basicJlxxcyidArr)
        ){
            $siJiFenLeiDatas = \App\HttpController\Models\RDS3\ZlxxcyNicCode::create()
                ->where('zlxxcy_id', $basicJlxxcyidArr, 'IN') 
                ->all();
            $matchedCnames = array_column($siJiFenLeiDatas, 'nic_id');
           $ElasticSearchService
                ->addMustShouldPhraseQuery( 'si_ji_fen_lei_code' , $matchedCnames) ; 
    
        }

        // 搜索shang_pin_data 商品信息 appStr:五香;农庄
        $appStr =   trim($this->request()->getRequestParam('appStr')); 
        $appStr && $appStrDatas = explode(';', $appStr);
        !empty($appStrDatas) && $ElasticSearchService->addMustShouldPhraseQuery( 'shang_pin_data.name' , $appStrDatas) ;
    
        //传过来的searchOption 例子 [{"type":20,"value":["5","10","2"]},{"type":30,"value":["15","5"]}]
        $searchOptionStr =  trim($this->request()->getRequestParam('searchOption'));
        $searchOptionArr = json_decode($searchOptionStr, true);
        
        // 把具体需要搜索的各项摘出来
        $org_type_values = [];  // 企业类型  
        $estiblish_time_values = [];  // 成立年限  
        $reg_status_values = [];// 营业状态 
        $reg_capital_values = [];  // 注册资本
        $ying_shou_gui_mo_values = [];  // 营收规模
        $tuan_dui_ren_shu_values = [];  // 团队人数
        $web_values = []; //官网
        $app_values = []; //官网
        foreach($searchOptionArr as $item){ 
            if($item['pid'] == 10){
                $org_type_values = $item['value'];  
            }
 
            if($item['pid'] == 20){ 
                $estiblish_time_values = $item['value']; 
            }
   
            if($item['pid'] == 30){
                $reg_status_values = $item['value']; 
            }
 
            if($item['pid'] == 40){ 
                $reg_capital_values = $item['value']; 
            }
  
            if($item['pid'] == 50){ 
                $ying_shou_gui_mo_values = $item['value']; 
            }
            if($item['pid'] == 60){ 
                $tuan_dui_ren_shu_values = $item['value']; 
            }
            if($item['pid'] == 70){ 
                $web_values = $item['value']; 
            }
            if($item['pid'] == 80){ 
                $app_values = $item['value']; 
            }
        }

        //必须存在官网 
        foreach($web_values as $value){
            if($value){
                // $ElasticSearchService->addMustExistsQuery( 'web') ; 
                $ElasticSearchService->addMustRegexpQuery( 'web', ".+") ; 
                
                break;
            }
        }

        //必须存在APP 
        foreach($app_values as $value){
            if($value){ 
                $ElasticSearchService->addMustRegexpQuery( 'app', ".+") ;  
                break;
            }
        }

        // 企业类型 :传过来的是10 20 转换成对应文案 然后再去搜索  
        $matchedCnames = [];
        foreach($org_type_values as $orgType){
            $orgType && $matchedCnames[] = (new XinDongService())->getCompanyOrgType()[$orgType]; 
        }
        (!empty($matchedCnames)) && $ElasticSearchService->addMustShouldPhraseQuery( 'company_org_type' , $matchedCnames) ;
    
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
            // 20年以上
            25 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -100 year')), 'max' => date('Y-m-d', strtotime(date('Y-m-01') . ' -20 year'))  ],
        ];
        foreach($estiblish_time_values as $item){
            $item && $matchedCnames[] = $map[$item]; 
        } 
        (!empty($matchedCnames)) && $ElasticSearchService->addMustShouldRangeQuery( 'estiblish_time' , $matchedCnames) ; 
    
        // 营业状态   传过来的是 10  20  转换成文案后 去匹配  
        $matchedCnames = [];
        foreach($reg_status_values as $item){
            $item && $matchedCnames[] = (new XinDongService())->getRegStatus()[$item]; 
        }
        (!empty($matchedCnames)) && $ElasticSearchService->addMustShouldPhraseQuery( 'reg_status' , $matchedCnames) ; 
    
        // 注册资本 传过来的是 10 20 转换成最大最小范围后 再去搜索
        $map = [
            // 100万以下 
            10 => ['min'=>0, 'max' => 100  ],
            // 100-500万
            15 =>  ['min'=>100, 'max' => 500  ], 
            // 500-1000
            20 =>  ['min'=>500, 'max' => 1000  ],
            // 1000-5000万
            25 =>  ['min'=>1000, 'max' => 5000  ],
            // 5000-10000万
            30 =>  ['min'=>5000, 'max' => 10000  ],
            // 10000-10亿
            35 =>  ['min'=>10000, 'max' => 100000  ],
            // 10亿+
            40 =>  ['min'=>100000, 'max' => 1000-000  ],
        ];
        $matchedCnames = [];
        foreach($reg_capital_values as $item){
            $item && $matchedCnames[] = $map[$item]; 
        } 
        (!empty($matchedCnames)) && $ElasticSearchService->addMustShouldRangeQuery( 'reg_capital' , $matchedCnames) ;  
         

        // 团队人数 传过来的是 10 20 转换成最大最小范围后 再去搜索
        $map =  (new XinDongService())::getTuanDuiGuiMoMap();
        $matchedCnames = [];
        // foreach($tuan_dui_ren_shu_values as $item){
        //     $item && $matchedCnames[] = $map[$item]; 
        // } 
        // (!empty($matchedCnames)) && $ElasticSearchService->addMustShouldRangeQuery( 'tuan_dui_ren_shu' , $matchedCnames) ;  
        
        foreach($tuan_dui_ren_shu_values as $item){
            $tmp = $map[$item]['epreg']; 
            foreach($tmp as $tmp_item){
                $matchedCnames[] = $tmp_item;
            } 
        } 
        (!empty($matchedCnames)) && $ElasticSearchService->addMustShouldRegexpQuery( 
            'tuan_dui_ren_shu' , $matchedCnames
        ) ;
       


        // 营收规模  传过来的是 10 20 转换成对应文案后再去匹配
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

        (!empty($matchedCnames)) && $ElasticSearchService->addMustShouldPhraseQuery( 'ying_shou_gui_mo' , $matchedCnames) ;  
        


        //四级分类 basic_nicid: A0111,A0112,A0113,
        $siJiFenLeiStrs = trim($this->request()->getRequestParam('basic_nicid'));
        $siJiFenLeiStrs && $siJiFenLeiArr = explode(',', $siJiFenLeiStrs); 
        if(!empty($siJiFenLeiArr)){
            $ElasticSearchService->addMustShouldPhraseQuery( 'si_ji_fen_lei_code' , $siJiFenLeiArr) ;   
        }

        // 地区 basic_regionid: 110101,110102,
        $basiRegionidStr = trim($this->request()->getRequestParam('basic_regionid')); 
        $basiRegionidStr && $basiRegionidArr = explode(',',$basiRegionidStr);
        if(!empty($basiRegionidArr)){ 
            $ElasticSearchService->addMustShouldPrefixQuery( 'reg_number' , $basiRegionidArr) ;  
        }

        $size = $this->request()->getRequestParam('size')??10;
        $page = $this->request()->getRequestParam('page')??1;
        $offset  =  ($page-1)*$size;
        $ElasticSearchService->addSize($size) ;
        $ElasticSearchService->addFrom($offset) ;
        $ElasticSearchService->addSort('xd_id', 'desc') ;

        //设置默认值 不传任何条件 搜全部
        $ElasticSearchService->setDefault() ;  

        $responseJson = (new XinDongService())->advancedSearch($ElasticSearchService);
        $responseArr = @json_decode($responseJson,true); 
        CommonService::getInstance()->log4PHP('advancedSearch-Es '.@json_encode(
            [
                'hits' => $responseArr['hits']['hits'],
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

        foreach($hits as &$dataItem){ 
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
            'total' => intval($responseArr['hits']['total']['value']),
            'totalPage' => (int)floor(intval($responseArr['hits']['total']['value'])/
            ($size)),
         
        ] 
       , $hits, '成功', true, []);
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
        
        $retData  =\App\HttpController\Models\RDS3\Company::create()->where('id', $companyId)->get();
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
            ->where('userId', $this->loginUserinfo['id'])->page($page)->withTotalCount();
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

        return $this->writeJson(200, ['total' => $total,'page' => $page, 'pageSize' => $size, 
        'totalPage'=> floor($total/$size)], $retData, '成功', true, []);
    
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
        
        $model = \App\HttpController\Models\RDS3\Company::create()
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

        
        $ElasticSearchService = new ElasticSearchService(); 
        
        $ElasticSearchService->addMustMatchQuery( 'xd_id' , $companyId) ;  

        $size = $this->request()->getRequestParam('size')??1;
        $page = $this->request()->getRequestParam('page')??1;
        $offset  =  ($page-1)*$size;
        $ElasticSearchService->addSize($size) ;
        $ElasticSearchService->addFrom($offset) ; 

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

        foreach($hits as &$dataItem){
            // 公司简介
            $tmpArr = explode('&&&', trim($dataItem['_source']['gong_si_jian_jie']));
            array_pop($tmpArr);
            $dataItem['_source']['gong_si_jian_jie_data_arr'] = [];
            foreach($tmpArr as $tmpItem_){
                $dataItem['_source']['gong_si_jian_jie_data_arr'][] = [$tmpItem_];
            }
            
            // tag信息
            $dataItem['_source']['tags'] = array_values(
                (new XinDongService())::getAllTagesByData(
                    $dataItem['_source']
                )
            );

            // 官网信息
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
            'total' => intval($responseArr['hits']['total']['value']),
            'totalPage' => (int)floor(intval($responseArr['hits']['total']['value'])/
            ($size)),
         
        ] 
       , $hits[0]['_source'], '成功', true, []);
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
}
