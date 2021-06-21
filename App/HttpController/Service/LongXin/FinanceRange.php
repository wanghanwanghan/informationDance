<?php

namespace App\HttpController\Service\LongXin;

use EasySwoole\Component\Singleton;

class FinanceRange
{
    use Singleton;

    public $range = [
        [
            'ASSGRO',//0资产总额
            'LIAGRO',//1负债总额
            'VENDINC',//2营业总收入
            'MAIBUSINC',//3主营业务收入
            'PROGRO',//4利润总额
            'NETINC',//5净利润
            'RATGRO',//6纳税总额
            'TOTEQU',//7所有者权益
            'C_ASSGROL',//9净资产
            'A_ASSGROL',//10平均资产总额
            'CA_ASSGRO',//11平均净资产
            'A_VENDINCL',//15企业人均产值
            'A_PROGROL',//16企业人均盈利
        ],
        [
            ['name' => 'F36', 'range' => [1000000, 2000000]],
            ['name' => 'F35', 'range' => [500000, 1000000]],
            ['name' => 'F34', 'range' => [300000, 500000]],
            ['name' => 'F33', 'range' => [200000, 300000]],
            ['name' => 'F32', 'range' => [150000, 200000]],
            ['name' => 'F31', 'range' => [100000, 150000]],
            ['name' => 'F30', 'range' => [70000, 100000]],
            ['name' => 'F29', 'range' => [50000, 70000]],
            ['name' => 'F28', 'range' => [30000, 50000]],
            ['name' => 'F27', 'range' => [25000, 30000]],
            ['name' => 'F26', 'range' => [20000, 25000]],
            ['name' => 'F25', 'range' => [18000, 20000]],
            ['name' => 'F24', 'range' => [16000, 18000]],
            ['name' => 'F23', 'range' => [14000, 16000]],
            ['name' => 'F22', 'range' => [12000, 14000]],
            ['name' => 'F21', 'range' => [10000, 12000]],
            ['name' => 'F20', 'range' => [7000, 10000]],
            ['name' => 'F19', 'range' => [5000, 7000]],
            ['name' => 'F18', 'range' => [4000, 5000]],
            ['name' => 'F17', 'range' => [3000, 4000]],
            ['name' => 'F16', 'range' => [2000, 3000]],
            ['name' => 'F15', 'range' => [1000, 2000]],
            ['name' => 'F14', 'range' => [800, 1000]],
            ['name' => 'F13', 'range' => [600, 800]],
            ['name' => 'F12', 'range' => [500, 600]],
            ['name' => 'F11', 'range' => [400, 500]],
            ['name' => 'F10', 'range' => [300, 400]],
            ['name' => 'F09', 'range' => [200, 300]],
            ['name' => 'F08', 'range' => [100, 200]],
            ['name' => 'F07', 'range' => [50, 100]],
            ['name' => 'F06', 'range' => [40, 50]],
            ['name' => 'F05', 'range' => [30, 40]],
            ['name' => 'F04', 'range' => [20, 30]],
            ['name' => 'F03', 'range' => [10, 20]],
            ['name' => 'F02', 'range' => [5, 10]],
            ['name' => 'F01', 'range' => [0, 5]],
            ['name' => 'Z00', 'range' => [0, 0]],
            ['name' => 'Z01', 'range' => [1, 5]],
            ['name' => 'Z02', 'range' => [5, 10]],
            ['name' => 'Z03', 'range' => [10, 20]],
            ['name' => 'Z04', 'range' => [20, 30]],
            ['name' => 'Z05', 'range' => [30, 40]],
            ['name' => 'Z06', 'range' => [40, 50]],
            ['name' => 'Z07', 'range' => [50, 100]],
            ['name' => 'Z08', 'range' => [100, 200]],
            ['name' => 'Z09', 'range' => [200, 300]],
            ['name' => 'Z10', 'range' => [300, 400]],
            ['name' => 'Z11', 'range' => [400, 500]],
            ['name' => 'Z12', 'range' => [500, 600]],
            ['name' => 'Z13', 'range' => [600, 800]],
            ['name' => 'Z14', 'range' => [800, 1000]],
            ['name' => 'Z15', 'range' => [1000, 2000]],
            ['name' => 'Z16', 'range' => [2000, 3000]],
            ['name' => 'Z17', 'range' => [3000, 4000]],
            ['name' => 'Z18', 'range' => [4000, 5000]],
            ['name' => 'Z19', 'range' => [5000, 7000]],
            ['name' => 'Z20', 'range' => [7000, 10000]],
            ['name' => 'Z21', 'range' => [10000, 12000]],
            ['name' => 'Z22', 'range' => [12000, 14000]],
            ['name' => 'Z23', 'range' => [14000, 16000]],
            ['name' => 'Z24', 'range' => [16000, 18000]],
            ['name' => 'Z25', 'range' => [18000, 20000]],
            ['name' => 'Z26', 'range' => [20000, 25000]],
            ['name' => 'Z27', 'range' => [25000, 30000]],
            ['name' => 'Z28', 'range' => [30000, 50000]],
            ['name' => 'Z29', 'range' => [50000, 70000]],
            ['name' => 'Z30', 'range' => [70000, 100000]],
            ['name' => 'Z31', 'range' => [100000, 150000]],
            ['name' => 'Z32', 'range' => [150000, 200000]],
            ['name' => 'Z33', 'range' => [200000, 300000]],
            ['name' => 'Z34', 'range' => [300000, 500000]],
            ['name' => 'Z35', 'range' => [500000, 1000000]],
            ['name' => 'Z36', 'range' => [1000000, 2000000]],
        ]
    ];

