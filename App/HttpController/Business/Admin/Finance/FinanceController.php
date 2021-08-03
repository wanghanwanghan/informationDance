<?php

namespace App\HttpController\Business\Admin\Finance;

use App\HttpController\Models\Api\User;
use App\HttpController\Models\Api\Wallet;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
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

        fwrite($fp, implode(',', ['数据年份', '企业名称', '营业总收入',
                '资产总额', '负债总额', '纳税总额',
                '主营业务收入', '所有者权益', '利润总额', '净利润', '社保人数',]) . PHP_EOL);

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
                        'YEAR' => $year,
                        'ENTNAME' => $oneEnt,
                        'VENDINC' => is_numeric($val['VENDINC']) ? sprintf('%.2f', $val['VENDINC']) : '--',
                        'ASSGRO' => is_numeric($val['ASSGRO']) ? sprintf('%.2f', $val['ASSGRO']) : '--',
                        'LIAGRO' => is_numeric($val['LIAGRO']) ? sprintf('%.2f', $val['LIAGRO']) : '--',
                        'RATGRO' => is_numeric($val['RATGRO']) ? sprintf('%.2f', $val['RATGRO']) : '--',
                        'MAIBUSINC' => is_numeric($val['MAIBUSINC']) ? sprintf('%.2f', $val['MAIBUSINC']) : '--',
                        'TOTEQU' => is_numeric($val['TOTEQU']) ? sprintf('%.2f', $val['TOTEQU']) : '--',
                        'PROGRO' => is_numeric($val['PROGRO']) ? sprintf('%.2f', $val['PROGRO']) : '--',
                        'NETINC' => is_numeric($val['NETINC']) ? sprintf('%.2f', $val['NETINC']) : '--',
                        'SOCNUM' => is_numeric($val['SOCNUM']) ? sprintf('%.2f', $val['SOCNUM']) : '--',
                    ];
                    fwrite($fp, implode(',', array_values($row)) . PHP_EOL);
                    $tmp[] = $row;
                }
            }
        }

        fclose($fp);

        return $this->writeJson(200, null, ['list' => $tmp, 'file' => $csvFile]);
    }

}