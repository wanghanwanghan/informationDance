<?php

date_default_timezone_set('Asia/Shanghai');

use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Models\Api\AntEmptyLog;
use App\HttpController\Models\Api\JinCaiTrace;
use App\HttpController\Models\EntDb\EntInvoice;
use App\HttpController\Models\EntDb\EntInvoiceDetail;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPoolForEntDb;
use App\HttpController\Service\CreateMysqlPoolForMinZuJiDiDb;
use App\HttpController\Service\CreateMysqlPoolForRDS3NicCode;
use App\HttpController\Service\CreateMysqlPoolForProjectDb;
use App\HttpController\Service\CreateMysqlPoolForRDS3SiJiFenLei;
use App\HttpController\Service\CreateRedisPool;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use App\HttpController\Service\MaYi\MaYiService;
use App\HttpController\Service\OSS\OSSService;
use Carbon\Carbon;
use \EasySwoole\EasySwoole\Core;
use App\HttpController\Service\CreateDefine;
use \EasySwoole\Component\Process\Config;
use \EasySwoole\Component\Process\AbstractProcess;
use wanghanwanghan\someUtils\control;

require_once './vendor/autoload.php';
require_once './bootstrap.php';

Core::getInstance()->initialize();

class updateIsElectronics extends AbstractProcess
{
    protected function run($arg)
    {
        // $this->runOne();
        $this->runAll();
    }

    function runOne()
    {
        $list = [
            '山东三丰新材料有限公司 91371300328348377N',
            '山东省临沂市三丰化工有限公司 913713006140090858',
            '营口市向阳催化剂有限责任公司 91210800725501566A',
            '青岛祥泰碳素有限公司 91370285773512075D',
            '深圳市大恒数据安全科技有限责任公司 91440300MA5D92547C',
        ];

        $list = [
            '91210800725501566A',
            '91370285773512075D',
            '91440300MA5D92547C',
            '91330110MA2J0G5C16',
            '91330784MA2EEJ1T50',
            '92330702MA2E6JWCXB',
            '91320100608931604Y',
            '91320115302688381Y',
            '91320118MA205A3G5P',
            '913201137541039705',
            '91320611MA1XA6KB7N',
            '91330782MA2M29K2XY',
            '913201057360801035',
            '913201167652971170',
            '91320106MA1MWMKK4P',
            '91320115MA21NUU50T',
            '91330900MA7F00DX62',
            '913201110532704694',
            '92320114MA1R6QJQ00',
            '91320105MA213XANXH',
            '91320500765135302R',
            '91330110MA2H2NRNX1',
            '913205945837761683',
            '91320594079903279G',
            '913301023524529758',
            '91320508MA1MFTLH26',
            '92320118MA1QUH0B2R',
            '91320116686724061W',
            '91320105MA1YLTJUXN',
            '9132050578766626X2',
            '91320594747332134L',
            '91330302MA2CTFD62T',
            '913201023025544044',
            '91320106MA24XJ7000',
            '913205836600825070',
            '92320506MA21P6ET07',
            '913201186749116602',
            '91320594MA20N7KF56',
            '9132011559803305XM',
            '913200007665269890',
            '91330103MA2GNUQWXR',
            '91330110MA2CFM8N2D',
            '92320105MA203Y8G1T',
            '91320508MA1YHACA87',
            '92320114MA1QJDTL8W',
            '92320114MA2514GY19',
            '92320191MA22BMP793',
            '9132059458843196XM',
            '92320115MA23F6A13K',
            '913201147217253517',
            '913201027871155560',
            '913201040532656358',
            '91320102MA20L0X3XX',
            '91320116302411745E',
            '913205093139729947',
            '91330104MA27XKUE7U',
            '92320104MA22492527',
            '91320102787117914T',
            '91320114738860648P',
            '91320116773995441T',
            '91320509557095860P',
            '91330109MA2GK0303H',
            '91320594739577318W',
            '91320106MA22E92B67',
            '91320115MA1NFH9M56',
            '913201186089563182',
            '913201020841543645',
            '91320191MA1Y1EQW0P',
            '91320505MA1Y9FU43H',
            '91320105671319100C',
            '91320115MA1WTU4468',
            '91320106742357199U',
            '92320115MA21BHUN7H',
            '91320115MA1YXPKG69',
            '91320105MA21W1J50T',
            '91320594764159206U',
            '91330103MA2KKX8M9Q',
            '91320106575902232C',
            '92320106MA1YJC9M3K',
            '9132059474558360X2',
            '91320583MA1P6U0L8L',
            '91330105MAC4R03T69',
            '91331122MABYYEWJ05',
            '92330702MA8G67H657',
            '91320113MA20YATB8P',
            '91330110MA2KD5N26K',
            '91330106MA2KE8PH5A',
        ];

        foreach ($list as $key => $one) {
            // list($entname, $code) = explode(' ', $one);
            $info = (new JinCaiShuKeService())
                ->S000502($one);
            echo $info['msg'] . PHP_EOL;
        }

        dd('yes');
    }

    function runAll()
    {
        $list = AntAuthList::create()
            ->where('belong', 41)
            ->where('getDataSource', 2)
            ->where('isElectronics', '%属%成功%', 'not like')
            ->where('isElectronics', '%全电%', 'not like')
            ->where('isElectronics', '%平台密码%', 'not like')
            ->where('isElectronics', '%非一般%', 'not like')
            ->where('isElectronics', '%不存在%', 'not like')
            ->where('isElectronics', '%未配置软证书信息', 'not like')
            ->all();

        foreach ($list as $key => $one) {
            if ($one->getAttr('id') - 0 <= 551) {
                // continue;
            }
            $info = (new JinCaiShuKeService())
                ->S000502($one->getAttr('socialCredit'));
            $one->update(['isElectronics' => $info['msg']]);
            echo $one->getAttr('id') . '|' . $one->getAttr('socialCredit') . '|' . $info['msg'] . PHP_EOL;
        }

        dd('yes');
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

//mysql orm
CreateMysqlOrm::getInstance()->createMysqlOrm();
CreateMysqlOrm::getInstance()->createEntDbOrm();
CreateMysqlOrm::getInstance()->createRDS3Orm();
CreateMysqlOrm::getInstance()->createRDS3NicCodeOrm();
CreateMysqlOrm::getInstance()->createRDS3SiJiFenLeiOrm();
CreateMysqlOrm::getInstance()->createRDS3Prism1Orm();

CreateRedisPool::getInstance()->createRedis();

for ($i = 1; $i--;) {
    $conf = new Config();
    $conf->setArg(['foo' => $i]);
    $conf->setEnableCoroutine(true);
    $process = new updateIsElectronics($conf);
    $process->getProcess()->start();
}

while (Swoole\Process::wait(true)) {
    var_dump('exit eee');
    die();
}
