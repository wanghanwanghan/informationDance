<?php

namespace App\HttpController\Business\Test;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
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
        $code = $this->getRequestData('code', '');
    }

    //产品标准页面用
    function product()
    {
        try {
            $mysqlObj = Manager::getInstance()
                ->get(CreateConf::getInstance()->getConf('env.mysqlDatabaseMZJD'))
                ->getObj();
            $sql = 'SELECT XZQH_NAME,count( 1 ) as num FROM qyxx WHERE XZQH_NAME IS NOT NULL AND XZQH_NAME <> "" GROUP BY XZQH_NAME';
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

        $arr = [];

        if (!empty($list)) {
            foreach ($list as $index => $val) {
                if (strpos($val['XZQH_NAME'], '-') !== false) {
                    $placeArr = explode('-', $val['XZQH_NAME']);
                    $placeArr = array_filter($placeArr);
                    $place = current($placeArr);
                    if (isset($arr[$place])) {
                        $arr[$place]++;
                    } else {
                        $arr[$place] = 1;
                    }
                }
            }
        }

        return $this->writeJson(200, null, [
            'k' => array_keys($arr), 'v' => array_values($arr)
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