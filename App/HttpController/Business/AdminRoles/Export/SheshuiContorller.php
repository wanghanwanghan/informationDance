<?php

namespace App\HttpController\Business\AdminRoles\Export;

use App\Csp\Service\CspService;
use App\HttpController\Business\AdminRoles\User\UserController;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\FaYanYuan\FaYanYuanService;

class SheshuiContorller  extends UserController
{
    /**
     * 法海 - 涉税处罚公示
     */
    public function fhGetSatpartyChufa($entNames){
//        dingAlarm('涉税处罚公示',['fhGetSatpartyChufa'=>json_encode($entNames)]);
        $fileName = date('YmdHis', time()) . '涉税处罚公示.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '局（政府单位）',
            '内容',
            '事件结果',
            '企业法定代表人',
            '法人身份证号码',
            '处罚金额',
            '企业名称',
            '发布时间',
            '处罚时间',
            '税务登记号',
            '标题',
            '事件名称'
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getSatpartyChufa($ent['entName'], 1);
//            dingAlarm('涉税处罚公示',['$datum'=>json_encode($data),'entName'=>$ent['entName']]);
            if (empty($data['satparty_chufaList'])) {
                continue;
            }
            if (isset($data['totalPageNum']) && $data['totalPageNum'] > 1) {
                for ($i = 2; $i <= $data['totalPageNum']; $i++) {
                    $data2 = $this->getSatpartyChufa($ent['entName'], $i);
                    $data['satparty_chufaList'] = array_merge($data['satparty_chufaList'], $data2['satparty_chufaList']);
                }
            }
            foreach ($data['satparty_chufaList'] as $datum) {
//                dingAlarm('涉税处罚公示',['$datum'=>json_encode($datum),'entName'=>$ent['entName']]);
                $resData[] = $this->fhGetSatpartyChufaDetail($datum['entryId'], $file, $ent['entName']);
            }
        }
        return [$fileName, $resData];
    }

    /**
     * 法海 - 涉税处罚公示
     */
    public function getSatpartyChufa($entName,$page){
        $docType = 'satparty_chufa';
        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => 10,
        ];
        return (new FaYanYuanService())->getList(CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'sat', $postData);
    }

    public function fhGetSatpartyChufaDetail($id,$file,$name){
        $postData = ['id' => $id];
        $docType = 'satparty_chufa';
        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);
//        dingAlarm('涉税处罚公示详情',['$data'=>json_encode($res),'$id'=>$id]);
        $data = $res['satparty_chufa']['0'];
        if(empty($data)){
            file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
            return [];
        }
        $insertData = [
            $name,
            $data['authority'],
            $data['body'],
            $data['eventResult'],
            $data['legalRepresentative'],
            $data['lrIdcard'],
            $data['money'],
            $data['pname'],
            $data['postTime'],
            $data['sortTime'],
            $data['taxpayerId'],
            $data['title'],
            $data['eventName']
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        return $insertData;
    }

    /*
     * 法海 - 欠税公告
     */
    public function fhGetSatpartyQs($entNames){
        $fileName = date('YmdHis', time()) . '欠税公告.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '局（政府单位）',
            '内容',
            '事件名称',
            '企业法定代表人',
            '法人身份证号码',
            '欠税金额',
            '企业名称',
            '发布时间',
            '欠税时间',
            '税种',
            '税务登记号',
            '标题',
            '税务局等级'
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getSatpartyQs($ent['entName'], 1);//
//            dingAlarm('欠税公告',['$data'=>json_encode($data)]);
            if (empty($data['satparty_qsList'])) {
                continue;
            }
            if (isset($data['totalPageNum']) && $data['totalPageNum'] > 1) {
                for ($i = 2; $i <= $data['totalPageNum']; $i++) {
                    $data2 = $this->getSatpartyQs($ent['entName'], $i);
                    $data['satparty_qsList'] = array_merge($data['satparty_qsList'], $data2['satparty_qsList']);
                }
            }
            foreach ($data['satparty_qsList'] as $datum) {
//                dingAlarm('涉税处罚公示',['$datum'=>json_encode($datum),'entName'=>$ent['entName']]);
                $resData[] = $this->fhgetSatpartyQsDetail($datum['entryId'], $file, $ent['entName']);
            }
        }
        return [$fileName, $resData];

    }
    public function getSatpartyQs($entName,$page){
        $docType = 'satparty_qs';
        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => 10,
        ];
        return (new FaYanYuanService())->getList(CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl')  . 'sat', $postData);
    }

    //欠税公告详情
    function fhgetSatpartyQsDetail($id,$file,$name)
    {
        $postData = ['id' => $id];
        $docType = 'satparty_qs';
        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);
