<?php

namespace App\HttpController\Business\Test;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\DaXiang\DaXiangService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\LongDun\LongDunService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\QianQi\QianQiService;
use Carbon\Carbon;
use wanghanwanghan\someUtils\control;

class TestController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return true;
    }


    function getInv(): bool
    {
        $page = $this->request()->getRequestParam('page');
        $NSRSBH = $this->request()->getRequestParam('NSRSBH');
        $KM = $this->request()->getRequestParam('KM');// 1 进项

        $FPLXDMS = [
            '01', '02', '03', '04', '10', '11', '14', '15'
        ];

        $KPKSRQ = Carbon::now()->subMonths(23)->startOfMonth()->format('Y-m-d');//开始日
        $KPJSRQ = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');//截止日

        foreach ($FPLXDMS as $FPLXDM) {
            $res = (new DaXiangService())
                ->getInv('91110108MA01KPGK0L', $page . '', $NSRSBH, $KM, $FPLXDM, $KPKSRQ, $KPJSRQ);
            $content = jsonDecode(base64_decode($res['content']));
            CommonService::getInstance()->log4PHP($content);
        }

        return $this->writeJson(200, null, control::getUuid());
    }

    function test()
    {
        $list = [
            '安庆炼化曙光丁辛醇化工有限公司',
            '安徽昊源化工集团有限公司',
            '苏州嘉玺新材料供应链有限公司',
            '江苏斯尔邦石化有限公司',
            '浙江巴德富供应链管理有限公司',
            '江苏中信国安新材料有限公司',
            '天津虹致新材料有限公司',
        ];

        $fp = fopen('res.txt', 'w+');

        foreach ($list as $ent) {
            $postData = [
                'entName' => $ent,
                'code' => '',
                'beginYear' => 2020,
                'dataCount' => 3,
            ];
            $res = (new LongXinService())
                ->setCheckRespFlag(true)
                ->setCal(true)
                ->getFinanceData($postData, false);

            CommonService::getInstance()->log4PHP($res);

            foreach ($res['result'] as $year => $val) {

                //            'ASSGRO',//0资产总额
                //            'LIAGRO',//1负债总额
                //            'VENDINC',//2营业总收入
                //            'MAIBUSINC',//3主营业务收入
                //            'PROGRO',//4利润总额
                //            'NETINC',//5净利润
                //            'RATGRO',//6纳税总额
                //            'TOTEQU',//7所有者权益
                //            'C_ASSGROL',//9净资产
                //            'A_ASSGROL',//10平均资产总额
                //            'CA_ASSGRO',//11平均净资产
                //            'A_VENDINCL',//15企业人均产值
                //            'A_PROGROL',//16企业人均盈利
                $insert = [
                    $year,
                    $val['VENDINC'],
                    $val['ASSGRO'],
                    $val['LIAGRO'],
                    $val['RATGRO'],
                    $val['MAIBUSINC'],
                    $val['TOTEQU'],
                    $val['PROGRO'],
                    $val['NETINC'],
                    $val['CA_ASSGRO'],
                    $val['A_ASSGROL'],
                    $val['SOCNUM'],
                ];

                file_put_contents($fp, implode('|', $insert) . PHP_EOL, FILE_APPEND);
            }
        }

        fclose($fp);

        return $this->writeJson(200, null);
    }

    function test1()
    {
        $arr = [
            '913201927770424021',
            '91130127MA088FE240',
            '91331023MA2DWUWR3R',
            '91511402MA62J7BR34',
            '91330782MA2ECM9G7J',
            '91140303MA0L9JN115',
            '91370213MA3CE0BP4D',
            '934508033225813383',
            '91340111MA2UJY4BXG',
            '91310120MA1HWAWMXL',
            '91440101MA5D64NP2C',
            '91610700MA6YU3E0XR',
            '913310033278392547',
            '93411422MA457KNLXW',
            '91469001MA5TXG2411',
            '91410100MA9GXTEM60',
            '91411322MA9FHKNY9G',
            '91511126MA688FLN13',
            '91210102313196104K',
            '93530627MA6PC4NQ64',
            '91411600MA9G2W1C0X',
            '91410103MA40MJCTXC',
            '91510105MA651DUG5T',
            '91410300MA9FA6NY2E',
            '91440101MA5D2HF19J',
            '91371621MA3Q3U821X',
            '91441900MA56AU7R3N',
            '91522601MA6DJNP562',
            '91130924MA0FFEHU4R',
            '91320412MA1MAUT344',
            '911301057941716794',
            '91330122MA2CEGQL9C',
            '91320509MA1T4UKL8K',
            '91220201589492975B',
            '91110108MA01WCPJXB',
            '91440205MA4UM3BC8Y',
            '9144030069397187XT',
            '91140700MA0JRXJ613',
            '91370781MA3CKCG740',
            '91420500MA499CDK0D',
            '91421200343521262M',
            '91450302MA5Q07RF3Q',
            '91340111MA2WH04J39',
            '91371602MA3TM3KP9D',
            '9136092659889073XG',
            '91440300MA5EH9HG5R',
            '91120112MA05R70H7F',
            '91330782MA2EDKR19D',
            '91421381MA495D988Q',
            '91510106709293410L',
            '91530121MA6N3RP67F',
            '91371482MA947XJG7P',
            '91110111306788267R',
            '914201066823340668',
            '91430103MA4TDL1Q8N',
            '91440101340130215J',
            '91440300MA5FBPWX1J',
            '91110105MA00B83LXD',
            '91530802MA6K6NAR6K',
            '91210921MA0UNBJ6XR',
            '91370211MA3QUQ9WXQ',
            '91440101MA5AN4HMXM',
            '91440101MA5CXRGK6R',
            '91441400MA51G8N53M',
            '914602005892830576',
            '93430821MA4PK4P495',
            '9115010531850193X6',
            '913205056993924306',
            '91110111102790066R',
            '91440300MA5EN8EL7J',
            '913101061346941323',
            '91442000MA56G1482H',
            '91410781MA9GHPT97H',
            '93654221MA788HQTXC',
            '91360681065367356N',
            '91610830MA70CK2Q50',
            '91440300MA5GNHJN1Y',
            '91440606MA4UQA1X7H',
            '91440101MA5AWPP66Q',
            '91140624MA0GR8UX1L',
            '91440300MA5F8D5X0D',
            '91370481MA3D0MTU4E',
            '91500230573402592C',
            '93640303MA75YF4KX5',
            '91420200MA498N0T6E',
            '91110117335454207E',
            '91620123MA71WYAX10',
            '91110116MA01EKF04N',
            '91210882MA0YRKJD7U',
            '92330110MA2BJMA679',
            '93220381068616389J',
            '91321002MA1WT8LF4B',
            '91150823MA0MX8UT70',
            '91640100MA771YYM0J',
            '91440101MA59QYM09H',
            '91441300MA55AE8714',
            '91110115565847348K',
            '91630105MA7583583J',
            '9143011133841856X0',
            '91441900MA54QK8L97',
            '91331003MA28H3G27U',
            '91310114342464542D',
            '91450702MA5PFJDR6X',
            '91330183MA2GP2GE3C',
            '91440300MA5EXK5C3G',
            '91330109MA2GYY4413',
            '91440101MA9UN6YRXN',
            '91331004670296779X',
            '91350525726440320J',
            '91440101MA59GGMD13',
            '91440101MA5CYCHE8A',
            '91370923MA3PLGRG6X',
            '91440101MA9UYHD48W',
            '91370323493219798Q',
            '91131022MA0GD7AJ0X',
            '91320102MA1MFMKY6T',
            '91341602MA2U3EH51T',
            '91430281MA4QK3DWXJ',
            '91653101MA78XL0B45',
            '91410381MA9FL7BA2A',
            '91420106MA4KLWTP39',
            '91120113758124736A',
            '914406050795021099',
            '9131011878516387X8',
            '91340403MA2W3YEG9U',
            '91110101330350089W',
            '91440300MA5DNEKK27',
            '91610621MA6YE1WM1D',
            '91620200MA726GPN5W',
            '91110114MA0049JM48',
            '93430181591042586A',
            '91440101MA59RF440L',
            '91440300MA5GDXQM7K',
            '91320312MA1NMPWJ7X',
            '91130229MA09UNXU7J',
            '93419001MA9FGHXJ6C',
            '91441900MA54UA9R98',
            '91310120MA1HW27J43',
            '93441622MA543C9B6B',
            '91440101MA9W1K7U2G',
            '91530129MA6P8R2E57',
            '912102133114682922',
            '91441900MA56AXBN4U',
            '914403005554062152',
            '913100003424589278',
            '93140221MA0GWYUW1K',
            '91440101MA5CWLFA6Y',
            '91440101MA9XW71M38',
            '91210204MA0Y2J7J0M',
            '91530112MA6PB5YK4A',
            '91520201356365174B',
            '91440101MA5B73A068',
            '915001063203322909',
            '91370214MA3TX57K01',
            '91350200MA2Y11U19Q',
            '91370203724007777X',
            '91371301MA3M7X350Q',
            '91421000MA48C7GF0D',
            '91140502MA0L016B7J',
            '91310117568047645K',
            '91330621MA2JTQEKX2',
            '91310118MA1JM8NT13',
            '913101157476097118',
            '91331022MA2DUT0R6N',
            '91320583591171951X',
            '91130802347755509P',
            '91440101MA9UNEJU0L',
            '91220602555292115N',
            '91321023MA1TBFHF7A',
            '912308003332252912',
            '91230109MA1CHXRUXM',
            '91440511MA4UNX7D82',
            '9332111255378911XY',
            '91131082MA09TX521J',
            '91320509MA21R7FW5K',
            '91150781MA0QTX615U',
            '91130182MA0EQY3B34',
            '912301047875169904',
            '91210303673775003T',
            '935325263228451532',
            '91131082MA07WDL07Y',
            '913201052496888322',
            '932207245988258821',
            '91320102762105570L',
            '91310230MA1HGMCR97',
            '91360313MA37X5JR4K',
            '91500225MA6003HM47',
            '91370214350422609T',
            '91350603MA334GY23C',
            '91130921MA0CHR7849',
            '91320506MA21KQ0W39',
            '91130902063383969H',
            '91320214MA1X787R4M',
            '91522702MAAJTD5E2Y',
            '91440300MA5EHKQH0B',
            '91371102MA3C1D75X4',
            '91230102MA19A7385Q',
            '91440101MA9W4FYM14',
            '9141010539985949XJ',
            '91440101MA59J3Y61G',
            '9113020331985741XP',
            '91320506MA1YMD7N8Y',
            '91310115798900330P',
            '91110115MA01EB1U61',
            '914416020537498167',
            '91340100588865039E',
            '91520198MAAL2MHM3W',
            '91411081MA9GW9218C',
            '91310120MA1HMFQR19',
            '91350502050335968J',
            '91310115MA1HAEKP90',
            '914210007959307298',
            '91440300MA5EFE9X6H',
            '91370600MA3TTCP416',
            '91511702MA6B490Q7R',
            '91310116MA1JAD33X8',
            '91440300MA5FBDEQ78',
            '91370213MA3MF1QM25',
            '91440400MA52R8250N',
            '91371081MA3TBDU21M',
            '93370682MA3TR3NP5A',
            '91410300MA9G0AFN96',
            '91320583628384847L',
            '91360826MA365YHP0C',
            '91510104MA65RH057L',
            '91340111MA2U07PH5N',
            '91370104MA3QKYE58G',
            '93370283MA3CK2YL6P',
            '91610102MA714AHR9G',
            '91440300083406267E',
            '91330782MA2E6TR07J',
            '91421222MA48UP9Q36',
            '914420003038148104',
            '91610502MA6Y8X4J3E',
            '91440300MA5F86D7XB',
            '91310113090096922T',
            '91320924553848538J',
            '91130128095342483D',
            '9111022831799439XQ',
            '913204136608334384',
            '91440101MA59EUU46B',
            '91430105MA4T74JR1D',
            '91430112MA4QNTAD2Q',
            '91632300MA752EH28U',
            '91310230MA1HFX1H1R',
            '91140105MA0LGL6J4C',
            '91420500MA48BB263G',
            '91440101MA9UK0DH1L',
            '91510107MA61WG1Q6U',
            '91460000MA5TN9GA6F',
            '91440101MA5D595R2A',
            '91441302MA4X472M8R',
            '91510181MA6A585T52',
            '91330201MA2CLYW7XY',
            '91440605314860134B',
            '93610122MA6U31E623',
            '91441900663331994U',
            '91330703MA2M0Q677M',
            '91310113MA1GP4LQ7D',
            '91420112MA4KX02Y39',
            '91310114554286573H',
            '913702036937618726',
            '913411000739458658',
            '91341204MA8LH7UU98',
            '91370811MA3D9QLQ0G',
            '91310104069327191T',
            '91350581MA3485XA51',
            '91310112777140520N',
            '915113025632578622',
            '91310115087809899W',
            '91370104MA3TLKGMXT',
            '91360122MA39C0K46X',
            '91440606071858928C',
            '91440300MA5F7FKY41',
            '91230111MA19H5M38U',
            '91370214MA3NL8CF9T',
            '91500107MA5YULYP9E',
            '91340300MA2RP3WR4L',
            '91440605MA51URAX80',
            '91140822MA0LB24L9H',
            '91441900MA55HAJQ40',
            '91131082MA08B251XE',
            '91330282309092943M',
            '91110105306699782F',
            '91621125MA73XMJM3G',
            '91360111MA388F9T92',
            '91130705MA0DHF272G',
            '91330782MA2EC1E43M',
            '91330681329939769L',
            '91321182562977972C',
            '91130632MA0FL9DY0F',
            '91120222340870475B',
            '91371302MA3CGFME6J',
            '93500118054266445K',
            '91520324MA6HXW2Y12',
            '91440300MA5GFXKW1B',
            '91440300MA5D95YX7R',
            '91440703MA544J5Q8R',
            '91330783MA28Q7RT8H',
            '91410102395864611E',
            '914401153314681935',
            '91450603MA5PL3M28D',
            '91610924MAB2XG3YXH',
            '91440300MA5EDKWX88',
            '91510108MA68YE8E4L',
            '91522327709550368L',
            '91442000MA52KUFUX0',
            '91350200791294251A',
            '93230184MA1907E722',
            '91440606MA539M8Q15',
            '914401013474484290',
            '93630122MA752UTJ7W',
            '93430726MA4P921G9U',
            '91440507MA545YT20F',
            '91310115MA1H94W831',
            '91360922MA38FFLC40',
            '91440705MA55J11Q6X',
            '9161070068795394XQ',
            '91140525MA0L1LPG9R',
            '91330109MA2H1UUF70',
            '91330108399346045Y',
            '91370281MA3EW1RUXN',
            '91320594MA1MYRR183',
            '91130534MA07T7MC99',
            '91441900MA53GAR055',
            '91310118778511311L',
            '91230102MA18XP493D',
            '9133062159436162X0',
            '91310113MA1GKFCL1R',
            '913205830915443444',
            '91130108MA0EKEA94D',
            '91430104MA4LX3Y40X',
            '91441900MA4X4KWN2L',
            '91320282MA1MEDTU0C',
            '91220302MA17L2U066',
            '91431002MA4PBUCT2Q',
            '91110106MA00747HXK',
            '911101143482784924',
            '91440101340234620A',
            '93230722090385150E',
            '91350902MA2YF3YF08',
            '91350200MA2YK9PA67',
            '91410303MA44EW0953',
            '91441300617908801X',
            '91330122MA2H2LA16W',
            '91330106790943467G',
            '91450203MA5PU92BXQ',
            '91420112MA4KY8G608',
            '91620923MA72G9839R',
            '911101147899659921',
            '914301053293485985',
            '91510703309414471J',
            '913309030762319560',
            '91150627561221620D',
            '91220200MA17BRY480',
            '91320200559318837L',
            '912301023009011420',
            '91440101MA9UW46H3M',
            '91310116MA1JDUKA8M',
            '91310114MA1GUWLG96',
            '91331002MA2KA1751J',
            '91430111MA4L8TA93J',
            '91520490MAAJXKDC8M',
            '91120113MA0778C26G',
            '91310116MA1J8P0H7A',
            '91350121MA344253XM',
            '91371323MA3F954712',
            '91500108MA60AC9K6K',
            '91420106MA4L0GB4X2',
            '91330104070985554Q',
            '91310115MA1H958H7C',
            '91310230MA1K08K9XP',
            '91411122MA475P057M',
            '91361100MA383U5508',
            '91410703MA9FP45G75',
            '91650106098171413H',
            '91441900MA55KT5L2Y',
            '91510107567150632Q',
            '91410728MA4721UM5C',
            '91440101MA5D38AA1D',
            '91320381MA22CL486D',
            '93150802MA0N87CB7G',
            '91430903MA4PFDFD63',
            '91520102MA6HLD4P34',
            '91140106MA0L23NCXM',
            '91330481MA2B9YX103',
            '91440101MA9UK0NKXG',
            '91410105MA4428R7XE',
            '91640300MA760P5W7Y',
            '91330782098810553C',
            '91330602MA2BFY2J30',
            '91440300MA5G9TA45P',
            '91650402580244211U',
            '91411403MA9G12PC6T',
            '91411724MA45A2DH0U',
            '91440101MA5AYRKW3D',
            '91321112793823735N',
            '91441602MA53DMGQ4D',
            '91350111MA346A4A3G',
            '91420528097276977P',
            '91141023MA0HFLDC2H',
            '91430702MA4T5YGB2N',
            '91410403MA47A6124D',
            '91330183MA2J293026',
            '91440300568544780X',
            '91440101MA5BUNFG9J',
            '91520522MA6HNHC01K',
            '91310118695781906N',
            '91451103MA5Q3LEN0X',
            '91330212MA2GU3QJ8X',
            '91370203321439073R',
            '91440101MA5C4HFA23',
            '91610116MAB0R9N217',
            '91370982MA3N2FD67U',
            '91410700MA482X31X4',
            '91330681MA2D648J20',
            '91511402MA68HA8D21',
            '913609833328993660',
            '91510105MA6CMEGM92',
            '91330212MA2CH2XF2M',
            '913101125931805951',
            '913401810852216886',
            '91371702MA3CHYGX0U',
            '91110105327259989H',
            '9144030034281432XB',
            '91451000099453295L',
            '91330109MA2AXUCF0U',
            '934114260742258278',
            '91650109MA77C00J7D',
            '91440300MA5G17EQ72',
            '91341203MA2NXF4P24',
            '91120222MA05KA4627',
            '91440300MA5EHG2D41',
            '91320412MA1Q2XEJ84',
            '91150781MA0QAJAX0L',
            '913207073021628735',
            '91130981MA0FMEXU4X',
            '91341200MA2MTJKR9H',
            '91440300MA5EXF771K',
            '91210114MA10EPPM2X',
            '91530111MA6PA65M3C',
            '91330182MA2J14QU58',
            '91350181MA359JFY1D',
            '91130982MA0EE5A89J',
            '91320214MA204PLJX6',
            '91410611MA40QFE81C',
            '91330382MA2AWL220C',
            '91330327MA2JCXK27P',
            '93321023553781521Y',
            '91371324168454209P',
            '91220381MA14AU5W2N',
            '91320104MA1MQAWP6R',
            '91150100MA0MY6PQ0C',
            '91371102MA3ELHRJ3C',
            '91460100MA5REPYX1W',
            '91450102MA5PCL4R89',
            '91620103MA74KBAF94',
            '91110112102398240E',
            '91310115792773457N',
            '91520502MA6JABBD66',
            '91310230MA1HGRML7U',
            '91450722321803795A',
            '91320402MA21T97K2F',
            '91150691MA13UR8G0C',
            '91411023MA9G6Q7E0F',
            '91430302MA4L5PJN89',
            '91510100MA6A393U2U',
            '91350921MA32E7D615',
            '91422801MA493WJH66',
            '91350105MA8RD0N11L',
            '913301086917250189',
            '91610802MA70E6UT0G',
            '91441900MA52NTGB4R',
            '91440300MA5GG3QG2A',
            '91310230MA1HH6UY27',
            '91440101MA59LJYG8C',
            '91510502MA671M0Q2Q',
            '91510114MA64QNRC60',
            '91441900MA4WGDW77K',
            '91330282MA2J6UP952',
            '91430111MA4QGY4H2T',
            '91321322MA20N9EBX7',
            '91220582MA17039G1K',
            '91340881563429433H',
            '91440507MA51AUFC7A',
            '91620102MA71BTWN11',
            '91110106MA00FG811N',
            '91411600MA47JPQLX4',
            '93410423MA40H7JN5R',
            '91330212316954986W',
            '91330100MA2J0Y7725',
            '91310105MA1FWGXX08',
            '91120116MA072EQ404',
            '91340123MA2W70112J',
            '91330109084589763P',
            '91640181MA76E61F7C',
            '914419000553214850',
            '91440183MA59E4GH2G',
            '91440300335161770W',
            '931301810799939136',
            '91320791MA20Y2JE7E',
            '91540192MA6TFJB407',
            '91210106MA110UAD19',
            '91420104MA4KP9DL8W',
            '91530122MA6KT7UD0E',
            '91210700MA0YAJU21P',
            '91360782MA39RHLWXY',
            '91440300MA5FY9RJ7N',
            '91450107MA5KCY6E56',
            '915101063945965124',
            '91340500MA2W03323U',
            '91442000MA5314J22L',
            '91450103552256792H',
            '91360902MA3ACRNK0D',
            '913301857434973299',
            '91371700MA3NKG2M97',
            '91370105MA3WECU287',
            '91310230MA1JY7WM48',
            '91500107597969534Q',
            '91371312MA9449NT7F',
            '913102306727311059',
            '913305220928184749',
            '91140311MA0KXMF19N',
            '91131024MA0F1JEE1G',
            '913212025754371822',
            '91220800MA0Y61PC0P',
            '93320981338926863B',
            '914403005586522003',
            '913310812554963194',
            '91131127MA0D71QB5H',
            '91321112MA1YC6M797',
            '91130826MA0DW1NF56',
            '93321023MA1MQ0YP1Y',
            '91350203581269066Y',
            '91500226595166658U',
            '91371000MA3NGMW04E',
            '91320582MA1QG44M6H',
            '9113020367602765X7',
            '93341125MA2T9JG0XR',
            '91440300MA5FWWFJ28',
            '91500113586851362K',
            '91370600MA3R69H88T',
            '91350103MA2XXAEN5M',
            '91640106MA75WBAN0B',
            '91320621MA1P5NX286',
            '91131003MA07MX8T13',
            '91510823MA6252RY2J',
            '91120222MA05JWK184',
            '91130282MA0CWHX76X',
            '913202063309656308',
            '93510626665386462B',
            '91420100MA4KY6M040',
            '91330383MA2JA7YX8R',
            '91460108MA5U1U4X6R',
            '91210104788734092B',
            '91340800591449407R',
            '91440106MA59B6UE6Y',
            '91360881MA3ACTCG3A',
            '91371302MA3C78TR9B',
            '91421000MA499D7K5L',
            '91440300MA5ER9HL0C',
            '91310120MA1HWCWG6N',
            '913202115837677719',
            '91130124MA0FDX6U52',
            '91310120MA1HY5U98W',
            '91440101MA5AL1MH1C',
            '91520402MA6HMQAAX0',
            '911201160731038520',
            '913102307495858050',
            '91440101MA5CUAPQX2',
            '9322032256995532XA',
            '91320903MA209TE24Q',
            '91320211MA1MYTP88F',
            '91131082MA0EHPUE67',
            '91500105MA602G82X7',
            '91370321MA3NQ4989X',
            '91320302MA221YLT11',
            '91220102MA14TYA66J',
            '91310115MA1HA0AP7H',
            '9135010374380179XX',
            '91370203MA3EJJJB16',
            '913702113341960152',
            '916206020999505748',
            '91110116764230658T',
            '91430111MA4PABXJ7Y',
            '91110108MA0188D9XH',
            '91410300MA9GXXY39H',
            '93330327560989451U',
            '91371200MA3C50WT1K',
            '91310105350984986M',
            '91371600MA3WPBLW37',
            '92330185MA2CC9M36K',
            '91441900MA545J9U11',
            '91370600MA3NLP1K6Y',
            '935001160628697214',
            '91320506MA1R9XCF18',
            '91120116MA05JG3J3R',
            '91520382MAAK8B0QXP',
            '91110108599677455X',
            '91440605MA4WX52A1P',
            '93450804692789718M',
            '9321132239967054X3',
            '91530113MA6PXTM18J',
            '91340203MA2TH07W6B',
            '91440101MA9XQ3JN2T',
            '91420104MA49QWKX9J',
            '91370281321445940A',
            '91500112MAABPKGLXQ',
            '914302111842811259',
            '911101170785971695',
            '913205097149576058',
            '91130602235957982G',
            '91445381MA569EF12F',
            '91610000067919329N',
            '93500234MA5U6G8D7U',
            '91371312MA3PDEBQ7L',
            '91411300MA459Q6U2E',
            '914201065584279434',
            '91370685MA3T6U7M88',
            '91370104MA3R0G3T7Y',
            '91341323355150251N',
            '91511323MA68KBX558',
            '93451102052739210Q',
            '91140822MA0MT5AF5E',
            '91370883073038237B',
            '91330185MA2CD4Y422',
            '91350102MA321BRK5H',
            '91420103MA4KXN291B',
            '91110105600030502R',
            '93513226MA62F0L21G',
            '914401113400808523',
            '91110115MA00D8982E',
            '91650104MA796K3Q7C',
            '91410105317213725B',
            '91420111MA4K2NQH0Y',
            '91441900MA4ULKNM7P',
            '91321002053511410R',
            '91130821336004945E',
            '913101205931670240',
            '91320322MA1MXLLMXW',
            '91420100MA49FJFT91',
            '91440300MA5G09F780',
            '915101070574654238',
            '91310000MA1HK9PL98',
            '91320324MA1Y58KT3F',
            '91440101MA5CC2DK4Q',
            '9152010233739544X9',
            '91140502MA0KE8HU7X',
            '914403003195151612',
            '91130922789848147M',
        ];

        foreach ($arr as $val) {
            $postData = ['keyWord' => $val];
            $getRegisterInfo = (new LongDunService())
                ->setCheckRespFlag(true)
                ->get('http://api.qichacha.com/ECIV4/GetBasicDetailsByName', $postData);
            if ($getRegisterInfo['code'] !== 200) {
                CommonService::getInstance()->log4PHP([
                    'code' => $val
                ]);
                continue;
            }
            $entName = $getRegisterInfo['result']['Name'];
            $status = $getRegisterInfo['result']['Status'];
            $esData = $getRegisterInfo['result']['StartDate'];
            $cDate = $getRegisterInfo['result']['CheckDate'];
            //=====getRegisterInfo=====getRegisterInfo=====getRegisterInfo=====getRegisterInfo=====
            $token_info = (new CoHttpClient())
                ->useCache(false)
                ->send('https://openapi.ele-cloud.com/api/authen/token', [
                    'appKey' => 'JczSaWGP76LYdIOfHds52Thk',
                    'appSecret' => 'BszCebdj6nOglZLBrYYUspWl',
                ], [], [], 'postjson');
            $token = $token_info['access_token'];
            list($usec, $sec) = explode(' ', microtime());
            $cn_time = date('YmdHis', time()) . round($usec * 1000);
            $id = str_pad($cn_time, 17, '0', STR_PAD_RIGHT) . str_pad(mt_rand(1, 999999), 15, '0', STR_PAD_RIGHT);
            $arr = [
                'zipCode' => '0',
                'encryptCode' => '0',
                'dataExchangeId' => $id . '',
                'entCode' => '91110108MA01KPGK0L',
                'content' => base64_encode(jsonEncode(['nsrmc' => $entName]))
            ];
            $info = (new CoHttpClient())
                ->useCache(false)
                ->send("https://openapi.ele-cloud.com/api/eplibrary-service/v1/exactQuery?access_token={$token}",
                    $arr, [], [], 'postjson');
            if ($info['returnStateInfo']['returnCode'] - 0 !== 0) {
                CommonService::getInstance()->log4PHP([
                    'entName' => $entName,
                    'code' => $val,
                    'returnCode' => $info['returnStateInfo']['returnCode'],
                    'msg' => base64_decode($info['returnStateInfo']['returnMessage'])
                ]);
                continue;
            }
            $content = base64_decode($info['content']);
            $content = jsonDecode($content);
            $content['data']['status'] = $status;
            $content['data']['start'] = $esData;
            $content['data']['check'] = $cDate;
            file_put_contents(LOG_PATH . 'ent.log', jsonEncode($content, true) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }

    //产品标准页面用
    function product()
    {
//        //地图
//        try {
//            $mysqlObj = Manager::getInstance()
//                ->get(CreateConf::getInstance()->getConf('env.mysqlDatabaseMZJD'))
//                ->getObj();
//            $sql = 'SELECT * FROM qyxx_copy1';
//            $list = $mysqlObj->rawQuery($sql);
//            $list = obj2Arr($list);
//            foreach ($list as $val) {
//                $map[$val['XZQH_NAME']] = $val['num'];
//            }
//        } catch (\Throwable $e) {
//            $this->writeErr($e, __FUNCTION__);
//        } finally {
//            Manager::getInstance()
//                ->get(CreateConf::getInstance()->getConf('env.mysqlDatabaseMZJD'))
//                ->recycleObj($mysqlObj);
//        }
//
//        //标准一级分类和个数
//        try {
//            $mysqlObj = Manager::getInstance()
//                ->get(CreateConf::getInstance()->getConf('env.mysqlDatabaseMZJD'))
//                ->getObj();
//            $sql = 'select JJHYDM,count(1) as num from cpxx where JJHYDM is not null group by JJHYDM';
//            $list = $mysqlObj->rawQuery($sql);
//            $list = obj2Arr($list);
//        } catch (\Throwable $e) {
//            $this->writeErr($e, __FUNCTION__);
//            $list = [];
//        } finally {
//            Manager::getInstance()
//                ->get(CreateConf::getInstance()->getConf('env.mysqlDatabaseMZJD'))
//                ->recycleObj($mysqlObj);
//        }
//
//        $JJHYDM = $tmp = [];
//
//        foreach ($list as $index => $val) {
//            if (preg_match('/[\xe0-\xef][\x80-\xbf]/', $val['JJHYDM'])) {
//                if (isset($JJHYDM[trim($val['JJHYDM'])])) {
//                    $JJHYDM[trim($val['JJHYDM'])] += $val['num'];
//                } else {
//                    $JJHYDM[trim($val['JJHYDM'])] = $val['num'];
//                }
//            }
//        }
//
//        arsort($JJHYDM);
//
//        foreach ($JJHYDM as $key => $val) {
//            $tmp[] = ['name' => $key, 'num' => $val];
//        }
//
//        return $this->writeJson(200, null, [
//            'k' => array_keys($map), 'v' => array_values($map), 'JJHYDM' => $tmp
//        ]);

        $k = [
            'HongKong' => '香港(0)',
            'Macau' => '澳门(0)',
            'Taiwan' => '台湾(0)',
            'Shanghai' => '上海(168797)',
            'Yunnan' => '云南(34691)',
            'InnerMongolia' => '内蒙(45638)',
            'Beijing' => '北京(137591)',
            'Jilin' => '吉林(45630)',
            'Sichuan' => '四川(27649)',
            'Tianjin' => '天津(67830)',
            'Ningxia' => '宁夏(47850)',
            'Anhui' => '安徽(56724)',
            'Shandong' => '山东(15672)',
            'Shanxi' => '山西(39472)',
            'Guangdong' => '广东(180820)',
            'Guangxi' => '广西(20307)',
            'Xinjiang' => '新疆(29289)',
            'Jiangsu' => '江苏(84037)',
            'Jiangxi' => '江西(56671)',
            'Hebei' => '河北(56749)',
            'Henan' => '河南(47687)',
            'Zhejiang' => '浙江(90127)',
            'Hainan' => '海南(19273)',
            'Hubei' => '湖北(67840)',
            'Hunan' => '湖南(59675)',
            'Gansu' => '甘肃(8982)',
            'Fujian' => '福建(60784)',
            'Tibet' => '西藏(2099)',
            'Guizhou' => '贵州(58987)',
            'Liaoning' => '辽宁(45678)',
            'Chongqing' => '重庆(44044)',
            'Shaanxi' => '陕西(39476)',
            'Qinghai' => '青海(7890)',
            'Heilongjiang' => '黑龙江(50472)',
        ];

        $JJHYDM = [
            'wanghan' => [
                'name' => 'hanwang',
                'num' => '666',
            ]
        ];

        $arr = [
            'k' => $k,
            'v' => null,
            'JJHYDM' => $JJHYDM,
        ];

        return $this->writeJson(200, null, $arr);
    }

    function caiwu()
    {
        $entList = $this->request()->getRequestParam('entList') ?? '';

        $entList = str_replace('，', ',', $entList);

        if (empty($entList)) return $this->writeJson(201, null, null, '公司名称不能是空');

        $entList = explode(',', $entList);

        $entList = array_filter($entList);

        if (empty($entList)) return $this->writeJson(201, null, null, '公司名称不能是空');

        $temp = [];

        foreach ($entList as $entName) {
            $entName = trim($entName);
            $res = (new QianQiService())
                ->setCheckRespFlag(true)
                ->getThreeYears(['entName' => $entName]);
            if ($res['code'] === 200 && !empty($res['result'])) {
                foreach ($res['result'] as $key => $val) {
                    if (empty($val)) {
                        $temp[] = [$entName, $key, '无数据', '无数据', '无数据'];
                    } else {
                        $range = (new QianQiService())->wordToRange($val);
                        $temp[] = [$entName, $key, 'ASSGRO_REL 资产总额', $val['ASSGRO_REL'], $range['ASSGRO_REL']];
                        $temp[] = ['', '', 'LIAGRO_REL 负债总额', $val['LIAGRO_REL'], $range['LIAGRO_REL']];
                        $temp[] = ['', '', 'VENDINC_REL 营业总收入', $val['VENDINC_REL'], $range['VENDINC_REL']];
                        $temp[] = ['', '', 'MAIBUSINC_REL 主营业务收入', $val['MAIBUSINC_REL'], $range['MAIBUSINC_REL']];
                        $temp[] = ['', '', 'PROGRO_REL 利润总额', $val['PROGRO_REL'], $range['PROGRO_REL']];
                        $temp[] = ['', '', 'NETINC_REL 净利润', $val['NETINC_REL'], $range['NETINC_REL']];
                        $temp[] = ['', '', 'RATGRO_REL 纳税总额', $val['RATGRO_REL'], $range['RATGRO_REL']];
                        $temp[] = ['', '', 'TOTEQU_REL 所有者权益', $val['TOTEQU_REL'], $range['TOTEQU_REL']];
                        $temp[] = ['', '', 'SOCNUM 社保人数', $val['SOCNUM'], ''];
                    }
                }
            }
        }

        if (!empty($temp)) {
            $config = [
                'path' => OTHER_FILE_PATH,
            ];
            $fileName = 'tutorial01.xlsx';
            $xlsxObject = new \Vtiful\Kernel\Excel($config);
            $filePath = $xlsxObject->fileName($fileName, 'sheet1')
                ->header(['企业名称', '年', '字段', '数值', '区间'])->data($temp)->output();
            $this->response()->redirect('/Static/OtherFile/' . $fileName);
        } else {
            return $this->writeJson(200, null, $temp, 'ok');
        }
    }
}