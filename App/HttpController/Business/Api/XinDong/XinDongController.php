<?php

namespace App\HttpController\Business\Api\XinDong;

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongDun\LongDunService;
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
        $entName = $this->request()->getRequestParam('entName');
        $industry = $this->request()->getRequestParam('industry');

        $industry_arr = [
            '新能源汽车' => [
                '上汽通用五菱汽车股份有限公司',
                '比亚迪股份有限公司',
                '特斯拉（上海）有限公司',
                '长城汽车股份有限公司',
                '上海汽车集团股份有限公司',
                '奇瑞汽车股份有限公司',
                '一汽—大众汽车有限公司',
                '上汽大众汽车有限公司',
                '广州汽车集团股份有限公司',
                '蔚来控股有限公司',
                '北京汽车股份有限公司',
                '东风汽车股份有限公司',
                '广东小鹏汽车科技有限公司',
                '北京车和家信息技术有限公司',
                '浙江吉利控股集团有限公司',
                '重庆长安汽车股份有限公司',
            ],
            '云计算行业' => [
                '阿里云计算有限公司 ',
                '腾讯云计算（北京）有限责任公司',
                '华为云计算技术有限公司',
                '百度云计算技术（北京）有限公司',
                '珠海金山云科技有限公司',
                '优刻得科技股份有限公司',
                '京东云计算有限公司',
                '新华三云计算技术有限公司',
                '杭州朗和科技有限公司',
                '浪潮云信息技术股份公司',
            ]
        ];


    }

}