//        dingAlarm('欠税公告详情',['$res'=>json_encode($res)]);
        $data = $res['satparty_qs']['0'];
        if(empty($data)){
            file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
            return [];
        }
        $insertData = [
            $name,
            $data['authority'],
            $data['body'],
            $data['eventName'],
            $data['legalRepresentative'],
            $data['lrIdcard'],
            $data['money'],
            $data['pname'],
            $data['postTime'],
            $data['sortTime'],
            $data['taxCategory'],
            $data['taxpayerId'],
            $data['title'],
            $data['authorityRank']
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        return $insertData;
    }

    /*
     * 税务非正常户公示
     */
    public function fhGetSatpartyFzc($entNames){
        $fileName = date('YmdHis', time()) . '税务非正常户公示.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '局（政府单位）',
            '内容',
            '事件名称',
            '事件结果',
            '企业法定代表人',
            '法人身份证号码',
            '企业名称',
            '发布时间',
            '认定时间',
            '税务登记号',
            '标题',
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getSatpartyFzc($ent['entName'], 1);//
//            dingAlarm('税务非正常户公示',['$data'=>json_encode($data)]);
            if (empty($data['satparty_fzcList'])) {
                continue;
            }
            if (isset($data['totalPageNum']) && $data['totalPageNum'] > 1) {
                for ($i = 2; $i <= $data['totalPageNum']; $i++) {
                    $data2 = $this->getSatpartyFzc($ent['entName'], $i);
                    $data['satparty_fzcList'] = array_merge($data['satparty_fzcList'], $data2['satparty_fzcList']);
                }
            }
            foreach ($data['satparty_fzcList'] as $datum) {
//                dingAlarm('税务非正常户公示',['$datum'=>json_encode($datum),'entName'=>$ent['entName']]);
                $resData[] = $this->fhGetSatpartyFzcDetail($datum['entryId'], $file, $ent['entName']);
            }
        }
        return [$fileName, $resData];
    }
    public function getSatpartyFzc($entName,$page){
        $docType = 'satparty_fzc';
        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => 10,
        ];
        return (new FaYanYuanService())->getList(CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'sat', $postData);
    }

    //税务非正常户公示详情
    function fhGetSatpartyFzcDetail($id,$file,$name)
    {
        $postData = ['id' => $id];
        $docType = 'satparty_fzc';
        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);
//        dingAlarm('税务非正常户公示详情',['$res'=>json_encode($res)]);
        $data = $res['satparty_fzc']['0'];
        if(empty($data)){
            file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
            return [];
        }
        $insertData = [
            $name,
            $data['authority'],
            $data['body'],
            $data['eventName'],
            $data['eventResult'],
            $data['legalRepresentative'],
            $data['lrIdcard'],
            $data['pname'],
            $data['postTime'],
            $data['sortTime'],
            $data['taxpayerId'],
            $data['title'],
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        return $insertData;
    }

    /*
     * 税务许可
     */
    public function fhGetSatpartyXuke($entNames){
        $fileName = date('YmdHis', time()) . '税务许可.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '局（政府单位）',
            '内容',
            '事件名称',
            '事件结果',
            '企业法定代表人',
            '法人身份证号码',
            '企业名称',
            '发布时间',
            '认定时间',
            '税务登记号',
            '标题',
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getSatpartyXuke($ent['entName'], 1);//
//            dingAlarm('税务许可',['$data'=>json_encode($data)]);
            if (empty($data['satparty_xukeList'])) {
                continue;
            }
            if (isset($data['totalPageNum']) && $data['totalPageNum'] > 1) {
                for ($i = 2; $i <= $data['totalPageNum']; $i++) {
                    $data2 = $this->getSatpartyXuke($ent['entName'], $i);
                    $data['satparty_xukeList'] = array_merge($data['satparty_xukeList'], $data2['satparty_xukeList']);
                }
            }
            foreach ($data['satparty_xukeList'] as $datum) {
//                dingAlarm('税务许可',['$datum'=>json_encode($datum),'entName'=>$ent['entName']]);
                $resData[] = $this->fhGetSatpartyXukeDetail($datum['entryId'], $file, $ent['entName']);
            }
        }
        return [$fileName, $resData];
    }

    public function getSatpartyXuke($entName,$page){
        $docType = 'satparty_xuke';
        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => 10,
        ];
        return (new FaYanYuanService())->getList(CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'sat', $postData);
    }

    //税务许可详情
    public function fhGetSatpartyXukeDetail($id,$file,$name)
    {
        $postData = ['id' => $id];
        $docType = 'satparty_xuke';
        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);
