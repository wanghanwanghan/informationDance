<?php

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPoolForEntDb;
use App\HttpController\Service\CreateMysqlPoolForMinZuJiDiDb;
use App\HttpController\Service\CreateMysqlPoolForProjectDb;
use App\HttpController\Service\CreateRedisPool;
use \EasySwoole\EasySwoole\Core;
use App\HttpController\Service\CreateDefine;
use \EasySwoole\Component\Process\Config;
use \EasySwoole\Component\Process\AbstractProcess;
use Swoole\Coroutine;

require_once './vendor/autoload.php';

Core::getInstance()->initialize();

class P extends AbstractProcess
{
    protected function run($arg)
    {
        $list = [
            '91330206144289544J',
            '91331082MA28G41Q54',
            '913205097222110026',
            '91320509726550060E',
            '913205825713907005',
            '91320205MA1MYEWL8U',
            '91320281055208028P',
            '913202117279935685',
            '913301057996661953',
            '91440300MA5DMM0H7K',
            '91440300734188014L',
            '91440300MA5DL0TU2J',
            '91330424MA2BAHH180',
            '9133030156817509XQ',
            '91330326MA2992N506',
            '91110114057321139M',
            '91110105599663723M',
            '91330783754911345W',
            '91320116067077918Q',
            '91320106302381232U',
            '9132000057814051XH',
            '91330106MA2B2QEYX1',
            '91310116550036382C',
            '91310117332729236X',
            '913101075741892796',
            '91331100344077765Y',
            '91330283L442580730',
            '91330602MA2883PQ16',
            '91330902719524024G',
            '91330503MA2B7X9M68',
            '9133080275590610X5',
            '911101063271474421',
            '913302827532988257',
            '913302057900591107',
            '91440604MA514LX49G',
            '91440300797968747W',
            '91440101556661656R',
            '91440106347403386C',
            '91440113078407820B',
            '914401117837889051',
            '91440784MA4UUACC7R',
            '91441700MA53MCEL2W',
            '91331082MA28GCCY29',
            '91441200737587598J',
            '91440111550564003N',
            '91330103768239019C',
            '91330106077336469W',
            '914403003591271124',
            '91440300MA5DEUF95F',
            '91320583583715816U',
            '91440300MA5FYA0G3H',
            '91330106MA2GMU7P6G',
            '91371000672202000K',
            '91441600559189828Y',
            '91441723MA528FPH0F',
            '91440300MA5EQA5R0W',
            '91420100MA49BF2C4L',
            '91320411MA20CJNX48',
            '91440101MA5D655570',
            '913205816878194687',
            '91440300MA5FCE0A53',
            '91440300573110602T',
            '91440300MA5FBWWF3E',
            '913701002644101930',
            '91440101MA59HH0F6C',
            '9135000073186315XM',
            '91510900MA6BUAG78G',
            '911201123006832458',
            '91120111MA05QCMJ13',
            '91120104MA05T0675K',
            '913205095546342245',
            '91440606MA52ER0G99',
            '91370500MA3W9X609H',
            '914403005879282880',
            '914403006911954087',
            '914406047857798855',
            '91440300063881781A',
            '91440400617499630B',
            '91330109MA2B0ADX07',
            '91440300MA5FHK6D1N',
            '91320582MA1Y11Q70P',
            '911301817761886534',
            '91350200MA3435MH5A',
            '91320602MA1Q3R4M84',
            '91110105633780350Y',
            '91370211MA3MPEAT8C',
            '91110108057377220T',
            '91330203580512749X',
            '91440300593013076K',
            '914212003523203074',
            '91330185MA2H2WPW5H',
            '91440300050455935K',
            '91320802398218076W',
            '91440300MA5DM24Y7E',
            '91440300MA5DP2894M',
            '91330108796674704T',
            '91320902MA1X7JLH1X',
            '9132068232117946XL',
            '91320582693389062Y',
            '91110111MA01DKP29M',
            '91370602599259755W',
            '91110302MA018EUHX8',
            '9113050239882130XR',
            '91370685683244759Q',
            '913205945866901674',
            '91321081MA1UQMFF6E',
            '91320211MA1XY2N73G',
            '91320602672003888Y',
            '914403003500385863',
            '91350205MA2XNBG59Y',
            '914419006886823790',
            '913202145512027762',
            '91150105797167819P',
            '91320205MA1W4XUW21',
            '91320206MA1UYQCE3H',
            '91460000MA5TLRDG4J',
            '91330503MA2B3C691H',
            '91130126682789976G',
            '91330281MA2H4HGP0G',
            '91440300359606332P',
            '914403006939911846',
            '914419005882970782',
            '913302035805420679',
            '914403006894108852',
            '91440300MA5F4P4480',
            '91110114769945564R',
            '91340100MA2RAP7M2L',
            '91450821MA5N94RC77',
            '913502136930326456',
            '91440300MA5FBCH51U',
            '91321182MA2547YA49',
            '914403000798364166',
            '91441900MA55JRPE1W',
            '91321181MA1WY5402N',
            '91440101781246053N',
            '91440101MA5CTW0F56',
            '91371400MA3DEB6N77',
            '914419003348934354',
            '914407036924399888',
            '91441900MA4WYA2H84',
            '91441900MA51N3529Q',
            '91440300561533798D',
            '91440101MA9UW90H9A',
            '91450126584349782X',
            '91370700MA3TKNX43M',
            '91440111331515295D',
            '91440300MA5ELMPB2B',
            '91320623679828436X',
            '91330201340543802C',
            '91440300MA5DCYLG0R',
            '91440114056566855M',
            '91441323MA52QBF3XP',
            '91440300MA5ELAJG2L',
            '91360824MA35ME9E74',
            '91442000684423927F',
            '914406040651944590',
            '911309827808006735',
            '91440300MA5F04FG7F',
            '91320804MA1UQCT483',
            '91440101MA9W0UF503',
            '91440513279770546U',
            '914403007954194297',
            '9144030008849020XM',
            '914419000946199467',
            '91441900MA4WJ9Q153',
            '91440300699088834H',
            '9144030006025036XP',
            '91370211MA3MFHAB48',
            '91440101795518160X',
            '91500109747457789Y',
            '91320921MA209YDC39',
            '91440881MA540X5790',
            '91350525MA34A2QQ4N',
            '91440300MA5G5PM06G',
            '91440604791225993E',
            '91430181090866042M',
            '91441300MA520ULA5G',
            '91441900MA53ACWE4H',
            '914401013045938291',
            '91360106MA35JD5911',
            '91350206671259478R',
            '91310118570774091W',
            '91130606568934818M',
        ];

        foreach ($list as $one) {

            $info = \App\HttpController\Models\EntDb\EntInvoice::create()
                ->addSuffix($one)
                ->where('nsrsbh', $one)
                ->get();

            if (empty($info)) {
                echo $one . PHP_EOL;
            }

        }


    }

