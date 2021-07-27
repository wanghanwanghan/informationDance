<?php

namespace App\Task\TaskList\EntDbTask;

use App\HttpController\Models\EntDb\EntDbEnt;
use App\HttpController\Models\EntDb\EntDbFinance;
use App\HttpController\Service\Common\CommonService;
use App\Task\TaskBase;
use EasySwoole\Task\AbstractInterface\TaskInterface;

class insertFinance extends TaskBase implements TaskInterface
{
    public $entName;
    public $finance;
    public $social;

    function __construct($entName = '', $finance = [], $social = [])
    {
        parent::__construct();

        $this->entName = trim($entName);
        $this->finance = $finance;
        $this->social = $social;

        return true;
    }

    function run(int $taskId, int $workerIndex)
    {
        if (empty($this->entName) || empty($this->finance)) return true;

        //插入财务数据
        try {
            $entInfo = EntDbEnt::create()->where('name', $this->entName)->get();
            if (empty($entInfo)) return true;
            $cid = $entInfo->getAttr('id');
            //整理要插入数组
            foreach ($this->social as $oneSoc) {
                $year = $oneSoc['ANCHEYEAR'];
                if (!is_numeric($year) || !isset($this->finance[$year . ''])) continue;
                $this->finance[$year . ''] = array_merge($this->finance[$year . ''], $oneSoc);
            }
            foreach ($this->finance as $rd) {
                if (empty($rd)) continue;
                $check = EntDbFinance::create()->where(['cid' => $cid, 'ANCHEYEAR' => $rd['ANCHEYEAR']])->get();
                if (empty($check)) {
                    EntDbFinance::create()->data([
                        'cid' => $cid,
                        'ANCHEYEAR' => $rd['ANCHEYEAR'],
                        'VENDINC' => (!isset($rd['VENDINC']) || !is_numeric($rd['VENDINC'])) ? null : $rd['VENDINC'],
                        'ASSGRO' => (!isset($rd['ASSGRO']) || !is_numeric($rd['ASSGRO'])) ? null : $rd['ASSGRO'],
                        'MAIBUSINC' => (!isset($rd['MAIBUSINC']) || !is_numeric($rd['MAIBUSINC'])) ? null : $rd['MAIBUSINC'],
                        'TOTEQU' => (!isset($rd['TOTEQU']) || !is_numeric($rd['TOTEQU'])) ? null : $rd['TOTEQU'],
                        'RATGRO' => (!isset($rd['RATGRO']) || !is_numeric($rd['RATGRO'])) ? null : $rd['RATGRO'],
                        'PROGRO' => (!isset($rd['PROGRO']) || !is_numeric($rd['PROGRO'])) ? null : $rd['PROGRO'],
                        'NETINC' => (!isset($rd['NETINC']) || !is_numeric($rd['NETINC'])) ? null : $rd['NETINC'],
                        'LIAGRO' => (!isset($rd['LIAGRO']) || !is_numeric($rd['LIAGRO'])) ? null : $rd['LIAGRO'],
                        'So1' => (!isset($rd['so1']) || !is_numeric($rd['so1'])) ? null : $rd['so1'],
                        'So2' => (!isset($rd['so2']) || !is_numeric($rd['so2'])) ? null : $rd['so2'],
                        'So3' => (!isset($rd['so3']) || !is_numeric($rd['so3'])) ? null : $rd['so3'],
                        'So4' => (!isset($rd['so4']) || !is_numeric($rd['so4'])) ? null : $rd['so4'],
                        'So5' => (!isset($rd['so5']) || !is_numeric($rd['so5'])) ? null : $rd['so5'],
                        'totalWagesSo1' => (!isset($rd['totalWagesSo1']) || !is_numeric($rd['totalWagesSo1'])) ? null : $rd['totalWagesSo1'],
                        'totalWagesSo2' => (!isset($rd['totalWagesSo2']) || !is_numeric($rd['totalWagesSo2'])) ? null : $rd['totalWagesSo2'],
                        'totalWagesSo3' => (!isset($rd['totalWagesSo3']) || !is_numeric($rd['totalWagesSo3'])) ? null : $rd['totalWagesSo3'],
                        'totalWagesSo4' => (!isset($rd['totalWagesSo4']) || !is_numeric($rd['totalWagesSo4'])) ? null : $rd['totalWagesSo4'],
                        'totalWagesSo5' => (!isset($rd['totalWagesSo5']) || !is_numeric($rd['totalWagesSo5'])) ? null : $rd['totalWagesSo5'],
                        'totalPaymentSo1' => (!isset($rd['totalPaymentSo1']) || !is_numeric($rd['totalPaymentSo1'])) ? null : $rd['totalPaymentSo1'],
                        'totalPaymentSo2' => (!isset($rd['totalPaymentSo2']) || !is_numeric($rd['totalPaymentSo2'])) ? null : $rd['totalPaymentSo2'],
                        'totalPaymentSo3' => (!isset($rd['totalPaymentSo3']) || !is_numeric($rd['totalPaymentSo3'])) ? null : $rd['totalPaymentSo3'],
                        'totalPaymentSo4' => (!isset($rd['totalPaymentSo4']) || !is_numeric($rd['totalPaymentSo4'])) ? null : $rd['totalPaymentSo4'],
                        'totalPaymentSo5' => (!isset($rd['totalPaymentSo5']) || !is_numeric($rd['totalPaymentSo5'])) ? null : $rd['totalPaymentSo5'],
                        'unPaidSocialInsSo1' => (!isset($rd['unPaidSocialInsSo1']) || !is_numeric($rd['unPaidSocialInsSo1'])) ? null : $rd['unPaidSocialInsSo1'],
                        'unPaidSocialInsSo2' => (!isset($rd['unPaidSocialInsSo2']) || !is_numeric($rd['unPaidSocialInsSo2'])) ? null : $rd['unPaidSocialInsSo2'],
                        'unPaidSocialInsSo3' => (!isset($rd['unPaidSocialInsSo3']) || !is_numeric($rd['unPaidSocialInsSo3'])) ? null : $rd['unPaidSocialInsSo3'],
                        'unPaidSocialInsSo4' => (!isset($rd['unPaidSocialInsSo4']) || !is_numeric($rd['unPaidSocialInsSo4'])) ? null : $rd['unPaidSocialInsSo4'],
                        'unPaidSocialInsSo5' => (!isset($rd['unPaidSocialInsSo5']) || !is_numeric($rd['unPaidSocialInsSo5'])) ? null : $rd['unPaidSocialInsSo5'],
                    ])->save();
                } else {
                    $check->update([
                        'VENDINC' => (!isset($rd['VENDINC']) || !is_numeric($rd['VENDINC'])) ? null : $rd['VENDINC'],
                        'ASSGRO' => (!isset($rd['ASSGRO']) || !is_numeric($rd['ASSGRO'])) ? null : $rd['ASSGRO'],
                        'MAIBUSINC' => (!isset($rd['MAIBUSINC']) || !is_numeric($rd['MAIBUSINC'])) ? null : $rd['MAIBUSINC'],
                        'TOTEQU' => (!isset($rd['TOTEQU']) || !is_numeric($rd['TOTEQU'])) ? null : $rd['TOTEQU'],
                        'RATGRO' => (!isset($rd['RATGRO']) || !is_numeric($rd['RATGRO'])) ? null : $rd['RATGRO'],
                        'PROGRO' => (!isset($rd['PROGRO']) || !is_numeric($rd['PROGRO'])) ? null : $rd['PROGRO'],
                        'NETINC' => (!isset($rd['NETINC']) || !is_numeric($rd['NETINC'])) ? null : $rd['NETINC'],
                        'LIAGRO' => (!isset($rd['LIAGRO']) || !is_numeric($rd['LIAGRO'])) ? null : $rd['LIAGRO'],
                        'So1' => (!isset($rd['so1']) || !is_numeric($rd['so1'])) ? null : $rd['so1'],
                        'So2' => (!isset($rd['so2']) || !is_numeric($rd['so2'])) ? null : $rd['so2'],
                        'So3' => (!isset($rd['so3']) || !is_numeric($rd['so3'])) ? null : $rd['so3'],
                        'So4' => (!isset($rd['so4']) || !is_numeric($rd['so4'])) ? null : $rd['so4'],
                        'So5' => (!isset($rd['so5']) || !is_numeric($rd['so5'])) ? null : $rd['so5'],
                        'totalWagesSo1' => (!isset($rd['totalWagesSo1']) || !is_numeric($rd['totalWagesSo1'])) ? null : $rd['totalWagesSo1'],
                        'totalWagesSo2' => (!isset($rd['totalWagesSo2']) || !is_numeric($rd['totalWagesSo2'])) ? null : $rd['totalWagesSo2'],
                        'totalWagesSo3' => (!isset($rd['totalWagesSo3']) || !is_numeric($rd['totalWagesSo3'])) ? null : $rd['totalWagesSo3'],
                        'totalWagesSo4' => (!isset($rd['totalWagesSo4']) || !is_numeric($rd['totalWagesSo4'])) ? null : $rd['totalWagesSo4'],
                        'totalWagesSo5' => (!isset($rd['totalWagesSo5']) || !is_numeric($rd['totalWagesSo5'])) ? null : $rd['totalWagesSo5'],
                        'totalPaymentSo1' => (!isset($rd['totalPaymentSo1']) || !is_numeric($rd['totalPaymentSo1'])) ? null : $rd['totalPaymentSo1'],
                        'totalPaymentSo2' => (!isset($rd['totalPaymentSo2']) || !is_numeric($rd['totalPaymentSo2'])) ? null : $rd['totalPaymentSo2'],
                        'totalPaymentSo3' => (!isset($rd['totalPaymentSo3']) || !is_numeric($rd['totalPaymentSo3'])) ? null : $rd['totalPaymentSo3'],
                        'totalPaymentSo4' => (!isset($rd['totalPaymentSo4']) || !is_numeric($rd['totalPaymentSo4'])) ? null : $rd['totalPaymentSo4'],
                        'totalPaymentSo5' => (!isset($rd['totalPaymentSo5']) || !is_numeric($rd['totalPaymentSo5'])) ? null : $rd['totalPaymentSo5'],
                        'unPaidSocialInsSo1' => (!isset($rd['unPaidSocialInsSo1']) || !is_numeric($rd['unPaidSocialInsSo1'])) ? null : $rd['unPaidSocialInsSo1'],
                        'unPaidSocialInsSo2' => (!isset($rd['unPaidSocialInsSo2']) || !is_numeric($rd['unPaidSocialInsSo2'])) ? null : $rd['unPaidSocialInsSo2'],
                        'unPaidSocialInsSo3' => (!isset($rd['unPaidSocialInsSo3']) || !is_numeric($rd['unPaidSocialInsSo3'])) ? null : $rd['unPaidSocialInsSo3'],
                        'unPaidSocialInsSo4' => (!isset($rd['unPaidSocialInsSo4']) || !is_numeric($rd['unPaidSocialInsSo4'])) ? null : $rd['unPaidSocialInsSo4'],
                        'unPaidSocialInsSo5' => (!isset($rd['unPaidSocialInsSo5']) || !is_numeric($rd['unPaidSocialInsSo5'])) ? null : $rd['unPaidSocialInsSo5'],
                    ]);
                }
            }
        } catch (\Throwable $e) {

        }
        return true;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }
}
