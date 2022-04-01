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
            '数据类型',
            '事件名称'
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getSatpartyChufa($ent['entName'], 1);
            if (empty($data['satparty_chufaList'])) {
                continue;
            }
            if (isset($data['totalPageNum']) && $data['totalPageNum'] > 1) {
                for ($i = 2; $i <= $data['totalPageNum']; $i++) {
                    $data2 = $this->getSatpartyChufa($ent['entName'], 1);
                    $data['satparty_chufaList'] = array_merge($data['satparty_chufaList'], $data2['satparty_chufaList']);
                }
            }
            foreach ($data['satparty_chufaList'] as $datum) {
                dingAlarm('涉税处罚公示',['$datum'=>json_encode($datum),'entName'=>$ent['entName']]);
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
        dingAlarm('涉税处罚公示详情',['$data'=>json_encode($res),'$id'=>$id]);
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
            $data['dataType'],
            $data['eventName']
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        return $insertData;
    }

    /*
     * 法海 - 欠税公告
     */
    public function fhGetSatpartyQs($entNames){
        foreach ($entNames as $ent) {
            $data = $this->getSatpartyQs($ent['entName'], 1);
            dingAlarm('欠税公告',['$data'=>json_encode($data)]);
        }
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
    function getSatpartyQsDetail($id)
    {
        $postData = ['id' => $id];

        $docType = 'satparty_qs';

        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);
        dingAlarm('欠税公告详情',['$res'=>json_encode($res)]);
    }
}