    protected function run_bk($arg)
    {
        $redis = \EasySwoole\RedisPool\Redis::defer('redis');
        $redis->select(13);

        $fp = fopen('/mnt/tiaoma.log', 'r');

        $i = 1;

        // 0、1、2，其中 0 是提取关键字，1 是切字分词， 2 是获取词性标注。

        while (feof($fp) === false) {

            $str = trim(fgets($fp));

            $arr = explode('|||', $str);

            if (count($arr) !== 3) {
                continue;
            }

            $entname = trim($arr[1]);
            $terms = trim($arr[2]);

            if (empty($entname) || empty($terms)) {
                continue;
            }

            $jieba = jieba($terms, 0);

            if (!empty($jieba)) {
                $jieba = array_map(function ($row) {
                    if (preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $row) > 0) {
                        return $row;
                    }
                    return null;
                }, $jieba);
            } else {
                continue;
            }

            $jieba = array_values(array_filter($jieba));

            if (empty($jieba)) {
                continue;
            }

            foreach ($jieba as $one) {
                //ZINCRBY '每日信动（北京）有限公司' 1 '衬衫'
                $redis->zInCrBy($entname, 1, $one);
            }

            $i++;

            if ($i % 100000 === 0) {
                $o_o = date('Y-m-d H:i:s', time()) . " 已经处理到了第 {$i} 行";
                file_put_contents(
                    '/home/wwwroot/informationDance/Static/Log/jieba.log', $o_o . PHP_EOL, FILE_APPEND
                );
            }

        }

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

//mysql orm
CreateMysqlOrm::getInstance()->createMysqlOrm();
CreateMysqlOrm::getInstance()->createEntDbOrm();
CreateMysqlOrm::getInstance()->createRDS3Orm();
CreateMysqlOrm::getInstance()->createRDS3NicCodeOrm();
CreateMysqlOrm::getInstance()->createRDS3SiJiFenLeiOrm();
CreateMysqlOrm::getInstance()->createRDS3Prism1Orm();

CreateRedisPool::getInstance()->createRedis();

$conf = new Config();

$conf->setEnableCoroutine(true);

$process = new P($conf);

$process->getProcess()->start();

while (Swoole\Process::wait(true)) {
    var_dump('exit eee');
    die();
}