    public $rangeRatio = [
        [
            'C_INTRATESL',//12净利率
            'ATOL',//13资产周转率
            'ASSGRO_C_INTRATESL',//14总资产净利率
            'ROAL',//17总资产回报率 ROA
            'ROE_AL',//18净资产回报率 ROE (A)
            'ROE_BL',//19净资产回报率 ROE (B)
            'DEBTL',//20资产负债率
            'MAIBUSINC_RATIOL',//22主营业务比率
            'NALR',//23净资产负债率
            'OPM',//24营业利润率
            'ROCA',//25资本保值增值率
            'NOR',//26营业净利率
            'PMOTA',//27总资产利润率
            'TBR',//28税收负担率
            'ASSGRO_yoy',//30资产总额同比
            'LIAGRO_yoy',//31负债总额同比
            'VENDINC_yoy',//32营业总收入同比
            'MAIBUSINC_yoy',//33主营业务收入同比
            'PROGRO_yoy',//34利润总额同比
            'NETINC_yoy',//35净利润同比
            'RATGRO_yoy',//36纳税总额同比
            'TOTEQU_yoy',//37所有者权益同比
            'TBR_new',//38税收负担率
            'SOCNUM_yoy',//39社保人数同比
        ],
        [
            ['name' => 'F13', 'range' => [-40.96, -20.48]],
            ['name' => 'F12', 'range' => [-20.48, -10.24]],
            ['name' => 'F11', 'range' => [-10.24, -5.12]],
            ['name' => 'F10', 'range' => [-5.12, -2.56]],
            ['name' => 'F09', 'range' => [-2.56, -1.28]],
            ['name' => 'F08', 'range' => [-1.28, -0.64]],
            ['name' => 'F07', 'range' => [-0.64, -0.32]],
            ['name' => 'F06', 'range' => [-0.32, -0.16]],
            ['name' => 'F05', 'range' => [-0.16, -0.08]],
            ['name' => 'F04', 'range' => [-0.08, -0.04]],
            ['name' => 'F03', 'range' => [-0.04, -0.02]],
            ['name' => 'F02', 'range' => [-0.02, -0.01]],
            ['name' => 'F01', 'range' => [-0.01, 0]],
            ['name' => 'Z01', 'range' => [0, 0.01]],
            ['name' => 'Z02', 'range' => [0.01, 0.02]],
            ['name' => 'Z03', 'range' => [0.02, 0.04]],
            ['name' => 'Z04', 'range' => [0.04, 0.08]],
            ['name' => 'Z05', 'range' => [0.08, 0.16]],
            ['name' => 'Z06', 'range' => [0.16, 0.32]],
            ['name' => 'Z07', 'range' => [0.32, 0.64]],
            ['name' => 'Z08', 'range' => [0.64, 1.28]],
            ['name' => 'Z09', 'range' => [1.28, 2.56]],
            ['name' => 'Z10', 'range' => [2.56, 5.12]],
            ['name' => 'Z11', 'range' => [5.12, 10.24]],
            ['name' => 'Z12', 'range' => [10.24, 20.48]],
            ['name' => 'Z13', 'range' => [20.48, 40.96]],
        ]
    ];

    function getRange($attr): array
    {
        return $this->{$attr};
    }


}
