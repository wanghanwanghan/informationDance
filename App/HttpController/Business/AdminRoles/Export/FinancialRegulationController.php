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
                    $data2 = $this->getPbcparty($ent['entName'],$i);
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
            date('Y-m-d',$data['sortTime']/1000),
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
            date('Y-m-d',$data['postTime']/1000),
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        return $insertData;
    }

    /**
     * 法海 - 银保监会处罚公示
     */
    public function fhGetPbcpartyCbrc($entNames){
        $fileName = date('YmdHis', time()) . '银保监会处罚公示.csv';
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
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getPbcpartyCbrc($ent['entName'],1);
            if(empty($data['pbcparty_cbrcList'])) {
                file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
                continue;
            }
            if(isset($data['totalPageNum']) && $data['totalPageNum']>1){
                for($i=2;$i<=$data['totalPageNum'];$i++){
                    $data2 = $this->getPbcpartyCbrc($ent['entName'],$i);
                    $data['pbcparty_cbrcList'] = array_merge($data['pbcparty_cbrcList'],$data2['pbcparty_cbrcList']);
                }
            }
            foreach ($data['pbcparty_cbrcList'] as $datum) {
                $resData[] = $this->getPbcpartyCbrcDetail($datum['entryId'],$file,$ent['entName']);
            }
//            dingAlarm('裁判文书',['$entName'=>$ent['entName'],'$data'=>json_encode($data)]);
        }
        return [$fileName, $resData];
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
    function getPbcpartyCbrcDetail($id,$file,$name)
    {
        $postData = ['id' => $id];
        $docType = 'pbcparty_cbrc';
        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);
        $data = $res[$docType]['0'];
        if(empty($data)){
            file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
            return [];
        }

        $insertData = [
            $name,
            date('Y-m-d',$data['sortTime']/1000),
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
            date('Y-m-d',$data['postTime']/1000),
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        return $insertData;
    }

    /**
     * 法海 - 证监处罚公示
     */
    public function fhGetPbcpartyCsrcChufa($entNames){
        $fileName = date('YmdHis', time()) . '证监处罚公示.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '公告类型',
            '处罚决定书文号',
            '处罚事由',
            '处罚依据',
            '当事人所属单位',
            '法定代表人姓名 /负责人',
            '处罚机关',
            '处罚结果/行政处罚决定',
            '处罚时间',
            '发布时间',
            '违规主体名称',
            '公告标题/案件名称',
            '源码/正文',
            '发布时间',
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getPbcpartyCsrcChufa($ent['entName'],1);
            if(empty($data['pbcparty_csrc_chufaList'])) {
                file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
                continue;
            }
            if(isset($data['totalPageNum']) && $data['totalPageNum']>1){
                for($i=2;$i<=$data['totalPageNum'];$i++){
                    $data2 = $this->getPbcpartyCsrcChufa($ent['entName'],$i);
                    $data['pbcparty_csrc_chufaList'] = array_merge($data['pbcparty_csrc_chufaList'],$data2['pbcparty_csrc_chufaList']);
                }
            }
            foreach ($data['pbcparty_csrc_chufaList'] as $datum) {
                $resData[] = $this->getPbcpartyCsrcChufaDetail($datum['entryId'],$file,$ent['entName']);
            }
//            dingAlarm('裁判文书',['$entName'=>$ent['entName'],'$data'=>json_encode($data)]);
        }
        return [$fileName, $resData];
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
    function getPbcpartyCsrcChufaDetail($id,$file,$name)
    {
        $postData = ['id' => $id];
        $docType = 'pbcparty_csrc_chufa';
        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);
        $data = $res[$docType]['0'];
        if(empty($data)){
            file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
            return [];
        }

        $insertData = [
            $name,
            $data['eventName'],
            $data['caseNo'],
            $data['eventDesc'],
            $data['eventYiju'],
            $data['company'],
            $data['legalRepresentative'],
            $data['authority'],
            $data['eventResult'],
            date('Y-m-d',$data['sortTime']/1000),
            date('Y-m-d',$data['postTime']/1000),
            $data['pname'],
            $data['title'],
            $data['body']
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        return $insertData;
    }

    /**
     * 法海 - 证监会许可批复等级
     */
    public function getfhGetPbcpartyCsrcXkpf($entNames){
        $fileName = date('YmdHis', time()) . '证监会许可批复等级.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '文书号',
            '许可事项',
            '许可机关/管理机关',
            '许可时间',
            '公告日期/有效期起',
            '发布时间',
            '被许可人',
            '信息发布标题',
            '源码/正文',
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getPbcpartyCsrcXkpf($ent['entName'],1);
            if(empty($data['pbcparty_csrc_xkpfList'])) {
                file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
                continue;
            }
            if(isset($data['totalPageNum']) && $data['totalPageNum']>1){
                for($i=2;$i<=$data['totalPageNum'];$i++){
                    $data2 = $this->getPbcpartyCsrcXkpf($ent['entName'],$i);
                    $data['pbcparty_csrc_xkpfList'] = array_merge($data['pbcparty_csrc_xkpfList'],$data2['pbcparty_csrc_xkpfList']);
                }
            }
            foreach ($data['pbcparty_csrc_xkpfList'] as $datum) {
                $resData[] = $this->getPbcpartyCsrcXkpfDetail($datum['entryId'],$file,$ent['entName']);
            }
        }
        return [$fileName, $resData];
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
    function getPbcpartyCsrcXkpfDetail($id,$file,$name)
    {
        $postData = ['id' => $id];
        $docType = 'pbcparty_csrc_xkpf';
        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);
        $data = $res[$docType]['0'];
        if(empty($data)){
            file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
            return [];
        }

        $insertData = [
            $name,
            $data['caseNo'],
            $data['eventName'],
            $data['authority'],
            date('Y-m-d',$data['sortTime']/1000),
            date('Y-m-d',$data['startTime']/1000),
            date('Y-m-d',$data['postTime']/1000),
            $data['pname'],
            $data['title'],
            $data['body'],
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        return $insertData;
    }

    /**
     * 法海 - 外汇局处罚
     */
    public function fhGetSafeChufa($entNames){
        $fileName = date('YmdHis', time()) . '外汇局处罚.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '处罚文书文号',
            '注册地',
            '查处机构',
            '违规行为',
            '处罚依据',
            '处罚结果',
            '机构代码',
            '处罚金额（单位：万元人民币）',
            '处罚时间',
            '违规主体名称',
            '源码/正文',
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getSafeChufa($ent['entName'],1);
            if(empty($data['safe_chufaList'])) {
                file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
                continue;
            }
            if(isset($data['totalPageNum']) && $data['totalPageNum']>1){
                for($i=2;$i<=$data['totalPageNum'];$i++){
                    $data2 = $this->getSafeChufa($ent['entName'],$i);
                    $data['safe_chufaList'] = array_merge($data['safe_chufaList'],$data2['safe_chufaList']);
                }
            }
            foreach ($data['safe_chufaList'] as $datum) {
                $resData[] = $this->getSafeChufaDetail($datum['entryId'],$file,$ent['entName']);
            }
        }
        return [$fileName, $resData];
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
    function getSafeChufaDetail($id,$file,$name)
    {
        $postData = ['id' => $id];
        $docType = 'safe_chufa';
        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);
        $data = $res[$docType]['0'];
        if(empty($data)){
            file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
            return [];
        }

        $insertData = [
            $name,
            $data['caseNo'],
            $data['address'],
            $data['authority'],
            $data['caseCause'],
            $data['yiju'],
            $data['eventResult'],
            $data['companyNo'],
            $data['money'],
            date('Y-m-d',$data['sortTime']/1000),
            $data['pname'],
            $data['body'],
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        return $insertData;
    }

    /**
     * 法海 - 外汇局许可
     */
    public function fhGetSafeXuke($entNames){
        $fileName = date('YmdHis', time()) . '外汇局许可.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '行政许可决定书文号',
            '注册地',
            '审批部门名称',
            '项目名称',
            '许可事项',
            '设定依据',
            '机构代码',
            '托管行',
            '累计批准额度',
            '许可时间',
            '被许可当事人',
            '源码/正文'
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getSafeXuke($ent['entName'],1);
            if(empty($data['safe_chufaList'])) {
                file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
                continue;
            }
            if(isset($data['totalPageNum']) && $data['totalPageNum']>1){
                for($i=2;$i<=$data['totalPageNum'];$i++){
                    $data2 = $this->getSafeXuke($ent['entName'],$i);
                    $data['safe_xukeList'] = array_merge($data['safe_xukeList'],$data2['safe_xukeList']);
                }
            }
            foreach ($data['safe_xukeList'] as $datum) {
                $resData[] = $this->getSafeXukeDetail($datum['entryId'],$file,$ent['entName']);
            }
        }
        return [$fileName, $resData];
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
    function getSafeXukeDetail($id,$file,$name)
    {
        $postData = ['id' => $id];
        $docType = 'safe_xuke';
        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);
        $data = $res[$docType]['0'];
        if(empty($data)){
            file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
            return [];
        }

        $insertData = [
            $name,
            $data['caseNo'],
            $data['address'],
            $data['authority'],
            $data['eventName'],
            $data['eventType'],
            $data['yiju'],
            $data['companyNo'],
            $data['bank'],
            $data['money'],
            date('Y-m-d',$data['sortTime']/1000),
            $data['pname'],
            $data['body'],
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        return $insertData;
    }
}