<?php

namespace App\HttpController\Business\Admin\Finance;

use App\HttpController\Models\Api\User;
use App\HttpController\Models\Api\Wallet;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\LongXin\LongXinService;
use EasySwoole\Http\Message\UploadFile;
use EasySwoole\Mysqli\QueryBuilder;
use Overtrue\Pinyin\Pinyin;
use wanghanwanghan\someUtils\control;

class FinanceController extends FinanceBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function getIndex()
    {
        //个人用户
        $userList = User::create()->all();

        //企业用户
        $entUserList = RequestUserInfo::create()->all();


        return $this->writeJson(200, null, [
            'userList' => $userList,
            'entUserList' => $entUserList,
        ]);
    }

    function uploadEntList()
    {
        $files = $this->request()->getUploadedFiles();

        foreach ($files as $key => $oneFile) {
            if ($oneFile instanceof UploadFile) {
                try {
                    $filename = $oneFile->getTempName();
                    break;
                } catch (\Throwable $e) {
                    return $this->writeErr($e, __FUNCTION__);
                }
            }
        }

        $fp = fopen($filename, 'r+');

        while (feof($fp) === false) {
            $row = fgets($fp);
            $row = trim($row);
            if (strlen($row) < 5) {
                break;
            }
            $arr = explode(',', $row);
            $pinyin = (new Pinyin())->convert($arr[0]);
            $content[] = ['name' => $arr[0], 'pinyin' => implode('', $pinyin)];
        }

        fclose($fp);

        return $this->writeJson(200, null, $content);
    }

    function getFinanceData()
    {
        $payEntValue = $this->getRequestData('payEntValue');
        $payUserValue = $this->getRequestData('payUserValue');
        $money = $this->getRequestData('money', 0);
        $entList = $this->getRequestData('entList');
        $CkRange = $this->getRequestData('CkRange');

        if (empty($payEntValue)) {
            $info = Wallet::create()->where('phone', $payUserValue)->get();
        } else {
            $info = RequestUserInfo::create()->where('appId', $payEntValue)->get();
        }

        if ($info->getAttr('money') < $money) {
            $this->writeJson(201);
        }

        $info->update([
            'money' => QueryBuilder::dec($money)
        ]);

        $csvFile = control::getUuid() . '.csv';

        $fp = fopen(TEMP_FILE_PATH . $csvFile, 'w+');

        $header = [
            '企业名称',
            '数据年份',
            '资产总额',
            '负债总额',
            '营业总收入',
            '主营业务收入',
            '利润总额',
            '净利润',
            '纳税总额',
            '所有者权益',
            '社保人数',
            '净资产',
            '平均资产总额',
            '平均净资产',
            '企业人均产值',
            '企业人均盈利',
        ];

        fwrite($fp, implode(',', $header) . PHP_EOL);

        foreach (jsonDecode($entList) as $oneEnt) {
            $postData = [
                'entName' => $oneEnt,
                'code' => '',
                'beginYear' => date('Y') - 1,
                'dataCount' => 5,
            ];
            $res = (new LongXinService())
                ->setCheckRespFlag(true)
                ->getFinanceData($postData, false);

            if (!empty($res['result']) && $res['code'] === 200) {
                foreach ($res['result'] as $year => $val) {
                    $row = [
                        'ENTNAME' => $oneEnt,
                        'YEAR' => $year,
                        'ASSGRO' => $this->setFinanceDataRange($val['ASSGRO'], $CkRange),
                        'LIAGRO' => $this->setFinanceDataRange($val['LIAGRO'], $CkRange),
                        'VENDINC' => $this->setFinanceDataRange($val['VENDINC'], $CkRange),
                        'MAIBUSINC' => $this->setFinanceDataRange($val['MAIBUSINC'], $CkRange),
                        'PROGRO' => $this->setFinanceDataRange($val['PROGRO'], $CkRange),
                        'NETINC' => $this->setFinanceDataRange($val['NETINC'], $CkRange),
                        'RATGRO' => $this->setFinanceDataRange($val['RATGRO'], $CkRange),
                        'TOTEQU' => $this->setFinanceDataRange($val['TOTEQU'], $CkRange),
                        'SOCNUM' => is_numeric($val['SOCNUM']) ? sprintf('%.2f', $val['SOCNUM']) : '--',
                        'C_ASSGROL' => $this->setFinanceDataRange($val['C_ASSGROL'], $CkRange),
                        'A_ASSGROL' => $this->setFinanceDataRange($val['A_ASSGROL'], $CkRange),
                        'CA_ASSGRO' => $this->setFinanceDataRange($val['CA_ASSGRO'], $CkRange),
                        'A_VENDINCL' => $this->setFinanceDataRange($val['A_VENDINCL'], $CkRange),
                        'A_PROGROL' => $this->setFinanceDataRange($val['A_PROGROL'], $CkRange),
                    ];
                    fwrite($fp, implode(',', array_values($row)) . PHP_EOL);
                    $tmp[] = $row;
                }
            }
        }

        fclose($fp);

        return $this->writeJson(200, null, ['list' => $tmp, 'file' => $csvFile]);
    }

    function setFinanceDataRange($num, $CkRangeNum): string
    {
        $CkRangeNum = $CkRangeNum - 0;

        $num = trim($num);

        $str = '--';

        if (!is_numeric($num)) return $str;

        $num = $num - 0;

        if ($CkRangeNum === 1) {
            $str = sprintf('%.2f', $num);
        } else {
            if ($num === 0) {
                $str = 'Z';
            } elseif ($num < 499) {
                $str = 'A';
            } elseif ($num < 999) {
                $str = 'B';
            } elseif ($num < 4999) {
                $str = 'C';
            } elseif ($num < 9999) {
                $str = 'D';
            } elseif ($num < 99999) {
                $str = 'E';
            } else {
                $str = 'F';
            }
        }

        return $str;
    }

}