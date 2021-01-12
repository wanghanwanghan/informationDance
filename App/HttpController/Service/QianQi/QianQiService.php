<?php

namespace App\HttpController\Service\QianQi;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;

class QianQiService extends ServiceBase
{
    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    private $usercode;
    private $userkey;
    private $baseUrl;
    private $sendHeaders;

    //范围
    private $range=[
        0=>[
            'Z'=>[0,0],
            'Z1'=>[0.0001,10],
            'Z2'=>[10,20],
            'Z3'=>[20,40],
            'Z4'=>[40,60],
            'Z5'=>[60,80],
            'Z6'=>[80,100],
            'Z7'=>[100,120],
            'Z8'=>[120,140],
            'Z9'=>[140,160],
            'Z10'=>[160,180],
            'Z11'=>[180,200],
            'Z12'=>[200,220],
            'Z13'=>[220,240],
            'Z14'=>[240,270],
            'Z15'=>[270,320],
            'Z16'=>[320,380],
            'Z17'=>[380,460],
            'Z18'=>[460,550],
            'Z19'=>[550,660],
            'Z20'=>[660,790],
            'Z21'=>[790,950],
            'Z22'=>[950,1100],
            'Z23'=>[1100,1400],
            'Z24'=>[1400,1600],
            'Z25'=>[1600,2000],
            'Z26'=>[2000,2400],
            'Z27'=>[2400,2800],
            'Z28'=>[2800,3400],
            'Z29'=>[3400,4100],
            'Z30'=>[4100,4900],
            'Z31'=>[4900,5900],
            'Z32'=>[5900,7100],
            'Z33'=>[7100,8500],
            'Z34'=>[8500,10000],
            'Z35'=>[10000,12000],
            'Z36'=>[12000,15000],
            'Z37'=>[15000,18000],
            'Z38'=>[18000,21000],
            'Z39'=>[21000,25000],
            'Z40'=>[25000,30000],
            'Z41'=>[30000,37000],
            'Z42'=>[37000,44000],
            'Z43'=>[44000,53000],
            'Z44'=>[53000,63000],
            'Z45'=>[63000,76000],
            'Z46'=>[76000,91000],
            'Z47'=>[91000,110000],
            'Z48'=>[110000,130000],
            'Z49'=>[130000,160000],
            'Z50'=>[160000,190000],
            'Z51'=>[190000,230000],
            'Z52'=>[230000,270000],
            'Z53'=>[270000,330000],
            'Z54'=>[330000,390000],
            'Z55'=>[390000,470000],
            'Z56'=>[470000,560000],
            'Z57'=>[560000,680000],
            'Z58'=>[680000,810000],
            'Z59'=>[810000,970000],
            'Z60'=>[970000,1200000],
            'Z61'=>[1200000,1400000],
            'Z62'=>[1400000,1700000],
            'Z63'=>[1700000,2000000],
            'Z64'=>[2000000,2400000],
            'Z65'=>[2400000,2900000],
            'Z66'=>[2900000,3500000],
            'Z67'=>[3500000,4200000],
            'Z68'=>[4200000,5000000],
            'Z69'=>[5000000,6000000],
            'Z70'=>[6000000,7200000],
            'Z71'=>[7200000,8700000],
            'Z72'=>[8700000,10000000],
            'Z73'=>[10000000,13000000],
            'Z74'=>[13000000,15000000],
            'Z75'=>[15000000,18000000],
            'Z76'=>[18000000,22000000],
            'Z77'=>[22000000,26000000],
            'Z78'=>[26000000,31000000],
            'Z79'=>[31000000,37000000],
            'Z80'=>[37000000,45000000],
            'Z81'=>[45000000,54000000],
            'Z82'=>[54000000,65000000],
            'Z83'=>[65000000,78000000],
            'Z84'=>[78000000,93000000],
            'Z85'=>[93000000,93000000],
            'F64'=>[2000000,2000000],
            'F63'=>[2000000,1700000],
            'F62'=>[1700000,1400000],
            'F61'=>[1400000,1200000],
            'F60'=>[1200000,970000],
            'F59'=>[970000,810000],
            'F58'=>[810000,680000],
            'F57'=>[680000,560000],
            'F56'=>[560000,470000],
            'F55'=>[470000,390000],
            'F54'=>[390000,330000],
            'F53'=>[330000,270000],
            'F52'=>[270000,230000],
            'F51'=>[230000,190000],
            'F50'=>[190000,160000],
            'F49'=>[160000,130000],
            'F48'=>[130000,110000],
            'F47'=>[110000,91000],
            'F46'=>[91000,76000],
            'F45'=>[76000,63000],
            'F44'=>[63000,53000],
            'F43'=>[53000,44000],
            'F42'=>[44000,37000],
            'F41'=>[37000,30000],
            'F40'=>[30000,25000],
            'F39'=>[25000,21000],
            'F38'=>[21000,18000],
            'F37'=>[18000,15000],
            'F36'=>[15000,12000],
            'F35'=>[12000,10000],
            'F34'=>[10000,8500],
            'F33'=>[8500,7100],
            'F32'=>[7100,5900],
            'F31'=>[5900,4900],
            'F30'=>[4900,4100],
            'F29'=>[4100,3400],
            'F28'=>[3400,2800],
            'F27'=>[2800,2400],
            'F26'=>[2400,2000],
            'F25'=>[2000,1600],
            'F24'=>[1600,1400],
            'F23'=>[1400,1100],
            'F22'=>[1100,950],
            'F21'=>[950,790],
            'F20'=>[790,660],
            'F19'=>[660,550],
            'F18'=>[550,460],
            'F17'=>[460,380],
            'F16'=>[380,320],
            'F15'=>[320,270],
            'F14'=>[270,240],
            'F13'=>[240,220],
            'F12'=>[220,200],
            'F11'=>[200,180],
            'F10'=>[180,160],
            'F9'=>[160,140],
            'F8'=>[140,120],
            'F7'=>[120,100],
            'F6'=>[100,80],
            'F5'=>[80,60],
            'F4'=>[60,40],
            'F3'=>[40,20],
            'F2'=>[20,10],
            'F1'=>[10,0.0001],
        ],
        1=>[
            'Z'=>[0,0],
            'Z1'=>[0,20],
            'Z2'=>[20,40],
            'Z3'=>[40,60],
            'Z4'=>[60,80],
            'Z5'=>[80,100],
            'Z6'=>[100,120],
            'Z7'=>[120,140],
            'Z8'=>[140,160],
            'Z9'=>[160,180],
            'Z10'=>[180,200],
            'Z11'=>[200,220],
            'Z12'=>[220,240],
            'Z13'=>[240,260],
            'Z14'=>[260,280],
            'Z15'=>[280,300],
            'Z16'=>[300,320],
            'Z17'=>[320,340],
            'Z18'=>[340,360],
            'Z19'=>[360,380],
            'Z20'=>[380,400],
            'Z21'=>[400,500],
            'Z22'=>[500,600],
            'Z23'=>[600,700],
            'Z24'=>[700,800],
            'Z25'=>[800,900],
            'Z26'=>[900,1000],
            'Z27'=>[1000,2000],
            'Z28'=>[2000,3000],
            'Z29'=>[3000,4000],
            'Z30'=>[4000,5000],
            'Z31'=>[5000,5000],
            'F1'=>[0,20],
            'F2'=>[20,40],
            'F3'=>[40,60],
            'F4'=>[60,80],
            'F5'=>[80,100],
            'F6'=>[100,120],
            'F7'=>[120,140],
            'F8'=>[140,160],
            'F9'=>[160,180],
            'F10'=>[180,200],
            'F11'=>[200,220],
            'F12'=>[220,240],
            'F13'=>[240,260],
            'F14'=>[260,280],
            'F15'=>[280,300],
            'F16'=>[300,320],
            'F17'=>[320,340],
            'F18'=>[340,360],
            'F19'=>[360,380],
            'F20'=>[380,400],
            'F21'=>[400,500],
            'F22'=>[500,600],
            'F23'=>[600,700],
            'F24'=>[700,800],
            'F25'=>[800,900],
            'F26'=>[900,1000],
            'F27'=>[1000,2000],
            'F28'=>[2000,3000],
            'F29'=>[3000,4000],
            'F30'=>[4000,5000],
            'F31'=>[5000,5000],
        ],
        2=>[
            'R0'=>[-30,-30],
            'R1'=>[-30,-20],
            'R2'=>[-20,-15],
            'R3'=>[-15,-10],
            'R4'=>[-10,-8],
            'R5'=>[-8,-6.5],
            'R6'=>[-6.5,-5],
            'R7'=>[-5,-4.5],
            'R8'=>[-4.5,-4],
            'R9'=>[-4,-3.5],
            'R10'=>[-3.5,-3],
            'R11'=>[-3,-2.75],
            'R12'=>[-2.75,-2.5],
            'R13'=>[-2.5,-2.25],
            'R14'=>[-2.25,-2],
            'R15'=>[-2,-1.8],
            'R16'=>[-1.8,-1.6],
            'R17'=>[-1.6,-1.4],
            'R18'=>[-1.4,-1.3],
            'R19'=>[-1.3,-1.2],
            'R20'=>[-1.2,-1.1],
            'R21'=>[-1.1,-1],
            'R22'=>[-1,-0.9],
            'R23'=>[-0.9,-0.85],
            'R24'=>[-0.85,-0.8],
            'R25'=>[-0.8,-0.75],
            'R26'=>[-0.75,-0.7],
            'R27'=>[-0.7,-0.65],
            'R28'=>[-0.65,-0.6],
            'R29'=>[-0.6,-0.55],
            'R30'=>[-0.55,-0.5],
            'R31'=>[-0.5,-0.45],
            'R32'=>[-0.45,-0.4],
            'R33'=>[-0.4,-0.35],
            'R34'=>[-0.35,-0.3],
            'R35'=>[-0.3,-0.25],
            'R36'=>[-0.25,-0.2],
            'R37'=>[-0.2,-0.15],
            'R38'=>[-0.15,-0.1],
            'R39'=>[-0.1,-0.05],
            'R40'=>[-0.05,0],
            'R41'=>[0,0],
            'R42'=>[0,0.05],
            'R43'=>[0.05,0.1],
            'R44'=>[0.1,0.15],
            'R45'=>[0.15,0.2],
            'R46'=>[0.2,0.25],
            'R47'=>[0.25,0.3],
            'R48'=>[0.3,0.35],
            'R49'=>[0.35,0.4],
            'R50'=>[0.4,0.45],
            'R51'=>[0.45,0.5],
            'R52'=>[0.5,0.55],
            'R53'=>[0.55,0.6],
            'R54'=>[0.6,0.65],
            'R55'=>[0.65,0.7],
            'R56'=>[0.7,0.75],
            'R57'=>[0.75,0.8],
            'R58'=>[0.8,0.85],
            'R59'=>[0.85,0.9],
            'R60'=>[0.9,1],
            'R61'=>[1,1.1],
            'R62'=>[1.1,1.2],
            'R63'=>[1.2,1.3],
            'R64'=>[1.3,1.4],
            'R65'=>[1.4,1.6],
            'R66'=>[1.6,1.8],
            'R67'=>[1.8,2],
            'R68'=>[2,2.25],
            'R69'=>[2.25,2.5],
            'R70'=>[2.5,2.75],
            'R71'=>[2.75,3],
            'R72'=>[3,3.5],
            'R73'=>[3.5,4],
            'R74'=>[4,4.5],
            'R75'=>[4.5,5],
            'R76'=>[5,6.5],
            'R77'=>[6.5,8],
            'R78'=>[8,10],
            'R79'=>[10,15],
            'R80'=>[15,20],
            'R81'=>[20,30],
            'R82'=>[30,30],
        ],
    ];

