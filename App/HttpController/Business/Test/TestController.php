<?php

namespace App\HttpController\Business\Test;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\QianQi\QianQiService;

class TestController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return true;
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

                //ASSGRO_REL 资产总额
                //LIAGRO_REL 负债总额
                //VENDINC_REL 营业总收入
                //MAIBUSINC_REL 主营业务收入
                //PROGRO_REL 利润总额
                //NETINC_REL 净利润
                //RATGRO_REL 纳税总额
                //TOTEQU_REL 所有者权益
                //SOCNUM 社保人数

                foreach ($res['result'] as $key => $val) {
                    if (empty($val)) {
                        $temp[] = [$entName, $key, '无数据', '无数据'];
                    } else {
                        $temp[] = [$entName, $key, 'ASSGRO_REL 资产总额', $val['ASSGRO_REL']];
                        $temp[] = [$entName, $key, 'LIAGRO_REL 负债总额', $val['LIAGRO_REL']];
                        $temp[] = [$entName, $key, 'VENDINC_REL 营业总收入', $val['VENDINC_REL']];
                        $temp[] = [$entName, $key, 'MAIBUSINC_REL 主营业务收入', $val['MAIBUSINC_REL']];
                        $temp[] = [$entName, $key, 'PROGRO_REL 利润总额', $val['PROGRO_REL']];
                        $temp[] = [$entName, $key, 'NETINC_REL 净利润', $val['NETINC_REL']];
                        $temp[] = [$entName, $key, 'RATGRO_REL 纳税总额', $val['RATGRO_REL']];
                        $temp[] = [$entName, $key, 'TOTEQU_REL 所有者权益', $val['TOTEQU_REL']];
                        $temp[] = [$entName, $key, 'SOCNUM 社保人数', $val['SOCNUM']];
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

            $filePath = $xlsxObject->fileName('tutorial01.xlsx', 'sheet1')
                ->header(['企业名称', '年', '字段', '数值'])->data($temp)->output();

            $this->response()->redirect('/Static/OtherFile/tutorial01.xlsx');



//            $this->response()
//                ->withHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
//            $this->response()->withHeader('Content-Disposition', 'attachment;filename="' . $fileName . '"');
//            $this->response()->withHeader('Content-Length', filesize($filePath));
//            $this->response()->withHeader('Content-Transfer-Encoding', 'binary');
//            $this->response()->withHeader('Cache-Control', 'must-revalidate');
//            $this->response()->withHeader('Cache-Control', 'max-age=0');
//            $this->response()->withHeader('Pragma', 'public');
//            $this->response()->withStatus(200);
//            $this->response()->end();

        } else {
            return $this->writeJson(200, null, $temp, 'ok');
        }
    }

}