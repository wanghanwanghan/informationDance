<?php

namespace App\HttpController\Business\Test;

use App\Csp\Service\CspService;
use App\HttpController\Business\BusinessBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\DbManager;

class TestController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return true;
    }

    function test20220614(): bool
    {
        $entName = $this->request()->getRequestParam('entName');

        $sql = <<<Eof
SELECT
	id,`name` 
FROM
	`company` 
WHERE
	`name` = '{$entName}' LIMIT 1;
Eof;
        $queryBuilder = new QueryBuilder();
        $queryBuilder->raw($sql);
        $list = DbManager::getInstance()
            ->query($queryBuilder, true, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_prism1'))
            ->toArray();

        if (!empty($list['result'])) {
            return $this->writeJson(200, null, $list);
        }

        for ($i = 0; $i <= 3; $i++) {
            $csp = CspService::getInstance()->create();
            $start = $i * 2;
            $end = $start + 1;
            for ($j = $start; $j <= $end; $j++) {
                $csp->add('BOOLEAN_MODE_new_company_name_' . $j, function () use ($entName, $j) {
                    $matchStr = $this->splitChineseNameForMatchAgainst($entName);
                    $retData = $this->matchAainstEntName(
                        $matchStr,
                        " IN BOOLEAN MODE ",
                        'company_name_' . $j,
                        'id,name',
                        5
                    );
                    return ['data' => $retData, 'type' => 'Boolean', 'cspKey' => $j];
                });
            }
            $dbres = CspService::getInstance()->exec($csp, 10);
            foreach ($dbres as $dataItem) {
                CommonService::getInstance()->log4PHP(jsonEncode($dataItem, false), 'info', 'csptest');
            }
        }

        return $this->writeJson(200, null, $list, 'haha');
    }

    function matchAainstEntName($str, $mode = " IN NATURAL LANGUAGE MODE ", $companyName = "company_name_0", $field = "id,name", $limit = 1): ?array
    {
        $sql = <<<Eof
SELECT
	{$field} 
FROM
	{$companyName} 
WHERE
	MATCH ( `name` ) AGAINST ( '{$str}' {$mode} ) 
	LIMIT {$limit}
Eof;
        try {
            $queryBuilder = new QueryBuilder();
            $queryBuilder->raw($sql);
            $res = DbManager::getInstance()
                ->query($queryBuilder, true, CreateConf::getInstance()->getConf('env.mysqlDatabase'))
                ->toArray();
        } catch (\Throwable $e) {
            return null;
        }

        return $res['result'];
    }

    function splitChineseNameForMatchAgainst($entName): ?string
    {
        $arr = preg_split('/(?<!^)(?!$)/u', $entName);

        $matchStr = "";

        if ($arr[0] && $arr[1]) {
            $matchStr .= '+' . $arr[0] . $arr[1];
        }
        if ($arr[2] && $arr[3]) {
            $matchStr .= '+' . $arr[2] . $arr[3];
        }
        if ($arr[4] && $arr[5]) {
            $matchStr .= '+' . $arr[4] . $arr[5];
        }
        if ($arr[6] && $arr[7]) {
            $matchStr .= '+' . $arr[6] . $arr[7];
        }
        if ($arr[8] && $arr[9]) {
            $matchStr .= '+' . $arr[8] . $arr[9];
        }

        return $matchStr;
    }

}