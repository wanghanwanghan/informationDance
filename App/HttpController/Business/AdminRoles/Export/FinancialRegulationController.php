<?php

namespace App\HttpController\Business\AdminRoles\Export;

use App\HttpController\Business\AdminRoles\User\UserController;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\FaYanYuan\FaYanYuanService;

class FinancialRegulationController extends UserController
{
    /**
     * 法海 - 央行行政处罚
     */
    public function fhGetPbcparty($entNames){
        $fileName = date('YmdHis', time()) . '央行行政处罚.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '处罚时间',
            '事件描述',
            '管理机关',
            '内容',
            '事件依据',
            '公告编号',
            '事件名称',
            '法定代表人',
            '事件结果',
            '事件日期',
            '被查企业',
            '事件结果',
            '标题',
            '发布时间',
            '数据类型',
            '大类',
            '小类',
            '信号描述',
            '信号等级',
            '规则编码',
            '规则版本',
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getPbcparty($ent['entName'],1);
            if(empty($data['pbcpartyList'])) {
                file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
                continue;
            }
            if(isset($data['totalPageNum']) && $data['totalPageNum']>1){
                for($i=2;$i<=$data['totalPageNum'];$i++){
                    $data2 = $this->getPbcparty($ent['entName'],1);
                    $data['pbcpartyList'] = array_merge($data['pbcpartyList'],$data2['pbcpartyList']);
                }
            }
            foreach ($data['pbcpartyList'] as $datum) {
                $resData[] = $this->getPbcpartyDetail($datum['entryId'],$file,$ent['entName']);
            }
//            dingAlarm('裁判文书',['$entName'=>$ent['entName'],'$data'=>json_encode($data)]);
        }
        return [$fileName, $resData];
    }

    /**
     * 法海 - 央行行政处罚
     */
    public function getPbcparty($entName,$page){
        $docType = 'pbcparty';
        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => 10,
        ];
        return (new FaYanYuanService())->getList(CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'pbc', $postData);
    }

    /**
     * 法海 - 央行行政处罚详情
     */
    function getPbcpartyDetail($id,$file,$name)
    {
        $postData = ['id' => $id];
        $docType = 'pbcparty';
        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);
        $data = $res[$docType]['0'];
        if(empty($data)){
            file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
            return [];
        }

        $insertData = [
            $name,
            $data['sortTime'],
            $data['eventDesc'],
            $data['authority'],
            $data['body'],
            $data['eventYiju'],
            $data['caseNo'],
            $data['eventName'],
            $data['legalRepresentative'],
            $data['eventResult'],
            $data['eventDate'],
            $data['pname'],
            $data['eventType'],
            $data['title'],
            $data['postTime'],
            $data['dataType'],
            $data['ruleMainType'],
            $data['ruleSubType'],
            $data['signalDesc'],
            $data['signalRating'],
            $data['signalRuleNo'],
            $data['signalRuleVersion'],
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        return $insertData;
    }

    /**
     * 法海 - 银保监会处罚公示
     */
    public function getPbcpartyCbrc($entName,$page){
        $docType = 'pbcparty_cbrc';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => 10,
        ];

        return (new FaYanYuanService())->getList(CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'pbc', $postData);

    }

    /**
     * 法海 - 银保监会处罚公示详情
     */
    function getPbcpartyCbrcDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'pbcparty_cbrc';

        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);

    }

    /**
     * 法海 - 证监处罚公示
     */
    public function getPbcpartyCsrcChufa($entName,$page){
        $docType = 'pbcparty_csrc_chufa';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => 10,
        ];

        return (new FaYanYuanService())->getList(CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'pbc', $postData);

    }

    /**
     * 法海 - 证监处罚公示详情
     */
    function getPbcpartyCsrcChufaDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'pbcparty_csrc_chufa';

        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);

    }

    /**
     * 法海 - 证监会许可批复等级
     */
    public function getPbcpartyCsrcXkpf($entName,$page){
        $docType = 'pbcparty_csrc_xkpf';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => 10,
        ];

        return (new FaYanYuanService())->getList(CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'pbc', $postData);
    }

    /**
     * 法海 - 证监会许可批复等级详情
     */
    function getPbcpartyCsrcXkpfDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'pbcparty_csrc_xkpf';

        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);

    }

    /**
     * 法海 - 外汇局处罚
     */
    public function getSafeChufa($entName,$page){
        $docType = 'safe_chufa';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => 10,
        ];

        return (new FaYanYuanService())->getList(CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'pbc', $postData);

    }

    /**
     * 法海 - 外汇局处罚详情
     */
    function getSafeChufaDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'safe_chufa';

        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);

    }

    /**
     * 法海 - 外汇局许可
     */
    public function getSafeXuke($entName,$page){
        $docType = 'safe_xuke';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => 10,
        ];

        return (new FaYanYuanService())->getList(CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'pbc', $postData);

    }

    /**
     * 法海 - 外汇局许可详情
     */
    function getSafeXukeDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'safe_xuke';

        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }
}