    //字段名 => 范围数组的下标
    private $word=[
        'ASSGRO_REL'=>[0],//资产总额
        'ASSGRO_REL_yoy'=>[2],//资产总额同比
        'LIAGRO_REL'=>[0],//负债总额
        'LIAGRO_REL_yoy'=>[2],//负债总额同比
        'VENDINC_REL'=>[0],//营业总收入
        'VENDINC_REL_yoy'=>[2],//营业总收入同比
        'MAIBUSINC_REL'=>[0],//主营业务收入
        'MAIBUSINC_REL_yoy'=>[2],//主营业务收入
        'PROGRO_REL'=>[0],//利润总额
        'PROGRO_REL_yoy'=>[2],//利润总额
        'NETINC_REL'=>[0],//净利润
        'NETINC_REL_yoy'=>[2],//净利润
        'RATGRO_REL'=>[0],//纳税总额
        'RATGRO_REL_yoy'=>[2],//纳税总额
        'TOTEQU_REL'=>[0],//所有者权益合计
        'TOTEQU_REL_yoy'=>[2],//所有者权益合计
        'SOCNUM'=>[],//社保人数
        'C_ASSGROL'=>[0],//净资产
        'C_ASSGROL_yoy'=>[2],//净资产
        'A_ASSGROL'=>[0],//平均资产总额
        'A_ASSGROL_yoy'=>[2],//平均资产总额
        'CA_ASSGROL'=>[0],//平均净资产
        'CA_ASSGROL_yoy'=>[2],//平均净资产
        'C_INTRATESL'=>[2],//净利率
        'ASSGRO_C_INTRATESL'=>[2],//总资产净利率
        'A_VENDINCL'=>[1],//企业人均产值
        'A_VENDINCL_yoy'=>[2],//企业人均产值
        'A_PROGROL'=>[1],//企业人均盈利
        'A_PROGROL_yoy'=>[2],//企业人均盈利
        'ROAL'=>[2],//总资产回报率 ROA
        'ROE_AL'=>[2],//净资产回报率 ROE (A)
        'ROE_BL'=>[2],//净资产回报率 ROE (B)
        'DEBTL'=>[2],//资产负债率
        'EQUITYL'=>[2],//权益乘数
        'ATOL'=>[2],//资产周转率
        'MAIBUSINC_RATIOL'=>[2],//主营业务比率
    ];

