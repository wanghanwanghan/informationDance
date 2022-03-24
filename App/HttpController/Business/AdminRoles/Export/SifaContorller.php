<?php

namespace App\HttpController\Business\AdminRoles\Export;

use App\HttpController\Business\AdminRoles\User\UserController;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\FaYanYuan\FaYanYuanService;

class SifaContorller  extends UserController
{

    /**法海判决文书导出
     * @param $entNames
     * @return array
     */
    public function fahaiGetCpws($entNames){
        $fileName = date('YmdHis', time()) . '裁判文书.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '案号',
            '内容',
            '法院',
            '裁判文书ID',
            '审判员',
            '判决结果',
            '审结日期',
            '标题',
            '审判程序',
            '依据',
            '案由',
            '当事人名称',
            '当前称号',
            '诉讼地位（原审）',
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getCpws($ent['entName'],1);
            if(empty($data['cpwsList'])) continue;
            if(isset($data['totalPageNum']) && $data['totalPageNum']>1){
                for($i=2;$i<=$data['totalPageNum'];$i++){
                    $data2 = $this->getCpws($ent['entName'],1);
                    $data['cpwsList'] = array_merge($data['cpwsList'],$data2['cpwsList']);
                }
            }
            foreach ($data['cpwsList'] as $datum) {
                $resData[] = $this->fhgetCpwsDetail($datum['entryId'],$file,$ent['entName']);
            }
//            dingAlarm('裁判文书',['$entName'=>$ent['entName'],'$data'=>json_encode($data)]);
        }
        return [$fileName, $resData];
    }

    /**
     * 获取判决文书详情并导出
     * @param $id
     * @param $file
     * @param $name
     * @return array
     */
    public function fhgetCpwsDetail($id,$file,$name){
        $postData = ['id' => $id];
        $docType = 'cpws';
        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);
        $data = $res['cpws']['0'];
        if(empty($data)){
            return [];
        }
        $caseCauseT = [];
        $pname = [];
        $partyTitleT = [];
        $partyPositionT = [];
        $partyPositionTMap = ['p'=>'原告','d'=>'被告','t'=>'第三人','u'=>'当事人'];
        foreach ($data['partys'] as $v) {
            $caseCauseT = $v['caseCauseT'];
            $pname[] = $v['pname'];
            $partyTitleT[] = $v['partyTitleT'];
            $partyPositionT[] = $partyPositionTMap[$v['partyPositionT']]??$v['partyPositionT'];
        }
        $insertData = [
            $name,
            $data['caseNo'],
            $data['body'],
            $data['court'],
            $data['cpwsId'],
            $data['judge'],
            $data['judgeResult'],
            $data['sortTime'],
            $data['title'],
            $data['trialProcedure'],
            $data['yiju'],
            implode('  ',$caseCauseT),
            implode('  ',$pname),
            implode('  ',$partyTitleT),
            implode('  ',$partyPositionT),
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        return $insertData;
    }

    /**
     * 根据公司名称获取判决文书
     * @param $entName
     * @param $page
     * @return array|mixed|string[]
     */
    public function getCpws($entName,$page)
    {
        $docType = 'cpws';
        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => 10,
        ];
        return (new FaYanYuanService())->getList(CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'sifa', $postData);
    }

    public function fhGetKtgg($entNames){
        $fileName = date('YmdHis', time()) . '开庭公告.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '接口提供方',
            'id',
            '开庭公告ID',//ktggId
            '内容',//body
            '案号',//caseNo
            '法院',//court
            '法庭',//courtroom
            '审判员',//judge
            '组织者',//organizer
            '开庭时间',//sortTime
            '标题',//title
            '案由',
            '当事人名称',
            '当前称号',
            '诉讼地位（原审）',
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getKtgg($ent['entName'],1);
            dingAlarm('开庭公告',['$entName'=>$ent['entName'],'$data'=>json_encode($data)]);
            if(empty($data['ktggList'])) continue;
            if(isset($data['totalPageNum']) && $data['totalPageNum']>1){
                for($i=2;$i<=$data['totalPageNum'];$i++){
                    $data2 = $this->getKtgg($ent['entName'],1);
                    $data['ktggList'] = array_merge($data['ktggList'],$data2['ktggList']);
                }
            }
            foreach ($data['ktggList'] as $datum) {
                $resData[] = $this->fhgetKtggDetail($datum['entryId'],$file,$ent['entName']);
            }
//            dingAlarm('裁判文书',['$entName'=>$ent['entName'],'$data'=>json_encode($data)]);
        }
        return [$fileName, $resData];
    }

    public function getKtgg($entName,$page){
        $docType = 'ktgg';
        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => 10,
        ];
        return (new FaYanYuanService())->getList(CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'sifa', $postData);
    }

    //开庭公告详情
    function fhgetKtggDetail($id)
    {
        $postData = ['id' => $id];
        $docType = 'ktgg';
        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);
        dingAlarm('裁判文书详情',['$data'=>json_encode($res)]);
        return '';
        $data = $res['cpws']['0'];
        if(empty($data)){
            return [];
        }
        $caseCauseT = [];
        $pname = [];
        $partyTitleT = [];
        $partyPositionT = [];
        $partyPositionTMap = ['p'=>'原告','d'=>'被告','t'=>'第三人','u'=>'当事人'];
        foreach ($data['partys'] as $v) {
            $caseCauseT = $v['caseCauseT'];
            $pname[] = $v['pname'];
            $partyTitleT[] = $v['partyTitleT'];
            $partyPositionT[] = $partyPositionTMap[$v['partyPositionT']]??$v['partyPositionT'];
        }
        $insertData = [
            $name,
            $data['caseNo'],
            $data['body'],
            $data['court'],
            $data['cpwsId'],
            $data['judge'],
            $data['judgeResult'],
            $data['sortTime'],
            $data['title'],
            $data['trialProcedure'],
            $data['yiju'],
            implode('  ',$caseCauseT),
            implode('  ',$pname),
            implode('  ',$partyTitleT),
            implode('  ',$partyPositionT),
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        return $insertData;
    }
}