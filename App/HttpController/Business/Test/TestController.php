<?php

namespace App\HttpController\Business\Test;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\BaiDu\BaiDuService;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\DaXiang\DaXiangService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\LongDun\LongDunService;
use App\HttpController\Service\MoveOut\MoveOutService;
use App\HttpController\Service\QianQi\QianQiService;
use App\HttpController\Service\TaoShu\TaoShuService;
use App\Process\Service\ProcessService;
use EasySwoole\Component\Di;
use EasySwoole\Pool\Manager;

class TestController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return true;
    }

    function test()
    {
        //ProcessService::getInstance()->sendToProcess('zhangjiang', 'go');

        //return $this->writeJson();

        $arr = [
            '91370611MA3W8B5D30',
            '91500112MA61ADXW0E',
            '91440605MA56030BXJ',
            '91440300MA5GH9CE9W',
            '91350602MA8RJK178P',
            '91220402MA17U69A4C',
            '91320706MA25CPN484',
            '91520400MAAKD1EX60',
            '91370681MA3W7KUT2M',
            '91120106MA077TAB46',
            '91340621MA2WPY148R',
            '91440101MA9W4TGJ0X',
            '93420625MA4D071U7X',
            '91110113MA01YUB138',
            '91421002MA49NKW143',
            '91370214MA3UJ89A4J',
            '91430681MA4T6RA517',
            '91340722MA2WP2CQ0D',
            '91140106MA0LH8P34W',
            '91430381MA4RY0JF8P',
            '91440981MA55XRMX4D',
            '91131023MA0FTQ1N4N',
            '91441322MA55TT5774',
            '93360983MA39T3UU3A',
            '91330201MA2J3NXD3C',
            '91450800MA5Q9HRP1M',
            '91530129MA6Q66GC0Y',
            '91610136MAB0QK5Y39',
            '91440101MA9W2H3A0B',
            '91610117MAB0T3RA7X',
            '91370811MA3WEJP68R',
            '91510106MAACF9EM1J',
            '91370703MA3ULDU279',
            '91320116MA25BBBH4Q',
            '91130582MA0G0T4W8X',
            '91460000MA5TRW7D86',
            '91220600MA84J0D362',
            '91510100MAACFD839W',
            '91330782MA2M2XME60',
            '91220203MA17XNQU9R',
            '91150824MA13TLHJ0Y',
            '91429004MA49N04Q6F',
            '91441900MA55UY883H',
            '93320723MA23PD2N64',
            '91140524MA0LHAL859',
            '91341602MA2WU62D99',
            '91330106MA2KCY6485',
            '91411726MA9G8T190M',
            '91440101MA9W3BH313',
            '91130503MA0FWT442B',
            '91610133MAB0NEWK81',
            '91460000MA5TU6GUX9',
            '91429004MA49N4QN2F',
            '91500110MA61BP7YXG',
            '91510114MA672Y147R',
            '91421126MA49P20UX7',
            '91411002MA9GAU6K6Q',
            '91440606MA5620NK2C',
            '91110105MA0216QKXW',
            '91440300MA5GHHUG15',
            '91150118MA13THQQ4R',
            '91220200MA17TUM645',
            '91310120MA1J06T232',
            '91510104MA6B1DRN93',
            '91370103MA3W8QPK9G',
            '91350981MA3562507N',
            '91540421MAB03L780N',
            '91440300MA5GKYPT9Y',
            '91430100MA4T2E6169',
            '91371500MA3UQ0K09P',
            '91370306MA3WAEUH4J',
            '91532625MA6Q0K233P',
            '91210411MA10WHBN8J',
            '91330212MA2J4X149Y',
            '91450324MA5QA3AE0M',
            '91450704MA5QAY613K',
            '91310112MA1GE2UE0J',
            '91230126MA1CENF983',
            '91653101MA792T155L',
            '91130802MA0G5L5J3Q',
            '91330624MA2JR4PF83',
            '91340881MA2WLTMK7T',
            '91330106MA2KCWDR5T',
            '91370613MA3UT6NW2E',
            '91370213MA3W84BX6H',
            '91440101MA9W3LHD0B',
            '91371082MA3UM5EK6H',
            '91611002MA70YCA958',
            '91440101MA9W5BE5XA',
            '91410181MA9GA1L173',
            '91110112MA01YC698Q',
            '91510823MA6623YP35',
            '91440300MA5GJG9F84',
            '91152502MA13TFLH3H',
            '91310116MA1JEJDW90',
            '91350681MA8RRYX73A',
            '91330211MA2J3L3L2X',
            '91320412MA24QG7JXC',
            '91370921MA3WCQAU73',
            '91230600MA1CENNK9C',
            '91370800MA3WB8LW95',
            '91360702MA39UGX16B',
            '91500107MA61B2GN4U',
            '91320594MA257A8W3D',
            '91520523MAAKADLE12',
            '91450922MA5Q6C5A95',
            '91440300MA5GLEGC3A',
            '91130132MA0G2FP103',
            '91370181MA3UL4GQXL',
            '91320281MA24XCC38M',
            '91310000MA1JE7EF3T',
            '91360622MA39T0XU4E',
            '91431124MA4T11084P',
            '91371312MA3WC0DP4M',
            '91310112MA1GE0464M',
            '91310120MA1JJ5NH9H',
            '91350503MA8RCR1B1C',
            '91320706MA25FD7A97',
            '91341222MA2WQB0YXL',
            '91350211MA35A9DJ4E',
            '91370112MA3UTF7J9D',
            '91430522MA4T0FJU8N',
            '91371302MA3URG000X',
            '91653101MA790MJ5XU',
            '91440101MA9W4HGR3D',
            '91370100MA3WHU5E2F',
            '91310116MA1JECM3X6',
            '91370724MA3ULFKU8L',
            '91440101MA9W32RA8C',
            '91653022MA791FXG50',
            '91460108MA5TWPPM4Q',
            '91350103MA8RR5DT72',
            '91410100MA9G7FXX7N',
            '91310109MA1G5XCJ2T',
            '91110112MA0209ND66',
            '91420800MA49P5BK6X',
            '91330402MA2JGJ7J7H',
            '91370103MA3WE19N63',
            '91430100MA4T6MTU3X',
            '91430405MA4T5RAL75',
            '91410502MA9GGR1G6N',
            '91500228MA619RU10H',
            '91532329MA6Q3G1H7Q',
            '91350104MA359R8Y9G',
            '91320100MA24QM0H5H',
            '91340104MA2WLPBA45',
            '91120116MA077KQL3Q',
            '91130283MA0G12WP0H',
            '91500108MA61Q7Q21J',
            '91320922MA256BKF8Q',
            '91610822MA70DQWQ86',
            '91440101MA9W4NMU2W',
            '91610921MAB2XEP656',
            '91310114MA1GXU3Q70',
            '91650104MA793L5E8G',
            '91511521MAACF8JL2U',
            '91513300MA661TYB4B',
            '91110108MA01YJQ86A',
            '91320281MA258T9W7G',
            '91433125MA4RX5EG2K',
            '91321182MA24T7DR53',
            '91310116MA1JEF3C05',
            '91350322MA8RMEUK9D',
            '91500112MA61CT7M8G',
            '91330104MA2KEP0A50',
            '91420112MA49NAL844',
            '91440300MA5GJUPG6X',
            '91511602MAACG7G55F',
            '91310120MA1J039E6H',
            '91210105MA10UMTR8T',
            '91440605MA55P1DL49',
            '91440300MA5GN7K88K',
            '91520102MAAK9WX85X',
            '91440101MA9W5PPH69',
            '91330782MA2M0D2M9H',
            '91320115MA24CPGMX4',
            '91510104MA6B1XCN8R',
            '91460000MA5TWHT835',
            '91370883MA3WGL1R38',
            '91440604MA55X1X54L',
            '91621102MA73WP873M',
            '91360102MA3ABK1B2P',
            '91430121MA4RXBKL83',
            '91450108MA5QACAD7U',
            '91340104MA2WRDHU6D',
            '91140109MA0LF0L60L',
            '91331081MA2K7Q2N9W',
            '91430302MA4T2QRL68',
            '91150207MA13TYXX2A',
            '91371302MA3UN5W61F',
            '91330726MA28DBKF9D',
            '91420200MA49NRHF29',
            '91310114MA1GXKHF9J',
            '91411524MA9GBC9D4E',
            '91430702MA4T2QHB0K',
            '91130581MA0G4MEH12',
            '91370282MA3UWDY307',
            '91320703MA250L9D6F',
            '91320411MA256PTW2A',
            '91510800MA6B4EDY90',
            '91460105MA5TU3R061',
            '91330110MA2KDA242U',
            '91441900MA5657TQ5G',
            '91430302MA4T3QC63E',
            '91370283MA3WA14JXU',
            '91420106MA49PN3N9A',
            '91500107MA61PUET33',
            '91370681MA3WCM1P8N',
            '91510113MA6B3Q0G6M',
            '91411081MA9GH1EF5N',
            '91340881MA2WGYN308',
            '91140303MA0LH8CN54',
            '91350603MA35BCJC9K',
            '91320282MA24QL0E8E',
            '91130100MA0G123U71',
            '91131124MA0G2XBTXH',
            '91610823MA70DTQA7T',
            '91411024MA9G9KXYX8',
            '91350103MA8RNXBT93',
            '91130925MA0G1A610U',
            '91420117MA49PLYK6N',
            '91530125MA6Q4PQD38',
            '91451226MA5QBU9D5P',
            '91230604MA1CDHM15M',
            '91513427MA6AYEJ010',
            '91430202MA4T2TUQ4T',
            '91410103MA9G5B9E7L',
            '91370613MA3UR0GL0T',
            '91410100MA9GC2WA4W',
            '91640100MA76LHUK02',
            '91522323MAAK9D4C1U',
            '91110106MA020LAG0C',
            '91310112MA1GE2TW1E',
            '91440300MA5GMC0117',
            '91370212MA3URWNT83',
            '91650106MA7910Q84N',
            '91350902MA8RT4DJ3Q',
            '91469006MA5TTTQR6C',
            '91530302MA6Q1MLM0T',
            '91540328MAB03F741H',
            '91130282MA0FXX6E3F',
            '91310230MA1HH5KK14',
            '91320115MA255DY63P',
            '91440300MA5GH2UU5G',
            '91360102MA39TM6N5Q',
            '91370310MA3URFA28G',
            '91420115MA49NJCG7D',
            '91210104MA10U5RAXL',
            '91411400MA9G7Y7540',
            '91370521MA3WAE1A2J',
            '91360829MA39T3G921',
            '91140321MA0LGA4J1Y',
            '91340100MA2WJBJ63M',
            '91310230MA1HH03L8U',
            '91321182MA25565A2M',
            '91430102MA4T7D0127',
            '91410883MA9GGWRN28',
            '91440300MA5GJU7B0B',
            '91440785MA55PL7P3H',
            '91130581MA0FUAKK2K',
            '91330822MA2DKGTN84',
            '91410183MA9G49R304',
            '91431103MA4T75M61H',
            '91450100MA5QAXRH56',
            '91410522MA9G4CKC83',
            '91460000MA5TXB1X9U',
            '91330206MA2J5E53X8',
            '91321003MA25K1LX0X',
            '91440101MA9W2N4W31',
            '91350623MA8RM4F53X',
            '91110115MA02135T1B',
            '91422825MA49Q7TE2P',
            '91430902MA4T32W65F',
            '91420106MA49P8DH23',
            '91360502MA39TYWPXN',
            '91410621MA9GAHA45P',
            '91540200MAB03Q8X5N',
            '91330212MA2J4QDP8F',
            '91441900MA561X6N3N',
            '91361127MA39T40Y81',
            '91510105MA6A57WQXK',
            '91320581MA25ED2R00',
            '91450108MA5QB95Y4R',
            '91130605MA0G45FY0H',
            '91610113MAB0PL617J',
            '91530921MA6Q2ME79D',
            '91370600MA3W7N547W',
            '91370402MA3W0F5Y22',
            '91510107MA68DUBN60',
            '91350302MA356MA386',
            '91620503MA72CX6L7P',
            '91370321MA3WDP6766',
            '91440101MA9W56PK9R',
            '91610102MAB0RTJD0A',
            '91411500MA9G4L2347',
            '91370322MA3WEM0LXG',
            '91330100MA2KDQJ36P',
            '91310113MA1GPQP50M',
            '91460000MA5TUE4L11',
            '91310120MA1J0AAX7C',
            '91320311MA255Y005Q',
            '91150105MA13TLL86K',
            '91340221MA2WT5GB3L',
            '91371422MA3WBY258N',
            '91110117MA01YDYD1Y',
            '91310120MA1J0DUW48',
            '91341500MA2WGEA5XY',
            '91140781MA0LEPLR5D',
            '91361003MA39RWT18E',
            '91131025MA0FWCCM4R',
            '91410105MA9G3LFR8K',
            '91370104MA3UK84B6G',
            '91442000MA55RDWX7Q',
            '91340802MA2WK7470R',
            '91120106MA078WWM89',
            '91620982MA72CXP81B',
            '91460000MA5TTHEW8M',
            '91440605MA55N7BK90',
            '91370831MA3WF3RA5A',
            '91410702MA9G4NGM4J',
            '91110108MA01YH4K96',
            '91130821MA0G69Y80U',
            '91130638MA0G1XN979',
            '91350583MA8REACD2Y',
            '91530627MA6Q6PGX4L',
            '91130403MA0FTH6P87',
            '91330521MA2D5JCC68',
            '91320724MA259BM98P',
            '91621027MA74H439X2',
            '91340121MA2WT6BW65',
            '91310115MA1K4NA64C',
            '91371521MA3WEPMA83',
            '91433101MA4T0W4C13',
            '93371526MA3UHRJD3T',
            '91310116MA7AD1D56W',
            '93230781MA1CDPPD45',
            '91450103MA5QBPUC7A',
            '91460000MA5TU94X6M',
            '91520622MAAK5GM20X',
            '91321023MA254G7J00',
            '91220104MA17U9R56B',
            '91321111MA251UP01C',
            '91350211MA8REHR517',
            '91330900MA2DMYFD77',
            '91370523MA3WDMUH24',
            '91440101MA9W4MG497',
            '91441900MA56329X02',
            '91360125MA39RRFE1N',
            '91440101MA9W25TB1B',
            '91510802MA68FP2M8G',
            '91430103MA4T6T7072',
            '91441900MA565TUM9Q',
            '91140109MA0LGLPF2D',
            '91340102MA2WRMER7A',
            '91330881MA2DK9FD88',
            '91320214MA2458UC2R',
            '91500233MA61DANK77',
            '91510100MA6B273U28',
            '91440904MA563TXX74',
            '91410103MA9G824P7P',
            '91511802MAACGW5377',
            '91330201MA2J4DU65N',
            '91360481MA39THT34K',
            '91320592MA259LRX3R',
            '91120113MA077W4E99',
            '91340802MA2WREL843',
            '91441283MA55T5L49T',
            '91340181MA2WLMBB48',
            '91140109MA0LDBNN1K',
            '91130430MA0G5B7Y16',
            '91230108MA1CFX6W16',
            '91330106MA2KC79003',
            '91421000MA49N3MN4G',
            '91310110MA1G99QP7F',
            '91130225MA0G2LJF3K',
            '91410104MA9GB7UH7X',
            '91230206MA1CDAMAXY',
            '91360731MA3ABA9064',
            '91370602MA3WCMBM7W',
            '91440300MA5GNXTT8W',
            '91510703MA6B3CJ758',
            '91360106MA39RUHQ07',
            '91513400MA68H2AFX4',
            '91230224MA1CEHHK4G',
            '91420115MA49PCQD3E',
            '91130101MA0FUD4R66',
            '91370112MA3UYMG072',
            '91330782MA2K1M6291',
            '91440101MA9W60YD8L',
            '91611102MAB2KEBKXY',
            '91530103MA6Q6N3P0E',
            '91321283MA25EYPDX9',
            '91370784MA3UYD3E4U',
            '91370211MA3WHP7E1X',
            '91341103MA2WRNHE2J',
            '91140100MA0LGLQB69',
            '91450881MA5QA17R23',
            '91110116MA020JTA7P',
            '91460000MA5TWTRC6X',
            '91411724MA9G4DQ27W',
            '91370800MA3W8C1Q7L',
            '91420116MA49PC6F2M',
            '91360805MA39TWJ827',
            '91430400MA4T3QYP91',
            '91230102MA1CGTC457',
            '91310230MA1HHECQ4U',
            '91370281MA3W9XA989',
            '91350322MA35CF3Y0K',
            '91510107MA6A6NDW7F',
            '91510104MA69J8C75P',
            '91621123MA74DN250W',
            '91410300MA9GC1LU57',
            '91350583MA358W7W49',
            '91210242MA10RBT35T',
            '91411621MA9G621C0E',
            '91440101MA9W2HXH92',
            '91410102MA9G5HNLXM',
            '91370700MA3UNQPT2F',
            '91220211MA17X29M8D',
            '91440604MA55MDJJ26',
            '91130435MA0G4LUL35',
            '91350582MA8RCQC179',
            '91431127MA4T3119XN',
            '91440101MA9W4F1CXW',
            '91360121MA39U9FA64',
            '91441502MA56563P9A',
            '91149900MA0LFQ2Q4H',
            '91340824MA2WPQHW7L',
            '91371325MA3W7R230Y',
            '91410185MA9GF0K66Q',
            '91440300MA5GLDEH9F',
            '91150404MA0QWLRY2T',
            '91371302MA3URW4A1Q',
            '91331082MA2K9YTH4R',
            '91370883MA3WFBBM34',
            '91320113MA250QC10Y',
            '91210112MA10RHM61W',
            '91310230MA1HHLH61P',
            '91440300MA5GNXPJ0T',
            '91441802MA55YKYP17',
            '91370105MA3UMGPQ5E',
            '91440200MA565TRN8T',
            '91310115MA1K4P023K',
            '91511402MA68DHHL2Y',
            '91370281MA3UGP865R',
            '91370304MA3UR725XP',
            '91411000MA9GDG5C2A',
            '91440101MA9W58FC57',
            '91330283MA2J4J3T89',
            '91440300MA5GJ2TP9T',
            '91350181MA8RH75D6K',
            '91420112MA49NXR18Y',
            '91451223MA5Q6UU56N',
            '91330106MA2KDJE00N',
            '91371702MA3WGWB735',
            '91340604MA2WJ7205H',
            '91310120MA1JJ02Q5N',
            '91520222MAAK661U4D',
            '91522634MAAKCUKX82',
            '91420103MA49MMEE65',
            '91620822MA71QGUR8K',
            '91340121MA2WUY4NXK',
            '91120112MA079C3N2K',
            '91410503MA9GCD3G94',
            '91361002MA39T8P42N',
            '92330105MA2KDXC81L',
            '91370220MA3URF5BXQ',
            '91110115MA01YE5X32',
            '91330782MA2M2P78X6',
            '91530402MA6Q2H1Q8T',
            '91371522MA3WEPDA0B',
            '91440101MA9XMAAN2Q',
            '91420625MA49PE6486',
            '91310113MA7AD1YA0L',
            '91150203MA13UFLH92',
            '91411625MA9G4EUK2G',
            '91320982MA24JUMH94',
            '91310120MA1JJ4BU6W',
            '91610102MAB0Q8Q81G',
            '91210106MA10R8PX7U',
            '91532625MA6Q04C5XN',
            '91371325MA3UXC5UXC',
            '91440300MA5GN63X1H',
            '91230800MA1CET1H0E',
            '91610131MAB0PQQA1E',
            '91430224MA4T3Q9W77',
            '91370103MA3UJKC12G',
            '91370203MA3URT7513',
            '91440101MA9W3MWC00',
            '91440101MA9W370LXX',
            '91510106MA65UT953Q',
            '91320581MA25FYYP0F',
            '91130925MA0G2N1X1X',
            '91350921MA35AAM5XN',
            '91460000MA5TRU829C',
            '91410302MA9GC2NR6P',
            '91330782MA2M33WD5F',
            '91130982MA0G040A4X',
            '91610526MA6YBN8610',
            '91231226MA1CD0JNXD',
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
        //地图
        try {
            $mysqlObj = Manager::getInstance()
                ->get(CreateConf::getInstance()->getConf('env.mysqlDatabaseMZJD'))
                ->getObj();
            $sql = 'SELECT * FROM qyxx_copy1';
            $list = $mysqlObj->rawQuery($sql);
            $list = obj2Arr($list);
            foreach ($list as $val) {
                $map[$val['XZQH_NAME']] = $val['num'];
            }
        } catch (\Throwable $e) {
            $this->writeErr($e, __FUNCTION__);
        } finally {
            Manager::getInstance()
                ->get(CreateConf::getInstance()->getConf('env.mysqlDatabaseMZJD'))
                ->recycleObj($mysqlObj);
        }

        //标准一级分类和个数
        try {
            $mysqlObj = Manager::getInstance()
                ->get(CreateConf::getInstance()->getConf('env.mysqlDatabaseMZJD'))
                ->getObj();
            $sql = 'select JJHYDM,count(1) as num from cpxx where JJHYDM is not null group by JJHYDM';
            $list = $mysqlObj->rawQuery($sql);
            $list = obj2Arr($list);
        } catch (\Throwable $e) {
            $this->writeErr($e, __FUNCTION__);
            $list = [];
        } finally {
            Manager::getInstance()
                ->get(CreateConf::getInstance()->getConf('env.mysqlDatabaseMZJD'))
                ->recycleObj($mysqlObj);
        }

        $JJHYDM = $tmp = [];

        foreach ($list as $index => $val) {
            if (preg_match('/[\xe0-\xef][\x80-\xbf]/', $val['JJHYDM'])) {
                if (isset($JJHYDM[trim($val['JJHYDM'])])) {
                    $JJHYDM[trim($val['JJHYDM'])] += $val['num'];
                } else {
                    $JJHYDM[trim($val['JJHYDM'])] = $val['num'];
                }
            }
        }

        arsort($JJHYDM);

        foreach ($JJHYDM as $key => $val) {
            $tmp[] = ['name' => $key, 'num' => $val];
        }

        return $this->writeJson(200, null, [
            'k' => array_keys($map), 'v' => array_values($map), 'JJHYDM' => $tmp
        ]);
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