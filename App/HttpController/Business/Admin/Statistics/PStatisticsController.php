<?php

namespace App\HttpController\Business\Admin\Statistics;

use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Models\Provide\RequestRecode;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
use Carbon\Carbon;
use Ritaswc\ZxIPAddress\IPv4Tool;

class PStatisticsController extends StatisticsBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function getStatisticsList()
    {
        $uid = $this->getRequestData('uid');
        $aid = $this->getRequestData('aid');
        $date = $this->getRequestData('date');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 20);

        $year = Carbon::now()->year;

        $data = RequestRecode::create()->addSuffix($year)->alias('t1')
            ->join('information_dance_request_user_info as t2', 't1.userId = t2.id', 'left')
            ->join('information_dance_request_api_info as t3', 't1.provideApiId = t3.id', 'left')
            ->field([
                't1.id',
                't1.requestId',
                't1.requestIp',
                't1.requestData',
                't1.responseCode',
                't1.responseData',
                't1.spendTime',
                't1.spendMoney',
                't1.created_at',
                't2.username',
                't3.path',
                't3.name',
                't3.desc',
                't3.source',
                't3.price',
            ])->order('t1.created_at', 'desc')
            ->limit($this->exprOffset($page, $pageSize), $pageSize);

        $total = RequestRecode::create()->addSuffix($year)->alias('t1')
            ->join('information_dance_request_user_info as t2', 't1.userId = t2.id', 'left')
            ->join('information_dance_request_api_info as t3', 't1.provideApiId = t3.id', 'left');

        if (is_numeric($uid)) {
            $data->where('t2.id', $uid);
            $total->where('t2.id', $uid);
        }

        if (is_numeric($aid)) {
            $data->where('t3.id', $aid);
            $total->where('t3.id', $aid);
        }

        if (!empty($date)) {
            $tmp = explode('|||', $date);
            $date1 = Carbon::parse($tmp[0])->startOfDay()->timestamp;
            $date2 = Carbon::parse($tmp[1])->endOfDay()->timestamp;
            $data->where('t1.created_at', [$date1, $date2], 'BETWEEN');
            $total->where('t1.created_at', [$date1, $date2], 'BETWEEN');
        }

        $data = $data->all();
        $total = $total->count('t1.id');

        $paging = [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
        ];

        if (!empty($data)) {
            foreach ($data as $key => $val) {
                if (empty(trim($val['requestIp']))) {
                    $ip_info = ['disp' => '本地'];
                } else {
                    $ip_info = IPv4Tool::query($val['requestIp']);
                }
                $data[$key]['ipDetail'] = $ip_info;
            }
        }

        $ext = [
            'user_info' => RequestUserInfo::create()->field(['id', 'username'])->all(),
            'api_info' => RequestApiInfo::create()->field(['id', 'name', 'source'])->all(),
        ];

        return $this->writeJson(200, $paging, $data, null, true, $ext);
    }


}