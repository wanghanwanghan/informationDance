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
            $caseCauseT = !empty($v['caseCauseT'])?$v['caseCauseT']:'';
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

    /**
     * 法海 - 开庭公告
     */
    public function fhGetKtgg($entNames){
        $fileName = date('YmdHis', time()) . '开庭公告.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
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
    function fhgetKtggDetail($id,$file,$name)
    {
        $postData = ['id' => $id];
        $docType = 'ktgg';
        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);
        dingAlarm('开庭公告详情',['$data'=>json_encode($res)]);
        $data = $res['ktgg']['0'];
        if(empty($data)){
            return [];
        }
        $caseCauseT = [];
        $pname = [];
        $partyTitleT = [];
        $partyPositionT = [];
        $partyPositionTMap = ['p'=>'原告','d'=>'被告','t'=>'第三人','u'=>'当事人'];
        foreach ($data['partys'] as $v) {
            $caseCauseT = !empty($v['caseCauseT'])?$v['caseCauseT']:'';
            $pname[] = $v['pname'];
            $partyTitleT[] = $v['partyTitleT'];
            $partyPositionT[] = $partyPositionTMap[$v['partyPositionT']]??$v['partyPositionT'];
        }
        $insertData = [
            $name,
            $data['body'],
            $data['caseNo'],
            $data['court'],
            $data['courtroom'],
            $data['judge'],
            $data['organizer'],
            date('Y-m-d H:i:s',$data['sortTime']),
            $data['title'],
            implode('  ',$caseCauseT),
            implode('  ',$pname),
            implode('  ',$partyTitleT),
            implode('  ',$partyPositionT),
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        return $insertData;
    }

    /**
     * 法海- 法院公告
     */
    public function fhGetFygg($entNames){
        $fileName = date('YmdHis', time()) . '法院公告.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '内容',//body
            '案号',//caseNo
            '法院',//court
            '版面',//layout
            '立案时间',//sortTime
            '标题',//title
            '案由',
            '当事人名称',
            '当前称号',
            '诉讼地位（原审）',
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getFygg($ent['entName'],1);
            dingAlarm('法院公告',['$entName'=>$ent['entName'],'$data'=>json_encode($data)]);
            if(empty($data['fyggList'])) continue;
            if(isset($data['totalPageNum']) && $data['totalPageNum']>1){
                for($i=2;$i<=$data['totalPageNum'];$i++){
                    $data2 = $this->getFygg($ent['entName'],1);
                    $data['fyggList'] = array_merge($data['fyggList'],$data2['fyggList']);
                }
            }
            foreach ($data['fyggList'] as $datum) {
                $resData[] = $this->fhgetfyggDetail($datum['entryId'],$file,$ent['entName']);
            }
        }
        return [$fileName, $resData];
    }

    public function getFygg($entName,$page){
        $docType = 'fygg';
        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => 10,
        ];
        return (new FaYanYuanService())->getList(CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'sifa', $postData);
    }
    //开庭公告详情
    function fhgetfyggDetail($id,$file,$name)
    {
        $postData = ['id' => $id];
        $docType = 'fygg';
        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);
        dingAlarm('法院公告详情',['$data'=>json_encode($res)]);
        $data = $res['fygg']['0'];
        if(empty($data)){
            return [];
        }
        $caseCauseT = [];
        $pname = [];
        $partyTitleT = [];
        $partyPositionT = [];
        $partyPositionTMap = ['p'=>'原告','d'=>'被告','t'=>'第三人','u'=>'当事人'];
        foreach ($data['partys'] as $v) {
            $caseCauseT = !empty($v['caseCauseT'])?$v['caseCauseT']:'';
            $pname[] = $v['pname'];
            $partyTitleT[] = $v['partyTitleT'];
            $partyPositionT[] = $partyPositionTMap[$v['partyPositionT']]??$v['partyPositionT'];
        }
        $insertData = [
            $name,
            $data['body'],
            $data['caseNo'],
            $data['court'],
            $data['layout'],
            date('Y-m-d H:i:s',$data['sortTime']),
            $data['title'],
            implode('  ',$caseCauseT),
            implode('  ',$pname),
            implode('  ',$partyTitleT),
            implode('  ',$partyPositionT),
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        return $insertData;
    }

    /**
     * 法海 - 执行公告
     */
    public function fhGetZxgg($entNames){
        $fileName = date('YmdHis', time()) . '执行公告.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '地址',
            '内容',
            '案号',
            '终本日期',
            '法院名称',
            '申请人',
            '立案日期',
            '标题',
            '依据文书',
            '依据单位',
            '当事人',
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getZxgg($ent['entName'],1);
            dingAlarm('执行公告',['$entName'=>$ent['entName'],'$data'=>json_encode($data)]);
            if(empty($data['zxggList'])) continue;
            if(isset($data['totalPageNum']) && $data['totalPageNum']>1){
                for($i=2;$i<=$data['totalPageNum'];$i++){
                    $data2 = $this->getZxgg($ent['entName'],1);
                    $data['zxggList'] = array_merge($data['zxggList'],$data2['zxggList']);
                }
            }
            foreach ($data['zxggList'] as $datum) {
                $resData[] = $this->getZxggDetail($datum['entryId'],$file,$ent['entName']);
            }
        }
        return [$fileName, $resData];
    }

    public function getZxgg($entName,$page){
        $docType = 'zxgg';
        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => 10,
        ];
        $res = (new FaYanYuanService())->getList(CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl'). 'sifa', $postData);
        foreach ($res['zxggList'] as &$one) {
            if (!isset($one['body'])) continue;
            $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
        }
        unset($one);
        return $res;
    }
    //执行公告详情
    public function getZxggDetail($id,$file,$name)
    {
        $postData = ['id' => $id];
        $docType = 'zxgg';
        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);
        dingAlarm('执行公告详情',['$res'=>json_encode($res)]);
        $data = $res['zxgg']['0'];
        if(empty($data)){
            return [];
        }
        $partys = [];
        foreach ($data['partys'] as $v) {
            $partys[] = '当事人名称:'.$v['pname'].';主体类型:'.$v['partyType'].';身份证号码:'.$v['idcardNo'].';执行金额:'.$v['execMoney'].';案件状态:'.$v['caseStateT'];
        }
        $insertData = [
            $name,
            $data['address'],
            $data['body'],
            $data['caseNo'],
            $data['closeDate'],
            $data['court'],
            $data['proposer'],
            $data['sortTime'],
            $data['title'],
            $data['yjCode'],
            $data['yjdw'],
            implode('；   ',$partys)
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        return $insertData;
    }

    /**
     * 法海 - 失信公告
     */
    public function fhGetShixin($entNames){
        $fileName = date('YmdHis', time()) . '失信公告.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '内容',//body
            '案号',//caseNo
            '法院',//court
            '发布时间',//postTime
            '立案时间',//sortTime
            '义务',//yiwu
            '依据文号',//yjCode
            '依据单位',//yjdw
            '当事人'
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getShixin($ent['entName'],1);
            dingAlarm('失信公告',['$entName'=>$ent['entName'],'$data'=>json_encode($data)]);
            if(empty($data['shixinList'])) continue;
            if(isset($data['totalPageNum']) && $data['totalPageNum']>1){
                for($i=2;$i<=$data['totalPageNum'];$i++){
                    $data2 = $this->getShixin($ent['entName'],1);
                    $data['shixinList'] = array_merge($data['shixinList'],$data2['shixinList']);
                }
            }
            foreach ($data['shixinList'] as $datum) {
                $resData[] = $this->getShixinDetail($datum['entryId'],$file,$ent['entName']);
            }
        }
        return [$fileName, $resData];
    }
    public function getShixin($entName,$page){
        $docType = 'shixin';
        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => 10,
        ];
        return (new FaYanYuanService())->getList(CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'sifa', $postData);
    }

    //失信公告详情
    function getShixinDetail($id,$file,$name)
    {
        $postData = ['id' => $id];
        $docType = 'shixin';
        $res = (new FaYanYuanService())->getDetail(CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl') . $docType, $postData);
        dingAlarm('失信公告详情',['$data'=>json_encode($res)]);
        $data = $res['shixin']['0'];
        if(empty($data)){
            return [];
        }
        $partys = [];
        foreach ($data['partys'] as $v) {
            $partys[] = '当事人名称:'.$v['pname'].';具体情形:'.$v['jtqx'].';主体类型:'.$v['partyType'].';涉案金额:'.$v['money'].
                ';履行情况:'.$v['lxqkT'].';省份:'.$v['province'].';身份证号码:'.$v['idcardNo'].';年龄:'.$v['age'];
        }
        $insertData = [
            $name,
            $data['body'],
            $data['caseNo'],
            $data['court'],
            date('Y-m-d H:i:s',$data['postTime']),
            date('Y-m-d H:i:s',$data['sortTime']),
            $data['yiwu'],
            $data['yjCode'],
            $data['yjdw'],
            implode('    ',$partys)
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        return $insertData;
    }
}