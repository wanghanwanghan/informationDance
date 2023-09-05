<?php

namespace App\HttpController\Business\Provide\DaTong;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\DaTong\DaTongService;

class DaTongController extends ProvideBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function checkResponse($res): bool
    {
        if (empty($res[$this->cspKey])) {
            $this->responseCode = 500;
            $this->responsePaging = null;
            $this->responseData = $res[$this->cspKey];
            $this->spendMoney = 0;
            $this->responseMsg = '请求超时';
        } else {
            $this->responseCode = $res[$this->cspKey]['code'];
            $this->responsePaging = $res[$this->cspKey]['paging'];
            $this->responseData = $res[$this->cspKey]['result'];
            $this->responseMsg = $res[$this->cspKey]['msg'];

            $res[$this->cspKey]['code'] === 200 ?: $this->spendMoney = 0;
        }

        return true;
    }

    function getList(): bool
    {
        //参数名称	数据类型	必填	示例	说明
        //keyword	String[]	否	["关键词1","关键词2"]	查询关键词
        //buyCompany	String[]	否	["招标公司1","招标公司2"]	招标公司名称
        //winCompany	String[]	否	["中标公司1","中标公司2"]	中标公司名称
        //index	int	否	0	索引，默认为 0 表示从第 0 条开始返回，每次调用接口会返回下一次应该传入的index。类似偏移量拿取
        //beginDate	String	否	"yyyy-MM-dd"	公告发布时间开始时间
        //endDate	String	否	"yyyy-MM-dd"	公告发布时间结束时间
        //maxMoney	Decimal(22,6)	否	9999.99	招标预算最大金额
        //minMoney	Decimal(22,6)	否	0	招标预算最小金额
        //exclude	String[]	否	["排除词1","排除词2"]	查询排除词
        //bidType	String[]	否	["101","107"]	公告类型
        //modifyBeginDate	String	否	"yyyy-MM-dd HH:mm:ss"	数据最近更新时间开始时间
        //modifyEndDate	String	否	"yyyy-MM-dd HH:mm:ss"	数据最近更新时间结束时间

        $data = [];
        $data['keyword'] = $this->getRequestData('keyword');
        $data['buyCompany'] = $this->getRequestData('buyCompany');
        $data['winCompany'] = $this->getRequestData('winCompany');
        $data['bidType'] = $this->getRequestData('bidType');

        $data = array_map(function ($row) {
            if (!empty($row)) {
                return explode(',', trim($row, ','));
            }
            return $row;
        }, $data);

        $data['beginDate'] = $this->getRequestData('beginDate');
        $data['endDate'] = $this->getRequestData('endDate');
        $data['index'] = $this->getRequestData('index');
        $data['pageSize'] = $this->getRequestData('pageSize');

        $this->csp->add($this->cspKey, function () use ($data) {
            return (new DaTongService())
                ->setCheckRespFlag(true)
                ->getList(array_filter($data), $this->userId);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

}