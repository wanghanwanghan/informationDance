<?php

namespace App\Process\ProcessList;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\Process\ProcessBase;
use EasySwoole\Pool\Manager;
use Swoole\Process;

class TestProcess extends ProcessBase
{
    protected function run($arg)
    {
        //可以用来初始化
        parent::run($arg);

        //接收参数可以是字符串也可以是数组

        $this->tmp();
    }

    protected function onPipeReadable(Process $process)
    {
        parent::onPipeReadable($process);

        //接收数据 string
        $data = jsonDecode($process->read());

        return true;
    }

    function tmp()
    {
        try {
            $mysqlObj = Manager::getInstance()
                ->get(CreateConf::getInstance()->getConf('env.mysqlDatabaseMZJD'))
                ->getObj();
            $mysqlObj->rawQuery('truncate table qyxx_copy1');
            $limit = 0;
            while (true) {
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
                $limit++;

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
                        CommonService::getInstance()->log4PHP($check);
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
            CommonService::getInstance()->log4PHP($e->getMessage());
        } finally {
            Manager::getInstance()
                ->get(CreateConf::getInstance()->getConf('env.mysqlDatabaseMZJD'))
                ->recycleObj($mysqlObj);
        }
    }

    protected function onShutDown()
    {
    }

    protected function onException(\Throwable $throwable, ...$args)
    {
    }


}
