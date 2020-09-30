<?php

namespace App\HttpController\Service\QianQi;

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
            'Z1'=>[0,5],
            'Z2'=>[5,10],
            'Z3'=>[10,20],
            'Z4'=>[20,30],
            'Z5'=>[30,40],
            'Z6'=>[40,50],
            'Z7'=>[50,100],
            'Z8'=>[100,200],
            'Z9'=>[200,300],
            'Z10'=>[300,400],
            'Z11'=>[400,500],
            'Z12'=>[500,600],
            'Z13'=>[600,800],
            'Z14'=>[800,1000],
            'Z15'=>[1000,2000],
            'Z16'=>[2000,3000],
            'Z17'=>[3000,4000],
            'Z18'=>[4000,5000],
            'Z19'=>[5000,7000],
            'Z20'=>[7000,10000],
            'Z21'=>[10000,12000],
            'Z22'=>[12000,14000],
            'Z23'=>[14000,16000],
            'Z24'=>[16000,18000],
            'Z25'=>[18000,20000],
            'Z26'=>[20000,25000],
            'Z27'=>[25000,30000],
            'Z28'=>[30000,50000],
            'Z29'=>[50000,70000],
            'Z30'=>[70000,100000],
            'Z31'=>[100000,150000],
            'Z32'=>[150000,200000],
            'Z33'=>[200000,300000],
            'Z34'=>[300000,500000],
            'Z35'=>[500000,1000000],
            'Z36'=>[1000000,1000000],
            'F1'=>[0,5],
            'F2'=>[5,10],
            'F3'=>[10,20],
            'F4'=>[20,30],
            'F5'=>[30,40],
            'F6'=>[40,50],
            'F7'=>[50,100],
            'F8'=>[100,200],
            'F9'=>[200,300],
            'F10'=>[300,400],
            'F11'=>[400,500],
            'F12'=>[500,600],
            'F13'=>[600,800],
            'F14'=>[800,1000],
            'F15'=>[1000,2000],
            'F16'=>[2000,3000],
            'F17'=>[3000,4000],
            'F18'=>[4000,5000],
            'F19'=>[5000,7000],
            'F20'=>[7000,10000],
            'F21'=>[10000,12000],
            'F22'=>[12000,14000],
            'F23'=>[14000,16000],
            'F24'=>[16000,18000],
            'F25'=>[18000,20000],
            'F26'=>[20000,25000],
            'F27'=>[25000,30000],
            'F28'=>[30000,50000],
            'F29'=>[50000,70000],
            'F30'=>[70000,100000],
            'F31'=>[100000,150000],
            'F32'=>[150000,200000],
            'F33'=>[200000,300000],
            'F34'=>[300000,500000],
            'F35'=>[500000,1000000],
            'F36'=>[1000000,1000000],
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
        $this->usercode=\Yaconf::get('qianqi.usercode');
        $this->userkey=\Yaconf::get('qianqi.userkey');
        $this->baseUrl=\Yaconf::get('qianqi.baseUrl');

        $this->sendHeaders=[
            'content-type'=>'application/x-www-form-urlencoded',
            'authorization'=>'',
        ];
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
    private function createToken($params,$str='')
    {
        ksort($params);

        foreach ($params as $k => $val)
        {
            $str.=$k.$val;
        }

        $res=hash_hmac('sha1',$str.$this->usercode,$this->userkey);

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
                'type'=>1,
                'usercode'=>$this->usercode
            ];

            $this->sendHeaders['authorization']=$this->createToken($arr);

            $res=(new CoHttpClient())->send($this->baseUrl.'xindong/search/',$arr,$this->sendHeaders);

            var_dump($res);

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

        return number_format(($now - $last) / abs($last) * 100,2);
    }




}
