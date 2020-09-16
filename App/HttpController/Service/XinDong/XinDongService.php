<?php

namespace App\HttpController\Service\XinDong;

use App\Csp\Service\CspService;
use App\HttpController\Service\FaHai\FaHaiService;
use App\HttpController\Service\ServiceBase;
use wanghanwanghan\someUtils\traits\Singleton;

class XinDongService extends ServiceBase
{
    use Singleton;

    private $fhList;

    function __construct()
    {
        $this->fhList = \Yaconf::get('fahai.listBaseUrl');

        return parent::onNewService();
    }

    //处理结果给信息controller
    private function checkResp($code,$paging,$result,$msg)
    {
        return $this->createReturn((int)$code,$paging,$result,$msg);
    }

    //控股法人股东的司法风险
    function getCorporateShareholderRisk(string $entName): array
    {
        $csp=CspService::getInstance()->create();

        //====================债务-顶====================
        //融资租赁
        $docType='company_zdw_zldjdsr';
        $csp->add($docType,function () use ($entName,$docType) {
            $postData=[
                'doc_type'=>$docType,
                'keyword'=>$entName,
                'pageno'=>1,
                'range'=>10,
            ];
            return (new FaHaiService())->setCheckRespFlag(true)->getList($this->fhList.'zdw',$postData);
        });
        //其他动产融资
        $docType='company_zdw_qtdcdsr';
        $csp->add($docType,function () use ($entName,$docType) {
            $postData=[
                'doc_type'=>$docType,
                'keyword'=>$entName,
                'pageno'=>1,
                'range'=>10,
            ];
            return (new FaHaiService())->setCheckRespFlag(true)->getList($this->fhList.'zdw',$postData);
        });
        //====================债务-底====================

        //====================债权-顶====================
        //保证金质押登记
        $docType='company_zdw_bzjzydsr';
        $csp->add($docType,function () use ($entName,$docType) {
            $postData=[
                'doc_type'=>$docType,
                'keyword'=>$entName,
                'pageno'=>1,
                'range'=>10,
            ];
            return (new FaHaiService())->setCheckRespFlag(true)->getList($this->fhList.'zdw',$postData);
        });
        //应收账款登记
        $docType='company_zdw_yszkdsr';
        $csp->add($docType,function () use ($entName,$docType) {
            $postData=[
                'doc_type'=>$docType,
                'keyword'=>$entName,
                'pageno'=>1,
                'range'=>10,
            ];
            return (new FaHaiService())->setCheckRespFlag(true)->getList($this->fhList.'zdw',$postData);
        });
        //仓单质押登记
        $docType='company_zdw_cdzydsr';
        $csp->add($docType,function () use ($entName,$docType) {
            $postData=[
                'doc_type'=>$docType,
                'keyword'=>$entName,
                'pageno'=>1,
                'range'=>10,
            ];
            return (new FaHaiService())->setCheckRespFlag(true)->getList($this->fhList.'zdw',$postData);
        });
        //所有权保留
        $docType='company_zdw_syqbldsr';
        $csp->add($docType,function () use ($entName,$docType) {
            $postData=[
                'doc_type'=>$docType,
                'keyword'=>$entName,
                'pageno'=>1,
                'range'=>10,
            ];
            return (new FaHaiService())->setCheckRespFlag(true)->getList($this->fhList.'zdw',$postData);
        });
        //====================债权-底====================

        //欠税公告
        $docType='satparty_qs';
        $csp->add($docType,function () use ($entName,$docType) {
            $postData=[
                'doc_type'=>$docType,
                'keyword'=>$entName,
                'pageno'=>1,
                'range'=>10,
            ];
            return (new FaHaiService())->setCheckRespFlag(true)->getList($this->fhList.'sat',$postData);
        });

        //涉税处罚公示
        $docType='satparty_chufa';
        $csp->add($docType,function () use ($entName,$docType) {
            $postData=[
                'doc_type'=>$docType,
                'keyword'=>$entName,
                'pageno'=>1,
                'range'=>10,
            ];
            return (new FaHaiService())->setCheckRespFlag(true)->getList($this->fhList.'sat',$postData);
        });

        //税务非正常户
        $docType='satparty_fzc';
        $csp->add($docType,function () use ($entName,$docType) {
            $postData=[
                'doc_type'=>$docType,
                'keyword'=>$entName,
                'pageno'=>1,
                'range'=>10,
            ];
            return (new FaHaiService())->setCheckRespFlag(true)->getList($this->fhList.'sat',$postData);
        });

        //执行
        $res=CspService::getInstance()->exec($csp);

        //整理返回数组
        foreach ($res as $key => $arr)
        {
            if ($arr['code']===200 && !empty($arr['paging']))
            {
                $num=$arr['paging']['total'];
            }else
            {
                $num=0;
            }

            $result[$key]=$num;
        }

        return $this->checkResp(200,null,$result ?? [],'查询成功');
    }


}