//        dingAlarm('税务许可详情',['$res'=>json_encode($res)]);
        $data = $res['satparty_xuke']['0'];
        if(empty($data)){
            file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
            return [];
        }
        $insertData = [
            $name,
            $data['authority'],
            $data['body'],
            $data['eventName'],
            $data['eventResult'],
            $data['legalRepresentative'],
            $data['lrIdcard'],
            $data['pname'],
            $data['postTime'],
            $data['sortTime'],
            $data['taxpayerId'],
            $data['title'],
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        return $insertData;
    }

    /*
     * 法海 - 纳税信用等级
     */
    public function fhGetSatpartyXin($entNames){
        $fileName = date('YmdHis', time()) . '纳税信用等级.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '局（政府单位）',
            '内容',
            '事件结果（级别）',
            '企业法定代表人',
            '企业名称',
            '发布时间',
            '评定时间',
            '税务登记号',
            '标题',
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getSatpartyXin($ent['entName'], 1);//
//            dingAlarm('纳税信用等级',['$data'=>json_encode($data)]);
            if (empty($data['satparty_xinList'])) {
                continue;
            }
            if (isset($data['totalPageNum']) && $data['totalPageNum'] > 1) {
                for ($i = 2; $i <= $data['totalPageNum']; $i++) {
                    $data2 = $this->getSatpartyXin($ent['entName'], $i);
                    $data['satparty_xinList'] = array_merge($data['satparty_xinList'], $data2['satparty_xinList']);
                }
            }
            foreach ($data['satparty_xinList'] as $datum) {
//                dingAlarm('纳税信用等级',['$datum'=>json_encode($datum),'entName'=>$ent['entName']]);
                $resData[] = $this->fhGetSatpartyXinDetail($datum['entryId'], $file, $ent['entName']);
            }
        }
        return [$fileName, $resData];
    }

    //纳税信用等级
    public function getSatpartyXin($entName,$page)
    {
        $docType = 'satparty_xin';
        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => 10,
        ];
        return (new FaYanYuanService())->getList(CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'sat', $postData);
    }

    //纳税信用等级详情
    public function fhGetSatpartyXinDetail($id,$file,$name)
    {
        $postData = ['id' => $id];
        $docType = 'satparty_xin';
        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);
//        dingAlarm('纳税信用等级详情',['$res'=>json_encode($res)]);
        $data = $res['satparty_xin']['0'];
        if(empty($data)){
            file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
            return [];
        }
        $insertData = [
            $name,
            $data['authority'],
            $data['body'],
            $data['eventResult'],
            $data['legalRepresentative'],
            $data['pname'],
            $data['postTime'],
            $data['sortTime'],
            $data['taxpayerId'],
            $data['title'],
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        return $insertData;
    }

    /*
     * 税务登记
     */
    public function fhGetSatpartyReg($entNames){
        $fileName = date('YmdHis', time()) . '税务登记.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '局（政府单位）',
            '内容',
            '事件名称',
            '事件结果',
            '企业法定代表人',
            '法人身份证号码',
            '企业名称',
            '发布时间',
            '评定时间',
            '税务登记号',
            '标题',
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getSatpartyReg($ent['entName'], 1);//
//            dingAlarm('税务登记',['$data'=>json_encode($data)]);
            if (empty($data['satparty_regList'])) {
                continue;
            }
            if (isset($data['totalPageNum']) && $data['totalPageNum'] > 1) {
                for ($i = 2; $i <= $data['totalPageNum']; $i++) {
                    $data2 = $this->getSatpartyReg($ent['entName'], $i);
                    $data['satparty_regList'] = array_merge($data['satparty_regList'], $data2['satparty_regList']);
                }
            }
            foreach ($data['satparty_regList'] as $datum) {
//                dingAlarm('税务登记',['$datum'=>json_encode($datum),'entName'=>$ent['entName']]);
                $resData[] = $this->fhGetSatpartyRegDetail($datum['entryId'], $file, $ent['entName']);
            }
        }
        return [$fileName, $resData];

    }
    //税务登记
    public function getSatpartyReg($entName,$page)
    {
        $docType = 'satparty_reg';
        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => 10,
        ];
        return (new FaYanYuanService())->getList(CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'sat', $postData);
    }

    //税务登记详情
    public function fhGetSatpartyRegDetail($id,$file,$name)
    {
        $postData = ['id' => $id];
        $docType = 'satparty_reg';
        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);
//        dingAlarm('税务登记详情',['$res'=>json_encode($res)]);
        $data = $res['satparty_reg']['0'];
        if(empty($data)){
            file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
            return [];
        }
        $insertData = [
            $name,
            $data['authority'],
            $data['body'],
            $data['eventName'],
            $data['eventResult'],
            $data['legalRepresentative'],
            $data['lrIdcard'],
            $data['pname'],
            $data['postTime'],
            $data['sortTime'],
            $data['taxpayerId'],
            $data['title'],
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        return $insertData;
    }

}