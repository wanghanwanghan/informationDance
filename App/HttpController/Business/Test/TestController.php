<?php

namespace App\HttpController\Business\Test;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\MoveOut\MoveOutService;
use App\HttpController\Service\QianQi\QianQiService;
use EasySwoole\Pool\Manager;

class TestController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return true;
    }

    function test()
    {
        MoveOutService::getInstance()->updateDatabase();
        $this->writeJson(random_int(100, 999));
    }

    //产品标准页面用
    function product()
    {
        //地图
        try {
            $mysqlObj = Manager::getInstance()
                ->get(CreateConf::getInstance()->getConf('env.mysqlDatabaseMZJD'))
                ->getObj();
            $sql = 'SELECT * FROM qyxx_copy1';
            $list = $mysqlObj->rawQuery($sql);
            $list = obj2Arr($list);
            foreach ($list as $val) {
                $map[$val['XZQH_NAME']] = $val['num'];
            }
        } catch (\Throwable $e) {
            $this->writeErr($e, __FUNCTION__);
        } finally {
            Manager::getInstance()
                ->get(CreateConf::getInstance()->getConf('env.mysqlDatabaseMZJD'))
                ->recycleObj($mysqlObj);
        }

        //标准一级分类和个数
        try {
            $mysqlObj = Manager::getInstance()
                ->get(CreateConf::getInstance()->getConf('env.mysqlDatabaseMZJD'))
                ->getObj();
            $sql = 'select JJHYDM,count(1) as num from cpxx where JJHYDM is not null group by JJHYDM';
            $list = $mysqlObj->rawQuery($sql);
            $list = obj2Arr($list);
        } catch (\Throwable $e) {
            $this->writeErr($e, __FUNCTION__);
            $list = [];
        } finally {
            Manager::getInstance()
                ->get(CreateConf::getInstance()->getConf('env.mysqlDatabaseMZJD'))
                ->recycleObj($mysqlObj);
        }

        $JJHYDM = $tmp = [];

        foreach ($list as $index => $val) {
            if (preg_match('/[\xe0-\xef][\x80-\xbf]/', $val['JJHYDM'])) {
                if (isset($JJHYDM[trim($val['JJHYDM'])])) {
                    $JJHYDM[trim($val['JJHYDM'])] += $val['num'];
                } else {
                    $JJHYDM[trim($val['JJHYDM'])] = $val['num'];
                }
            }
        }

        arsort($JJHYDM);

        foreach ($JJHYDM as $key => $val) {
            $tmp[] = ['name' => $key, 'num' => $val];
        }

        return $this->writeJson(200, null, [
            'k' => array_keys($map), 'v' => array_values($map), 'JJHYDM' => $tmp
        ]);
    }

    function caiwu()
    {
        $entList = $this->request()->getRequestParam('entList') ?? '';

        $entList = str_replace('，', ',', $entList);

        if (empty($entList)) return $this->writeJson(201, null, null, '公司名称不能是空');

        $entList = explode(',', $entList);

        $entList = array_filter($entList);

        if (empty($entList)) return $this->writeJson(201, null, null, '公司名称不能是空');

        $temp = [];

        foreach ($entList as $entName) {
            $entName = trim($entName);
            $res = (new QianQiService())
                ->setCheckRespFlag(true)
                ->getThreeYears(['entName' => $entName]);
            if ($res['code'] === 200 && !empty($res['result'])) {
                foreach ($res['result'] as $key => $val) {
                    if (empty($val)) {
                        $temp[] = [$entName, $key, '无数据', '无数据', '无数据'];
                    } else {
                        $range = (new QianQiService())->wordToRange($val);
                        $temp[] = [$entName, $key, 'ASSGRO_REL 资产总额', $val['ASSGRO_REL'], $range['ASSGRO_REL']];
                        $temp[] = ['', '', 'LIAGRO_REL 负债总额', $val['LIAGRO_REL'], $range['LIAGRO_REL']];
                        $temp[] = ['', '', 'VENDINC_REL 营业总收入', $val['VENDINC_REL'], $range['VENDINC_REL']];
                        $temp[] = ['', '', 'MAIBUSINC_REL 主营业务收入', $val['MAIBUSINC_REL'], $range['MAIBUSINC_REL']];
                        $temp[] = ['', '', 'PROGRO_REL 利润总额', $val['PROGRO_REL'], $range['PROGRO_REL']];
                        $temp[] = ['', '', 'NETINC_REL 净利润', $val['NETINC_REL'], $range['NETINC_REL']];
                        $temp[] = ['', '', 'RATGRO_REL 纳税总额', $val['RATGRO_REL'], $range['RATGRO_REL']];
                        $temp[] = ['', '', 'TOTEQU_REL 所有者权益', $val['TOTEQU_REL'], $range['TOTEQU_REL']];
                        $temp[] = ['', '', 'SOCNUM 社保人数', $val['SOCNUM'], ''];
                    }
                }
            }
        }

        if (!empty($temp)) {
            $config = [
                'path' => OTHER_FILE_PATH,
            ];
            $fileName = 'tutorial01.xlsx';
            $xlsxObject = new \Vtiful\Kernel\Excel($config);
            $filePath = $xlsxObject->fileName($fileName, 'sheet1')
                ->header(['企业名称', '年', '字段', '数值', '区间'])->data($temp)->output();
            $this->response()->redirect('/Static/OtherFile/' . $fileName);
        } else {
            return $this->writeJson(200, null, $temp, 'ok');
        }
    }


}