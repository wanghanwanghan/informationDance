<?php

namespace App\HttpController\Business\Api\FaHai;

use App\HttpController\Service\FaHai\FaHaiService;

class FaHaiController extends FaHaiBase
{
    private $listBaseUrl;
    private $detailBaseUrl;

    function onRequest(?string $action): ?bool
    {
        $this->listBaseUrl=\Yaconf::get('fahai.listBaseUrl');
        $this->detailBaseUrl=\Yaconf::get('fahai.detailBaseUrl');

        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //检验法海返回值，并给客户计费
    private function checkResponse($res,$docType,$type)
    {
        $type=ucfirst($type);

        if (isset($res['pageNo']) && isset($res['range']) && isset($res['totalCount']) && isset($res['totalPageNum']))
        {
            $res['Paging']=[
                'page'=>$res['pageNo'],
                'pageSize'=>$res['range'],
                'total'=>$res['totalCount'],
                'totalPage'=>$res['totalPageNum'],
            ];

        }else
        {
            $res['Paging']=null;
        }

        if (isset($res['coHttpErr'])) return $this->writeJson(500,$res['Paging'],[],'co请求错误');

        $res['code'] === 's' ? $res['code'] = 200 : $res['code'] = 600;

        //拿返回结果
        isset($res[$docType.$type]) ? $res['Result'] = $res[$docType.$type] : $res['Result'] = [];

        return $this->writeJson($res['code'],$res['Paging'],$res['Result'],$res['msg']);
    }

    //环保处罚
    function getEpbparty()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='epbparty';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'epb',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //重点监控企业名单
    function getEpbpartyJkqy()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='epbparty_jkqy';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'epb',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //环保企业自行监测结果
    function getEpbpartyZxjc()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='epbparty_zxjc';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'epb',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //环评公示数据
    function getEpbpartyHuanping()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='epbparty_huanping';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'epb',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //海关企业
    function getCustomQy()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='custom_qy';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'custom',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //海关许可
    function getCustomXuke()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='custom_xuke';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'custom',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //海关信用
    function getCustomCredit()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='custom_credit';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'custom',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //海关处罚
    function getCustomPunish()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='custom_punish';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'custom',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //开庭公告
    function getKtgg()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='ktgg';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'sifa',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //裁判文书
    function getCpws()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='cpws';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'sifa',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //法院公告
    function getFygg()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='fygg';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'sifa',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //执行公告
    function getZxgg()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='zxgg';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'sifa',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //失信公告
    function getShixin()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='shixin';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'sifa',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //司法查封冻结扣押
    function getSifacdk()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='sifacdk';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'sifa',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //欠税公告
    function getSatpartyQs()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='satparty_qs';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'sat',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //涉税处罚公示
    function getSatpartyChufa()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='satparty_chufa';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'sat',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //税务非正常户公示
    function getSatpartyFzc()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='satparty_fzc';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'sat',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //纳税信用等级
    function getSatpartyXin()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='satparty_xin';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'sat',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //税务登记
    function getSatpartyReg()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='satparty_reg';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'sat',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //税务许可
    function getSatpartyXuke()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='satparty_xuke';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'sat',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //央行行政处罚
    function getPbcparty()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='pbcparty';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'pbc',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //银保监会处罚公示
    function getPbcpartyCbrc()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='pbcparty_cbrc';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'pbc',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //证监处罚公示
    function getPbcpartyCsrcChufa()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='pbcparty_csrc_chufa';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'pbc',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //证监会许可批复等级
    function getPbcpartyCsrcXkpf()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='pbcparty_csrc_xkpf';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'pbc',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //外汇局处罚
    function getSafeChufa()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='safe_chufa';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'pbc',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //外汇局许可
    function getSafeXuke()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='safe_xuke';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'pbc',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //应收帐款
    function getCompanyZdwYszkdsr()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='company_zdw_yszkdsr';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'zdw',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //租赁登记
    function getCompanyZdwZldjdsr()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='company_zdw_zldjdsr';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'zdw',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //保证金质押登记
    function getCompanyZdwBzjzydsr()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='company_zdw_bzjzydsr';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'zdw',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //仓单质押
    function getCompanyZdwCdzydsr()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='company_zdw_cdzydsr';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'zdw',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //所有权保留
    function getCompanyZdwSyqbldsr()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='company_zdw_syqbldsr';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'zdw',$postData);

        return $this->checkResponse($res,$docType,'list');
    }

    //其他动产融资
    function getCompanyZdwQtdcdsr()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $docType='company_zdw_qtdcdsr';

        $postData=[
            'doc_type'=>$docType,
            'keyword'=>$entName,
            'pageno'=>$page,
            'range'=>$pageSize,
        ];

        $res=(new FaHaiService())->getList($this->listBaseUrl.'zdw',$postData);

        return $this->checkResponse($res,$docType,'list');
    }




}