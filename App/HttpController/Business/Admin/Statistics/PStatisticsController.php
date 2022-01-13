<?php

namespace App\HttpController\Business\Admin\Statistics;

use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Models\Provide\RequestRecode;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
use Carbon\Carbon;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\DbManager;
use Ritaswc\ZxIPAddress\IPv4Tool;
use wanghanwanghan\someUtils\control;

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

        $sql = $this->getSqlByYear($date);
        $querySql = '1=1';
        if (is_numeric($uid)) {
            $querySql .= ' and userId = ' . $uid;
        }

        if (is_numeric($aid)) {
            $querySql .= ' and provideApiId = ' . $aid;
        }

        if (!empty($date)) {
            $tmp = explode('|||', $date);
            $date1 = Carbon::parse($tmp[0])->startOfDay()->timestamp;
            $date2 = Carbon::parse($tmp[1])->endOfDay()->timestamp;
            $querySql .= ' and created_at between '.$date1.' and '.$date2 ;
        }
        $querySql = ($querySql == '1=1') ? '' : ' where ' . $querySql;
        $sql = $sql . $querySql;

        try {

            DbManager::getInstance()->startTransaction('mrxd');
            $field = $this->getField();
            CommonService::getInstance()->log4PHP("SELECT SQL_CALC_FOUND_ROWS " . $field . $sql . " order by created_at desc limit "
                . $this->exprOffset($page, $pageSize) . ' ,' . $pageSize,'info','getStatisticsListSql');

            $data = DbManager::getInstance()->query(
                (new QueryBuilder())->raw("SELECT SQL_CALC_FOUND_ROWS " . $field . $sql . " order by created_at desc limit "
                    . $this->exprOffset($page, $pageSize) . ' ,' . $pageSize), true, 'mrxd', 15)
                ->getResult();

            $total = DbManager::getInstance()->query(
                (new QueryBuilder())->raw("SELECT FOUND_ROWS() as num "), true, 'mrxd')
                ->getResultOne();

            DbManager::getInstance()->commit('mrxd');

        }catch (\Throwable $e) {
            DbManager::getInstance()->rollback('mrxd');
        }

        $userIds = array_column($data,'userId');
        $requestUserInfoList = getArrByKey(RequestUserInfo::getListByIds($userIds),'id');
        $provideApiIds = array_column($data,'provideApiId');
        $requestApiInfoList = getArrByKey(RequestApiInfo::getListByIds($provideApiIds),'id');

        $paging = [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total['num'],
        ];

        if (!empty($data)) {
            foreach ($data as $key => $val) {
                if (empty(trim($val['requestIp']))) {
                    $ip_info = ['disp' => '本地'];
                } else {
                    $ip_info = IPv4Tool::query($val['requestIp']);
                }
                $data[$key]['ipDetail'] = $ip_info;
                $provideApiId = $date[$key]['provideApiId'];
                $userId = $date[$key]['userId'];
                $date[$key]['path'] = $requestApiInfoList[$provideApiId]['path'];
                $date[$key]['name'] = $requestApiInfoList[$provideApiId]['name'];
                $date[$key]['desc'] = $requestApiInfoList[$provideApiId]['desc'];
                $date[$key]['source'] = $requestApiInfoList[$provideApiId]['source'];
                $date[$key]['price'] = $requestApiInfoList[$provideApiId]['price'];
                $date[$key]['username'] = $requestUserInfoList[$userId]['username'];
            }
        }
        $ext = [
            'user_info' => RequestUserInfo::create()->field(['id', 'username'])->all(),
            'api_info' => RequestApiInfo::create()->field(['id', 'name', 'source'])->all(),
        ];

        return $this->writeJson(200, $paging, $data, null, true, $ext);
    }


    /**
     * 获取joinSQL
     * @param $date
     * @return string
     */
    private function getSqlByYear($date): string
    {
        $dValue = 0;
        $startYear = Carbon::now()->year;
        if(!empty($date)){
            $tmp = explode('|||', $date);
            $startYear = substr($tmp[0], 0, 4);
            $endYear = substr($tmp[1], 0, 4);
            $dValue = $endYear - $startYear;
        }

        switch ($dValue) {
            case 0:
                $sql = "SELECT * FROM information_dance_request_recode_" . $startYear;
                break;
            case 1:
                $sql = "SELECT * FROM information_dance_request_recode_" . $startYear
                    . " UNION SELECT * FROM information_dance_request_recode_" . $endYear;
                break;
            case 2:
                $middleYear = $startYear + 1;
                $sql = "SELECT * FROM information_dance_request_recode_" . $startYear
                    . " UNION SELECT * FROM information_dance_request_recode_" . $middleYear
                    . " UNION SELECT * FROM information_dance_request_recode_" . $endYear;
                break;
            default:
                $sql = "SELECT * FROM information_dance_request_recode_" . $startYear;
                for ($i = 1; $i > $dValue; $i++) {
                    $sql .= " UNION SELECT * FROM information_dance_request_recode_" . ($startYear + $i);
                }
                $sql .= " UNION SELECT * FROM information_dance_request_recode_" . $endYear;
                break;
        }
        return " FROM( " . $sql . " ) as t";
    }

    function exportCsv()
    {
        $uid = $this->getRequestData('uid');
        $aid = $this->getRequestData('aid');
        $date = $this->getRequestData('date');

        $sql = $this->getSqlByYear($date);
        $querySql = '1=1';
        if (is_numeric($uid)) {
            $querySql .= ' and t2.id = ' . $uid;
        }

        if (is_numeric($aid)) {
            $querySql .= ' and t3.id = ' . $aid;
        }

        if (!empty($date)) {
            $tmp = explode('|||', $date);
            $date1 = Carbon::parse($tmp[0])->startOfDay()->timestamp;
            $date2 = Carbon::parse($tmp[1])->endOfDay()->timestamp;
            $querySql .= ' and t1.created_at between '.$date1.' and '.$date2 ;
        }
        $querySql = ($querySql == '1=1') ? '' : ' where ' . $querySql;
        $sql = $sql . $querySql;

        $field = $this->getField();
        $data = DbManager::getInstance()->query(
            (new QueryBuilder())->raw("SELECT SQL_CALC_FOUND_ROWS " . $field . $sql . " order by t1.created_at desc "), true, 'mrxd', 15)
            ->getResult();

        $i = 1;
        $filename = control::getUuid() . '.csv';
        foreach ($data as $oneData) {
            if ($i === 1) {
                $header = [
                    '用户名称',
                    '接口名称',
                    '接口描述',
                    '接口地址',
                    '是否成功',
                    '扣费金额',
                    '接口成本',
                    '请求ip',
                    '请求时间',
                    '请求唯一标记',
                ];
                file_put_contents(TEMP_FILE_PATH . $filename, implode(',', $header) . PHP_EOL);
            }
            $insert = [
                $oneData['username'],
                $oneData['name'],
                $oneData['desc'],
                $oneData['path'],
                $oneData['responseCode'],
                $oneData['spendMoney'],
                $oneData['price'],
                $oneData['requestIp'],
                date('Y-m-d H:i:s', $oneData['created_at'] ?? time()),
                $oneData['requestId'],
            ];
            file_put_contents(TEMP_FILE_PATH . $filename, implode(',', $insert) . PHP_EOL, FILE_APPEND);
            $i++;
        }

        return $this->writeJson(200, null, ['filename' => $filename]);
    }

    /**
     * 获取查询字段
     * @return string
     */
    private function getField(): string
    {
        return 'id,
        requestId,
        requestIp,
        requestData,
        responseCode,
        responseData,
        spendTime,
        spendMoney,
        created_at,
        provideApiId,
        userId';
    }
}