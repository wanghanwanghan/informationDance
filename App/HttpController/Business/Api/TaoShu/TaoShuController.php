<?php

namespace App\HttpController\Business\Api\TaoShu;

use App\HttpController\Service\TaoShu\TaoShuService;
use App\Process\Service\ProcessService;

class TaoShuController extends TaoShuBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    //还有afterAction
    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //检验企查查返回值，并给客户计费
    private function checkResponse($res)
    {
        if (isset($res['PAGEINFO']) && isset($res['PAGEINFO']['TOTAL_COUNT']) && isset($res['PAGEINFO']['TOTAL_PAGE']) && isset($res['PAGEINFO']['CURRENT_PAGE'])) {
            $res['Paging'] = [
                'page' => $res['PAGEINFO']['CURRENT_PAGE'],
                'pageSize' => null,
                'total' => $res['PAGEINFO']['TOTAL_COUNT'],
                'totalPage' => $res['PAGEINFO']['TOTAL_PAGE'],
            ];

        } else {
            $res['Paging'] = null;
        }

        if (isset($res['coHttpErr'])) return $this->writeJson(500, $res['Paging'], [], 'co请求错误');

        $res['ISUSUAL'] == '1' ? $res['code'] = 200 : $res['code'] = 600;

        //拿返回结果
        isset($res['RESULTDATA']) ? $res['Result'] = $res['RESULTDATA'] : $res['Result'] = [];

        return $this->writeJson($res['code'], $res['Paging'], $res['Result'], null);
    }

    //法人变更
    function frbg()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $pageNo = $this->request()->getRequestParam('pageNo') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 100;

        $tmp = [];

        for ($i = 10; $i--;) {
            $postData = [
                'entName' => $entName,
                'pageNo' => $pageNo,
                'pageSize' => $pageSize,
            ];

            $res = (new TaoShuService())->setCheckRespFlag(true)->post($postData, 'getRegisterChangeInfo');

            if ($res['code'] === 200 && !empty($res['result'])) {
                //如果本次取到了，就循环找
                foreach ($res['result'] as $one) {
                    if ($one['ALTITEM'] === '法定代表人') {
                        array_push($tmp, $one);
                    }
                }
            } else {
                break;
            }

            $pageNo++;
        }

        return $this->writeJson(200, null, $tmp, '查询成功');
    }

    //企业名称检索
    function getEntByKeyword()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        //0企业名称模糊检索，1注册号,统一社会信用代码精准检索，2注册地址模糊检索，4智能模糊检索
        $type = $this->request()->getRequestParam('type') ?? 0;

        $postData = [
            'keyword' => $entName,
            'type' => $type,
        ];

        $res = (new TaoShuService())->post($postData, __FUNCTION__);

        return $this->checkResponse($res);
    }

    //企业基本信息
    function getRegisterInfo()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';

        $postData = ['entName' => $entName];

        $res = (new TaoShuService())->post($postData, __FUNCTION__);

        return $this->checkResponse($res);
    }

    //企业股东及出资信息
    function getShareHolderInfo()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $pageNo = $this->request()->getRequestParam('pageNo') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'entName' => $entName,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ];

        $res = (new TaoShuService())->post($postData, __FUNCTION__);

        return $this->checkResponse($res);
    }

    //企业对外投资
    function getInvestmentAbroadInfo()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $pageNo = $this->request()->getRequestParam('pageNo') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'entName' => $entName,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ];

        $res = (new TaoShuService())->post($postData, __FUNCTION__);

        return $this->checkResponse($res);
    }

    //企业分支机构
    function getBranchInfo()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $pageNo = $this->request()->getRequestParam('pageNo') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'entName' => $entName,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ];

        $res = (new TaoShuService())->post($postData, __FUNCTION__);

        return $this->checkResponse($res);
    }

    //企业变更信息
    function getRegisterChangeInfo()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $pageNo = $this->request()->getRequestParam('pageNo') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'entName' => $entName,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ];

        $res = (new TaoShuService())->post($postData, __FUNCTION__);

        return $this->checkResponse($res);
    }

    //企业主要管理人员
    function getMainManagerInfo()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $pageNo = $this->request()->getRequestParam('pageNo') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'entName' => $entName,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ];

        $res = (new TaoShuService())->post($postData, __FUNCTION__);

        return $this->checkResponse($res);
    }

    //法人代表对外投资
    function lawPersonInvestmentInfo()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $pageNo = $this->request()->getRequestParam('pageNo') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'entName' => $entName,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ];

        $res = (new TaoShuService())->post($postData, __FUNCTION__);

        return $this->checkResponse($res);
    }

    //法人代表其他公司任职
    function getLawPersontoOtherInfo()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $pageNo = $this->request()->getRequestParam('pageNo') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'entName' => $entName,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ];

        $res = (new TaoShuService())->post($postData, __FUNCTION__);

        return $this->checkResponse($res);
    }

    //企业最终控制人
    function getGraphGFinalData()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        //R102-企业股东，R104-自然人股东，R108-总部
        $attIds = $this->request()->getRequestParam('attIds') ?? 'R102';
        $level = $this->request()->getRequestParam('level') ?? 10;
        //GS-企业节点，GR-人员节点
        $nodeType = $this->request()->getRequestParam('nodeType') ?? 'GS';

        $postData = [
            'keyword' => $entName,
            'attIds' => $attIds,
            'level' => $level,
            'nodeType' => $nodeType,
        ];

        $res = (new TaoShuService())->post($postData, __FUNCTION__);

        return $this->checkResponse($res);
    }

    //企业经营异常
    function getOperatingExceptionRota()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';

        $postData = [
            'entName' => $entName,
        ];

        $res = (new TaoShuService())->post($postData, __FUNCTION__);

        return $this->checkResponse($res);
    }

    //企业股权出质列表
    function getEquityPledgedInfo()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $pageNo = $this->request()->getRequestParam('pageNo') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'entName' => $entName,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ];

        $res = (new TaoShuService())->post($postData, __FUNCTION__);

        return $this->checkResponse($res);
    }

    //企业股权出质详情
    function getEquityPledgedDetailInfo()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = [
            'rowKey' => $id,
        ];

        $res = (new TaoShuService())->post($postData, __FUNCTION__);

        return $this->checkResponse($res);
    }

    //企业动产抵押列表
    function getChattelMortgageInfo()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $pageNo = $this->request()->getRequestParam('pageNo') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'entName' => $entName,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ];

        $res = (new TaoShuService())->post($postData, __FUNCTION__);

        return $this->checkResponse($res);
    }

    //企业动产抵押详情
    function getChattelMortgageDetailInfo()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = [
            'rowKey' => $id,
        ];

        $res = (new TaoShuService())->post($postData, __FUNCTION__);

        return $this->checkResponse($res);
    }

    //企业实际控制人信息
    function getEntActualContoller()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        //R102-法人股东，R104-企业自然人股东，R108-总部
        $attIds = $this->request()->getRequestParam('attIds') ?? 'R102';
        $level = $this->request()->getRequestParam('level') ?? 10;

        $postData = [
            'keyword' => $entName,
            'attIds' => $attIds,
            'level' => $level,
        ];

        $res = (new TaoShuService())->post($postData, __FUNCTION__);

        return $this->checkResponse($res);
    }

    //企业年报对外担保信息
    function getEntAnnReportForGuaranteeInfo()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $reportDate = $this->request()->getRequestParam('reportDate') ?? 2015;

        $postData = [
            'entName' => $entName,
            'reportDate' => $reportDate,
        ];

        $res = (new TaoShuService())->post($postData, __FUNCTION__);

        return $this->checkResponse($res);
    }


}