    function __construct()
    {
        $this->usercode=CreateConf::getInstance()->getConf('qianqi.usercode');
        $this->userkey=CreateConf::getInstance()->getConf('qianqi.userkey');
        $this->baseUrl=CreateConf::getInstance()->getConf('qianqi.baseUrl');

        $this->sendHeaders=[
            'content-type'=>'application/x-www-form-urlencoded',
            'authorization'=>'',
        ];

        return parent::__construct();
    }

    //公司名称换取entid
    private function getEntid($entName): ?string
    {
        $ctype=preg_match('/\d{5}/',$entName) ? 1 : 3;

        $arr=[
            'key'=>$entName,
            'ctype'=>$ctype,
            'usercode'=>$this->usercode
        ];

        $this->sendHeaders['authorization']=$this->createToken($arr);

        $res=(new CoHttpClient())->send($this->baseUrl.'getentid/',$arr,$this->sendHeaders);

        (!empty($res) && !empty($res['data'])) ? $entid=$res['data'] : $entid=null;

        return $entid;
    }

    //创建请求token
    private function createToken($params, $str = '', $userKey = '')
    {
        ksort($params);

        foreach ($params as $k => $val) {
            $str .= $k . $val;
        }

        empty($userKey) ?
            $res = hash_hmac('sha1', $str . $this->usercode, $this->userkey) :
            $res = hash_hmac('sha1', $str.'j7uSz7ipmJ', 'EBjGihfGnxF');
        
        return $res;
    }

