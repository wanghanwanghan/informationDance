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

    function getFinanceData(): bool
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

        if ($money > 0) {
            $info->update([
                'money' => QueryBuilder::dec($money)
            ]);
        }

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
            '净利率',
            '资产周转率',
            '总资产净利率',
            '总资产回报率',
            '净资产回报率A',
            '净资产回报率B',
            '资产负债率',
            '权益乘数',
            '主营业务比率',
            '净资产负债率',
            '营业利润率',
            '资本保值增值率',
            '营业净利率',
            '总资产利润率',
            '税收负担率',
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

                        'C_INTRATESL' => $this->setFinanceRateDataRange($val['C_INTRATESL'], $CkRange),
                        'ATOL' => $this->setFinanceRateDataRange($val['ATOL'], $CkRange),
                        'ASSGRO_C_INTRATESL' => $this->setFinanceRateDataRange($val['ASSGRO_C_INTRATESL'], $CkRange),
                        'ROAL' => $this->setFinanceRateDataRange($val['ROAL'], $CkRange),
                        'ROE_AL' => $this->setFinanceRateDataRange($val['ROE_AL'], $CkRange),
                        'ROE_BL' => $this->setFinanceRateDataRange($val['ROE_BL'], $CkRange),
                        'DEBTL' => $this->setFinanceRateDataRange($val['DEBTL'], $CkRange),
                        'EQUITYL' => is_numeric($val['EQUITYL']) ? sprintf('%.3f', $val['EQUITYL']) : '--',
                        'MAIBUSINC_RATIOL' => $this->setFinanceRateDataRange($val['MAIBUSINC_RATIOL'], $CkRange),
                        'NALR' => $this->setFinanceRateDataRange($val['NALR'], $CkRange),
                        'OPM' => $this->setFinanceRateDataRange($val['OPM'], $CkRange),
                        'ROCA' => $this->setFinanceRateDataRange($val['ROCA'], $CkRange),
                        'NOR' => $this->setFinanceRateDataRange($val['NOR'], $CkRange),
                        'PMOTA' => $this->setFinanceRateDataRange($val['PMOTA'], $CkRange),
                        'TBR' => $this->setFinanceRateDataRange($val['TBR'], $CkRange),
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
        } elseif ($CkRangeNum === 2) {
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
        } elseif ($CkRangeNum === 3) {
            $str = sprintf('%.2f', $num);
            $table = [
                '0' => 'A',
                '1' => 'B',
                '2' => 'C',
                '3' => 'D',
                '4' => 'E',
                '5' => 'F',
                '6' => 'G',
                '7' => 'H',
                '8' => 'I',
                '9' => 'J',
                '.' => '*',
                '-' => '#',
            ];
            $str = strtr($str, $table);
        } elseif ($CkRangeNum === 4) {
            $str = ceil($num) . '';
            $str < 0 ? $symbol = '-' : $symbol = '';
            $str = ltrim($str, '-');
            $len = strlen($str);
            if ($len >= 3) {
                $str = substr($str, 0, -2) . '00';
            } elseif ($len === 2) {
                $str = substr($str, 0, 1) . '0';
            } else {

            }
            $str = $symbol . $str;
            $str = sprintf('%.2f', $str);
        } else {
            $str = '出错了';
        }

        return $str;
    }

    function setFinanceRateDataRange($num, $CkRangeNum): string
    {
        $CkRangeNum = $CkRangeNum - 0;

        $num = trim($num);

        $str = '--';

        if (!is_numeric($num)) return $str;

        if ($CkRangeNum === 1) {
            $str = sprintf('%.3f', $num * 100) . '%';
        } elseif ($CkRangeNum === 2) {
            $str = sprintf('%.3f', $num * 100) . '%';
        } elseif ($CkRangeNum === 3) {
            $str = sprintf('%.3f', $num);
            $table = [
                '0' => 'A',
                '1' => 'B',
                '2' => 'C',
                '3' => 'D',
                '4' => 'E',
                '5' => 'F',
                '6' => 'G',
                '7' => 'H',
                '8' => 'I',
                '9' => 'J',
                '.' => '*',
                '-' => '#',
            ];
            $str = strtr($str, $table);
        } elseif ($CkRangeNum === 4) {
            $str = ceil($num * 100) . '';
            $str < 0 ? $symbol = '-' : $symbol = '';
            $str = ltrim($str, '-');
            $len = strlen($str);
            if ($len >= 3) {
                $str = substr($str, 0, -2) . '00';
            } elseif ($len === 2) {
                $str = substr($str, 0, 1) . '0';
            } else {

            }
            $str = $symbol . $str;
            $str = sprintf('%.3f', $str) . '%';
        } else {
            $str = '出错了';
        }

        return $str;
    }
}