<?php

namespace App\HttpController\Business\Api\XinDong;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongDun\LongDunService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Pay\ChargeService;
use App\HttpController\Service\XinDong\XinDongService;

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

    private function checkResponse($res)
    {
        return $this->writeJson((int)$res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //控股法人股东的司法风险
    function getCorporateShareholderRisk()
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
                $check = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'ECIPartner/GetList', ['searchKey' => $one['StockName']]);
                //有股东，说明是企业法人
                ($check['code'] != 200 || empty($check['result'])) ?: $entName = $one['StockName'];
            }
        }

        if (empty($entName)) return $this->checkResponse(['code' => 200, 'paging' => null, 'result' => [], 'msg' => '查询成功']);

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

        $result['result'] = [
            'fz_list' => $fz_list,
            'fm_list' => $fm_list,
        ];

        return $this->checkResponse($result);
    }

}