    //整理数据，把字母转换成数字
    private function wordToNum($data)
    {
        if (empty($data)) return [];

        $tmp=[];

        foreach ($data as $key => $val)
        {
            $key=trim($key);
            $val=trim($val);

            if (empty($val) || $val=='-')
            {
                $tmp[$key]=null;
                continue;
            }

            if ($key=='SOCNUM')
            {
                $tmp[$key]=(int)$val;
                continue;
            }

            if (!array_key_exists($key,$this->word))
            {
                $tmp[$key]=$val;
                continue;
            }

            $index=reset($this->word[$key]);

            $num=$this->range[$index][$val];

            $src=sprintf('%.2f',(reset($num) + end($num)) / 2);

            substr($val,0,1) == 'F' ? $change = -1 : $change = 1;

            $tmp[$key]=$src * $change;
        }

        return $tmp;
    }

    private function checkResp($res)
    {
        $res['Paging']=null;

        if (isset($res['coHttpErr'])) return $this->createReturn(500,$res['Paging'],[],'co请求错误');

        $res['Result']=$res['data'];
        $res['Message']=$res['msg'];

        return $this->createReturn((int)$res['code'],$res['Paging'],$res['Result'],$res['Message']);
    }

    //天眼查取数据测试
    function getDataTest()
    {
        $this->sendHeaders['authorization']=$this->createToken([
            'usercode' => 'j7uSz7ipmJ'
        ],'','test');

        $data = [
            'usercode' => 'j7uSz7ipmJ'
        ];

        CommonService::getInstance()->log4PHP($this->sendHeaders['authorization']);

        $url = 'http://39.106.95.155/data/daily_ent_mrxd?_t='.time();

        return (new CoHttpClient())->send($url,$data,$this->sendHeaders);
    }

