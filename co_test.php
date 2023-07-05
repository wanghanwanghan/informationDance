<?php

date_default_timezone_set('Asia/Shanghai');

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPoolForEntDb;
use App\HttpController\Service\CreateMysqlPoolForMinZuJiDiDb;
use App\HttpController\Service\CreateMysqlPoolForRDS3NicCode;
use App\HttpController\Service\CreateMysqlPoolForProjectDb;
use App\HttpController\Service\CreateMysqlPoolForRDS3SiJiFenLei;
use App\HttpController\Service\CreateMysqlPoolForJinCai;
use App\HttpController\Service\CreateRedisPool;
use \EasySwoole\EasySwoole\Core;
use App\HttpController\Service\CreateDefine;
use \EasySwoole\Component\Process\Config;
use \EasySwoole\Component\Process\AbstractProcess;

require_once './vendor/autoload.php';
require_once './bootstrap.php';

Core::getInstance()->initialize();

class co_test extends AbstractProcess
{
    //启动
    protected function run($arg)
    {
        $fr = fopen('renjiaochuzi.txt', 'r');

        while (feof($fr) === false) {

            $str = trim(fgets($fr));

            if (empty($str)) continue;

            $arr = explode("\t", $str);

            $sql = <<<EOF
SELECT
	ANCHEYEAR,
	INV,
	SUBCONDATE 
FROM
	company_ar_capital 
WHERE
	companyid = ( SELECT companyid FROM company_basic WHERE UNISCID = '{$arr[2]}' ORDER BY updated DESC LIMIT 1 ) 
ORDER BY
	ANCHEYEAR;
EOF;
            $res = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_hd_saic'));

            dd($res);


        }

    }

    protected function biaoxun()
    {
        $url = 'https://api.biaoxun.cn/api/search/find';

        $key = '1c05d3aaa53e4cb19f62002c56c06602';
        $secret = '5c5c97af02ff4117a2cc27953510fe60';
        $randomStr = \wanghanwanghan\someUtils\control::getUuid();
        $time = time() . '000';

        $sign = md5($key . $time . $randomStr . $secret);

        $data = [
            'winCompany' => ['黄河水利委员会黄河水利科学研究院']
        ];

        $header = [
            'key' => $key,
            'timestamp' => $time,
            'randomStr' => $randomStr,
            'sign' => $sign,
        ];

        $res = (new \App\HttpController\Service\HttpClient\CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(false)
            ->send($url, $data, $header, [], 'postJSON');

        $res = jsonDecode($res);

        foreach ($res['data']['list'] as $one) {


            $BID_TITLE = $one['BID_TITLE'] ?? '艹';                   // BID_TITLE	String	信息标题
            $PROVINCE = $one['PROVINCE'] ?? '艹';                     // PROVINCE	String	省份
            $CITY = $one['CITY'] ?? '艹';                             // CITY	String	城市
            $PUBLISH_TM = $one['PUBLISH_TM'] ?? '艹';                 // PUBLISH_TM	String	公告发布时间
            $BID_NAME = $one['BID_NAME'] ?? '艹';                     // BID_NAME	String	项目名称
            $BID_NUM = $one['BID_NUM'] ?? '艹';                       // BID_NUM	String	招标编号
            $BID_BUDGET_AMT = $one['BID_BUDGET_AMT'] ?? '艹';         // BID_BUDGET_AMT	decimal(22,6)	招标预算（元）
            $WIN_AMT = $one['WIN_AMT'] ?? '艹';                       // WIN_AMT	decimal(22,6)	中标金额（元）
            $BXB_URL = $one['BXB_URL'] ?? '艹';                       // BXB_URL	String	标讯宝原文地址
            $BID_INDUSTRY_TYPE1 = $one['BID_INDUSTRY_TYPE1'] ?? '艹'; // BID_INDUSTRY_TYPE1	String	行业类型
            $BID_TYPE = $one['BID_TYPE'] ?? '艹';                     // BID_TYPE	String	公告类型（码值）
            $BID_TEXT = $one['BID_TEXT'] ?? '艹';                     // BID_TEXT	String	公告正文文本
            $BID_TEXT_HTML = $one['BID_TEXT_HTML'] ?? '艹';           // BID_TEXT_HTML	String	公告正文HTML
            $BUYER_NAME = $one['BUYER_NAME'] ?? '艹';                 // BUYER_NAME	String	招标单位
            $BUYER_USER = $one['BUYER_USER'] ?? '艹';                 // BUYER_USER	String	招标单位联系人
            $PROTECT_CONTACT = $one['PROTECT_CONTACT'] ?? '艹';       // PROTECT_CONTACT	String	招标单位联系方式
            $AGENT_NAME = $one['AGENT_NAME'] ?? '艹';                 // AGENT_NAME	String	招标代理机构
            $AGENT_USER = $one['AGENT_USER'] ?? '艹';                 // AGENT_USER	String	招标代理机构联系人
            $AGENT_CONTACT = $one['AGENT_CONTACT'] ?? '艹';           // AGENT_CONTACT	String	招标代理机构联系方式
            $WIN_SUPPLY = $one['WIN_SUPPLY'] ?? '艹';                 // WIN_SUPPLY	String	中标单位
            $WIN_USER = $one['WIN_USER'] ?? '艹';                     // WIN_USER	String	中标单位联系人
            $WIN_CONTACT = $one['WIN_CONTACT'] ?? '艹';               // WIN_CONTACT	String	中标单位联系方式
            $OBJECT_NAMES = $one['OBJECT_NAMES'] ?? '艹';             // OBJECT_NAMES	String	标的物-产品名称
            $BID_PRODUCT = $one['BID_PRODUCT'] ?? '艹';               // BID_PRODUCT	String	招标产品-标题
            $ROWID = $one['ROWID'] ?? '艹';                           // ROWID	String	数据唯一标识
            $MODIFY_TM = $one['MODIFY_TM'] ?? '艹';                   // MODIFY_TM	String	数据最新更新时间（数据标识）


        }

        dd('onver');
    }

    protected function onShutDown()
    {

    }

    protected function onException(\Throwable $throwable, ...$args)
    {

    }

}

CreateDefine::getInstance()->createDefine(__DIR__);
CreateConf::getInstance()->create(__DIR__);

//mysql pool
CreateMysqlPoolForProjectDb::getInstance()->createMysql();
CreateMysqlPoolForEntDb::getInstance()->createMysql();
CreateMysqlPoolForMinZuJiDiDb::getInstance()->createMysql();
CreateMysqlPoolForRDS3NicCode::getInstance()->createMysql();
CreateMysqlPoolForRDS3SiJiFenLei::getInstance()->createMysql();
CreateMysqlPoolForJinCai::getInstance()->createMysql();

//mysql orm
CreateMysqlOrm::getInstance()->createMysqlOrm();
CreateMysqlOrm::getInstance()->createEntDbOrm();
CreateMysqlOrm::getInstance()->createRDS3Orm();
CreateMysqlOrm::getInstance()->createRDS3NicCodeOrm();
CreateMysqlOrm::getInstance()->createRDS3SiJiFenLeiOrm();
CreateMysqlOrm::getInstance()->createRDS3Prism1Orm();
CreateMysqlOrm::getInstance()->createRDS3JinCai();

CreateRedisPool::getInstance()->createRedis();

for ($i = 1; $i--;) {
    $conf = new Config();
    $conf->setArg(['foo' => $i]);
    $conf->setEnableCoroutine(true);
    $process = new co_test($conf);
    $process->getProcess()->start();
}

while (Swoole\Process::wait(true)) {
    var_dump('exit eee');
    exit;
}
