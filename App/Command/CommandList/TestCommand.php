<?php

namespace App\Command\CommandList;

use App\Command\CommandBase;
use App\HttpController\Service\CreateConf;
use EasySwoole\Pool\Manager;

class TestCommand extends CommandBase
{
    function commandName(): string
    {
        return 'test';
    }

    //php easyswoole test
    function exec(array $args): ?string
    {
        parent::commendInit();

        try {
            $mysqlObj = Manager::getInstance()
                ->get(CreateConf::getInstance()->getConf('env.mysqlDatabaseMZJD'))
                ->getObj();
            $mysqlObj->rawQuery('truncate table qyxx_copy1');
            while (true) {
                $limit = 0;
                $sql = <<<EOF
SELECT
	ORG_CODE,
	XZQH_NAME,
	count( 1 ) AS num 
FROM
	qyxx 
WHERE
	XZQH_NAME IS NOT NULL 
	AND XZQH_NAME <> "" 
GROUP BY
	ORG_CODE,
	XZQH_NAME 
	LIMIT {$limit},
	500
EOF;
                $list = $mysqlObj->rawQuery($sql);
                $list = obj2Arr($list);

                if (empty($list)) break;

                foreach ($list as $index => $val) {
                    if (strpos($val['XZQH_NAME'], '-') !== false) {
                        $val['XZQH_NAME'] = str_replace('--', '-', $val['XZQH_NAME']);
                        $placeArr = explode('-', $val['XZQH_NAME']);
                        $placeArr = array_filter($placeArr);
                        $sql = <<<EOF
SELECT
	* 
FROM
	qyxx_copy1 
WHERE
	XZQH_NAME = {$placeArr[0]}
EOF;
                        $check = $mysqlObj->rawQuery($sql);
                        if (empty($check)) {
                            $sql = <<<EOF
INSERT INTO qyxx_copy1
VALUES
	( {$placeArr[0]}, {$val['num']} )
EOF;
                        } else {
                            $sql = <<<EOF
UPDATE qyxx_copy1 
SET num = num + {$val['num']} 
WHERE
	XZQH_NAME = {$placeArr[0]}
EOF;
                        }
                        $mysqlObj->rawQuery($sql);
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->writeErr($e, __FUNCTION__);
        } finally {
            Manager::getInstance()
                ->get(CreateConf::getInstance()->getConf('env.mysqlDatabaseMZJD'))
                ->recycleObj($mysqlObj);
        }

        return 'this is exec' . PHP_EOL;
    }

    //php easyswoole help test
    function help(array $args): ?string
    {
        return 'this is help' . PHP_EOL;
    }
}