    //近三年的财务数据
    function getThreeYearsData($postData)
    {
        $entId=$this->getEntid($postData['entName']);

        if (empty($entId))
        {
            return ['code'=>102,'msg'=>'entId是空','data'=>[]];
        }

        (int)date('m') >= 9 ? $yearStart=date('Y') - 1 : $yearStart=date('Y') - 2;

        for ($i=3;$i--;)
        {
            $arr=[
                'entid'=>$entId,
                'year'=>$yearStart - $i,
                'type'=>2,
                'usercode'=>$this->usercode
            ];

            $this->sendHeaders['authorization']=$this->createToken($arr);

            $res=(new CoHttpClient())->send($this->baseUrl.'xindong/search/',$arr,$this->sendHeaders);

            isset($res['data']) ? $return[$yearStart - $i]=$this->wordToNum($res['data']) : $return[$yearStart - $i]=[];
        }

        krsort($return);

        return $this->checkRespFlag ?
            $this->checkResp(['code'=>200,'msg'=>'查询成功','data'=>$return]) : ['code'=>200,'msg'=>'查询成功','data'=>$return];
    }

    //对外的最近三年财务数据
    function getThreeYears($postData)
    {
        $entId=$this->getEntid($postData['entName']);

        if (empty($entId))
        {
            return ['code'=>102,'msg'=>'entId是空','data'=>[]];
        }

        (int)date('m') >= 9 ? $yearStart=date('Y') - 1 : $yearStart=date('Y') - 2;

        for ($i=3;$i--;)
        {
            $arr=[
                'entid'=>$entId,
                'year'=>$yearStart - $i,
                'type'=>2,
                'usercode'=>$this->usercode
            ];

            $this->sendHeaders['authorization']=$this->createToken($arr);

            $res=(new CoHttpClient())->send($this->baseUrl.'xindong/search/',$arr,$this->sendHeaders);

            isset($res['data']) ? $return[$yearStart - $i]=$this->wordToNum($res['data']) : $return[$yearStart - $i]=[];
        }

        krsort($return);

        return $this->checkRespFlag ?
            $this->checkResp(['code'=>200,'msg'=>'查询成功','data'=>$return]) : ['code'=>200,'msg'=>'查询成功','data'=>$return];
    }

    //近三年的财务数据不给原值的，转化成同比
    function toPercent($data): array
    {
        $tmp=[];

        //计算哪些字段的同比
        $target=[
            'ASSGRO_REL',//资产总额
            'LIAGRO_REL',//负债总额
            'VENDINC_REL',//营业总收入
            'MAIBUSINC_REL',//主营业务收入
            'PROGRO_REL',//利润总额
            'NETINC_REL',//净利润
            'RATGRO_REL',//纳税总额
            'TOTEQU_REL',//所有者权益合计
            'SOCNUM',//社保人数
        ];

        $yearArr=array_keys($data);
        krsort($yearArr);

        foreach ($yearArr as $year)
        {
            foreach ($target as $field)
            {
                //今年和去年都有这个字段
                if (isset($data[$year][$field]) && isset($data[$year-1][$field]))
                {
                    $tmp[$year][$field]=$this->expr($data[$year][$field],$data[$year-1][$field]);
                }else
                {
                    $tmp[$year][$field]=null;
                }
            }
        }

        krsort($tmp);
        array_pop($tmp);

        return $tmp;
    }

    //计算 (a - b) / abs(b) * 0.01
    private function expr($now,$last)
    {
        if ($now===null || $last===null) return null;

        //0不能是除数
        if ($last===0) return null;

        return (int)sprintf('%.2f',($now - $last) / abs($last) * 100);
    }



}
