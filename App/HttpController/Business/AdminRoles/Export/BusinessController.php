<?php

namespace App\HttpController\Business\AdminRoles\Export;

use App\HttpController\Business\AdminRoles\User\UserController;
use App\HttpController\Business\Api\TaoShu\TaoShuController;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\TaoShu\TaoShuService;
use EasySwoole\Pool\Manager;

class BusinessController  extends UserController
{
    /**
     * 陶数导出多个公司的基本信息
     */
    public function taoshuRegisterInfo($entNames)
    {
        $fileName = date('YmdHis', time()) . '企业基本信息.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $insertData = [
            '公司名称', '企业名称', '曾用名', '统一社会信用代码', '法定代表人', '成立日期', '经营状态', '注册资本', '注册资本币种', '地址', '企业类型',
            '经营业务范围', '登记机关', '经营期限自', '经营期限至', '核准日期', '死亡日期', '吊销日期', '注销日期', '地理坐标',
            '行业领域', '行业领域代码', '省份', '组织机构代码', '企业英文名', '企业官网'
        ];
        $res = file_put_contents($file, implode(',', $insertData) . PHP_EOL, FILE_APPEND);

        $data = [];
        foreach ($entNames as $ent) {
            $postData = ['entName' => $ent['entName']];
            $res = (new TaoShuService())->post($postData, 'getRegisterInfo');
            $TaoShuController = new TaoShuController();
            $res = $TaoShuController->checkResponse($res, false);
            if (!is_array($res)) {
                file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
                continue;
            }

            if ($res['code'] == 200 || !empty($res['result'])) {
                //2018年营业收入区间
                $mysql = CreateConf::getInstance()->getConf('env.mysqlDatabase');
                try {
                    $obj = Manager::getInstance()->get($mysql)->getObj();
                    $obj->queryBuilder()->where('entName', $ent['entName'])->get('qiyeyingshoufanwei');
                    $range = $obj->execBuilder();
                    Manager::getInstance()->get($mysql)->recycleObj($obj);
                } catch (\Throwable $e) {
                    $this->writeErr($e, 'getRegisterInfo');
                    $range = [];
                }

                $vendinc = [];

                foreach ($range as $one) {
                    $vendinc[] = $one;
                }

                !empty($vendinc) ?: $vendinc = '';
                $res['result'][0]['VENDINC'] = $vendinc;
                $re = $res['result']['0'];
                $insertData = [
                    $ent['entName'],
                    $re['ENTNAME'],
                    $re['OLDNAME'],
                    $re['SHXYDM'],
                    $re['FRDB'],
                    $re['ESDATE'],
                    $re['ENTSTATUS'],
                    $re['REGCAP'],
                    $re['REGCAPCUR'],
                    $re['DOM'],
                    $re['ENTTYPE'],
                    $re['OPSCOPE'],
                    $re['REGORG'],
                    $re['OPFROM'],
                    $re['OPTO'],
                    $re['APPRDATE'],
                    $re['ENDDATE'],
                    $re['REVDATE'],
                    $re['CANDATE'],
                    $re['JWD'],
                    $re['INDUSTRY'],
                    $re['INDUSTRY_CODE'],
                    $re['PROVINCE'],
                    $re['ORGID'],
                    $re['ENGNAME'],
                    $re['WEBSITE'],
                ];
                file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
                $data[] = $insertData;
            }
            file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
        }
        return [$fileName, $data];
    }


    /**
     * 陶数导出企业经营异常信息
     */
    public function taoshuGetOperatingExceptionRota($entNames)
    {
        $fileName = date('YmdHis', time()) . '企业经营异常信息.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $insertData = [
            '公司名称', '列入经营异常名录原因', '列入日期', '作出决定机关（列入）', '移出经营异常名录原因', '移出日期', '作出决定机关（移出）'
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        $data = [];
        foreach ($entNames as $ent) {
            $postData = [
                'entName' => $ent['entName'],
            ];
            $res = (new TaoShuService())->post($postData, 'getOperatingExceptionRota');
            if(empty($res['RESULTDATA'])) {
                file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
                continue;
            }
            foreach ($res['RESULTDATA'] as $re) {
                $insertData = [
                    $ent['entName'],
                    $re['REASONIN'],
                    $re['DATEIN'],
                    $re['REGORGIN'],
                    $re['REASONOUT'],
                    $re['DATEOUT'],
                    $re['REGORGOUT']
                ];
                file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
                $data[] = $insertData;
            }
        }

        return [$fileName, $data];
    }

    /**
     * 导出陶数股东信息
     */
    public function taoshuGetShareHolderInfo($entNames)
    {
        $fileName = date('YmdHis', time()) . '企业股东信息.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $insertData = [
            '公司名称', '股东名称', '统一社会信用代码', '股东类型', '认缴出资额', '出资币种', '出资比例', '出资时间'
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        $data = [];
        foreach ($entNames as $ent) {
            $entName = $ent['entName'];
            list($data1, $totalPage) = $this->getShareHolderInfo($entName, 1);
            if ($totalPage > 1) {
                for ($i = 2; $i <= $totalPage; $i++) {
                    list($data2, $totalPage2) = $this->getShareHolderInfo($entName, $i);
                    $data1 = array_merge($data2, $data1);
                }
            }
            if(empty($data1)) {
                file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
                continue;
            }
            foreach ($data1 as $re) {
                $insertData = [
                    $entName,
                    $re['INV'],
                    $re['SHXYDM'],
                    $re['INVTYPE'],
                    $re['SUBCONAM'],
                    $re['CONCUR'],
                    $re['CONRATIO'],
                    $re['CONDATE'],
                ];
                file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
                $data[] = $insertData;
            }
        }
        return [$fileName, $data];
    }

    public function getShareHolderInfo($entName, $pageNo = 1)
    {
        $postData = [
            'entName' => $entName,
            'pageNo' => $pageNo,
            'pageSize' => 100,
        ];

        $res = (new TaoShuService())->post($postData, 'getShareHolderInfo');
        $TaoShuController = new TaoShuController();
        $res = $TaoShuController->checkResponse($res, false);
        if (!is_array($res)) return [];
        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                $one['CONRATIO'] = formatPercent($one['CONRATIO']);
            }
            unset($one);
        }
        return [$res['result'], $res['paging']['totalPage']];
    }

    /**
     * 陶数 - 导出企业主要管理人
     */
    public function tsGetMainManagerInfo($entNames){
        $fileName = date('YmdHis', time()) . '企业主要管理人.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '姓名',
            '职务',
            '是否法定代表人',
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getMainManagerInfo($ent['entName'],1);
            if(empty($data['RESULTDATA'])) {
                file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
                continue;
            }
            $dataList = $data['RESULTDATA'];
            if(isset($data['PAGEINFO']['TOTAL_PAGE']) && $data['PAGEINFO']['TOTAL_PAGE']>1){
                for($i=2;$i<=$data['PAGEINFO']['TOTAL_PAGE'];$i++){
                    $data2 = $this->getMainManagerInfo($ent['entName'],1);
                    $dataList = array_merge($dataList,$data2['RESULTDATA']);
                }
            }
            foreach ($dataList as $datum) {
                $resData[] = $insertData = [
                    $ent['entName'],
                    $datum['NAME'],
                    $datum['POSITION'],
                    $datum['ISFRDB'],
                ];
//                dingAlarm('企业主要管理人明细',['$data'=>json_encode($resData)]);
                file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
            }
//            dingAlarm('企业主要管理人',['$entName'=>$ent['entName'],'$data'=>json_encode($resData)]);
        }
        return [$fileName, $resData];
    }

    public function getMainManagerInfo($entName,$page){
        $postData = [
            'entName' => $entName,
            'pageNo' => $page,
            'pageSize' => 10,
        ];
        return (new TaoShuService())->post($postData, 'getMainManagerInfo');
    }

    /**
     * 陶数-企业分支机构
     */
    public function tsGetBranchInfo($entNames){
        $fileName = date('YmdHis', time()) . '企业分支机构.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '总公司',
            '分支机构名称',
            '统一社会信用代码',
            '负责人',
            '成立日期',
            '经营状态',
            '登记地省份',
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getBranchInfo($ent['entName'],1);
            if(empty($data['RESULTDATA'])) {
                file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
                continue;
            }
            if(isset($data['PAGEINFO']['TOTAL_PAGE']) && $data['PAGEINFO']['TOTAL_PAGE']>1){
                for($i=2;$i<=$data['PAGEINFO']['TOTAL_PAGE'];$i++){
                    $data2 = $this->getBranchInfo($ent['entName'],1);
                    $data['RESULTDATA'] = array_merge($data['RESULTDATA'],$data2['RESULTDATA']);
                }
            }
            foreach ($data['RESULTDATA'] as $datum) {
                $insertData = [
                    $ent['entName'],
                    $datum['ENTNAME'],
                    $datum['SHXYDM'],
                    $datum['FRDB'],
                    $datum['ESDATE'],
                    $datum['ENTSTATUS'],
                    $datum['PROVINCE']
                ];
                file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
                $resData[] = $insertData;
            }
//            dingAlarm('企业分支机构',['$entName'=>$ent['entName'],'$data'=>json_encode($data)]);
        }
        return [$fileName, $resData];
    }

    public function getBranchInfo($entName,$pageNo){
        $postData = [
            'entName' => $entName,
            'pageNo' => $pageNo,
            'pageSize' => 10,
        ];

        return (new TaoShuService())->post($postData, 'getBranchInfo');
    }

    /**
     * 陶数 - 企业对外投资
     */
    public function tsGetInvestmentAbroadInfo($entNames){

        $fileName = date('YmdHis', time()) . '企业对外投资.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '总公司',
            '被投企业名称',
            '统一社会信用代码',
            '成立日期',
            '经营状态',
            '注册资本',
            '认缴出资额',
            '出资币种',
            '出资比例',
            '出资时间'
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getInvestmentAbroadInfo($ent['entName'],1);
//            dingAlarm('企业对外投资$data',['$data'=>json_encode($data)]);
            if(empty($data['RESULTDATA'])) {
                file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
                continue;
            }
            if(isset($data['PAGEINFO']['TOTAL_PAGE']) && $data['PAGEINFO']['TOTAL_PAGE']>1){
                for($i=2;$i<=$data['PAGEINFO']['TOTAL_PAGE'];$i++){
                    $data2 = $this->getInvestmentAbroadInfo($ent['entName'],1);
                    $data['RESULTDATA'] = array_merge($data['RESULTDATA'],$data2['RESULTDATA']);
                }
            }
            foreach ($data['RESULTDATA'] as $datum) {
                $insertData = [
                    $ent['entName'],
                    $datum['ENTNAME'],
                    $datum['SHXYDM'],
                    $datum['ESDATE'],
                    $datum['ENTSTATUS'],
                    $datum['REGCAP'],
                    $datum['SUBCONAM'],
                    $datum['CONCUR'],
                    formatPercent($datum['CONRATIO']),
                    $datum['CONDATE']??''
                ];
                file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
                $resData[] = $insertData;
            }
//            dingAlarm('企业对外投资',['$entName'=>$ent['entName'],'$data'=>json_encode($data)]);
        }
        return [$fileName, $resData];
    }
    public function getInvestmentAbroadInfo($entName,$pageNo){
        $postData = [
            'entName' => $entName,
            'pageNo' => $pageNo,
            'pageSize' => 10,
        ];
        return (new TaoShuService())->post($postData, 'getInvestmentAbroadInfo');
    }

    /**
     * 陶数 - 企业变更信息
     */
    public function tsGetRegisterChangeInfo($entNames){

        $fileName = date('YmdHis', time()) . '企业变更信息.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '总公司',
            '变更事项',
            '变更前内容',
            '变更后内容',
            '变更时间',
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getRegisterChangeInfo($ent['entName'],1);
//            dingAlarm('企业变更信息',['$entName'=>$ent['entName'],'$data'=>json_encode($data)]);
            if(empty($data['result'])) {
                file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
                continue;
            }
            if(isset($data['paging']['totalPage']) && $data['paging']['totalPage']>1){
                for($i=2;$i<=$data['paging']['totalPage'];$i++){
                    $data2 = $this->getRegisterChangeInfo($ent['entName'],1);
                    $data['result'] = array_merge($data['result'],$data2['result']);
                }
            }
            foreach ($data['result'] as $datum) {
                $insertData = [
                    $ent['entName'],
                    $datum['ALTITEM'],
                    $datum['ALTBE'],
                    $datum['ALTAF'],
                    $datum['ALTDATE'],
                ];
                file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
                $resData[] = $insertData;
            }

        }
        return [$fileName, $resData];
    }

    public function getRegisterChangeInfo($entName,$pageNo){
        $postData = [
            'entName' => $entName,
            'pageNo' => $pageNo,
            'pageSize' => 10,
        ];
        return  (new TaoShuService())->setCheckRespFlag(true)->post($postData, 'getRegisterChangeInfo');
    }
}