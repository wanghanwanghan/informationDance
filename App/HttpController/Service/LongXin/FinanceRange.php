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
            ['name' => 'F64', 'range' => []],
            ['name' => 'F63', 'range' => []],
            ['name' => 'F62', 'range' => []],
            ['name' => 'F61', 'range' => []],
            ['name' => 'F60', 'range' => []],
            ['name' => 'F59', 'range' => []],
            ['name' => 'F58', 'range' => []],
            ['name' => 'F57', 'range' => []],
            ['name' => 'F56', 'range' => []],
            ['name' => 'F55', 'range' => []],
            ['name' => 'F54', 'range' => []],
            ['name' => 'F53', 'range' => []],
            ['name' => 'F52', 'range' => []],
            ['name' => 'F51', 'range' => []],
            ['name' => 'F50', 'range' => []],
            ['name' => 'F49', 'range' => []],
            ['name' => 'F48', 'range' => []],
            ['name' => 'F47', 'range' => []],
            ['name' => 'F46', 'range' => []],
            ['name' => 'F45', 'range' => []],
            ['name' => 'F44', 'range' => []],
            ['name' => 'F43', 'range' => []],
            ['name' => 'F42', 'range' => []],
            ['name' => 'F41', 'range' => []],
            ['name' => 'F40', 'range' => []],
            ['name' => 'F39', 'range' => []],
            ['name' => 'F38', 'range' => []],
            ['name' => 'F37', 'range' => []],
            ['name' => 'F36', 'range' => []],
            ['name' => 'F35', 'range' => []],
            ['name' => 'F34', 'range' => []],
            ['name' => 'F33', 'range' => []],
            ['name' => 'F32', 'range' => []],
            ['name' => 'F31', 'range' => []],
            ['name' => 'F30', 'range' => []],
            ['name' => 'F29', 'range' => []],
            ['name' => 'F28', 'range' => []],
            ['name' => 'F27', 'range' => []],
            ['name' => 'F26', 'range' => []],
            ['name' => 'F25', 'range' => []],
            ['name' => 'F24', 'range' => []],
            ['name' => 'F23', 'range' => []],
            ['name' => 'F22', 'range' => []],
            ['name' => 'F21', 'range' => []],
            ['name' => 'F20', 'range' => []],
            ['name' => 'F19', 'range' => []],
            ['name' => 'F18', 'range' => []],
            ['name' => 'F17', 'range' => []],
            ['name' => 'F16', 'range' => []],
            ['name' => 'F15', 'range' => []],
            ['name' => 'F14', 'range' => []],
            ['name' => 'F13', 'range' => []],
            ['name' => 'F12', 'range' => []],
            ['name' => 'F11', 'range' => []],
            ['name' => 'F10', 'range' => []],
            ['name' => 'F09', 'range' => []],
            ['name' => 'F08', 'range' => []],
            ['name' => 'F07', 'range' => []],
            ['name' => 'F06', 'range' => []],
            ['name' => 'F05', 'range' => []],
            ['name' => 'F04', 'range' => []],
            ['name' => 'F03', 'range' => []],
            ['name' => 'F02', 'range' => []],
            ['name' => 'F01', 'range' => []],
            ['name' => 'Z00', 'range' => [0, 0]],
            ['name' => 'Z01', 'range' => [1, 10]],
            ['name' => 'Z02', 'range' => [10, 20]],
            ['name' => 'Z03', 'range' => [20, 40]],
            ['name' => 'Z04', 'range' => [40, 60]],
            ['name' => 'Z05', 'range' => [60, 80]],
            ['name' => 'Z06', 'range' => [80, 100]],
            ['name' => 'Z07', 'range' => [100, 120]],
            ['name' => 'Z08', 'range' => [120, 140]],
            ['name' => 'Z09', 'range' => [140, 160]],
            ['name' => 'Z10', 'range' => [160, 180]],
            ['name' => 'Z11', 'range' => [180, 200]],
            ['name' => 'Z12', 'range' => [200, 220]],
            ['name' => 'Z13', 'range' => [220, 240]],
            ['name' => 'Z14', 'range' => [240, 270]],
            ['name' => 'Z15', 'range' => [270, 320]],
            ['name' => 'Z16', 'range' => [320, 380]],
            ['name' => 'Z17', 'range' => [380, 460]],
            ['name' => 'Z18', 'range' => [460, 550]],
            ['name' => 'Z19', 'range' => [550, 660]],
            ['name' => 'Z20', 'range' => [660, 790]],
            ['name' => 'Z21', 'range' => [790, 950]],
            ['name' => 'Z22', 'range' => [950, 1100]],
            ['name' => 'Z23', 'range' => [1100, 1400]],
            ['name' => 'Z24', 'range' => [1400, 1600]],
            ['name' => 'Z25', 'range' => [1600, 2000]],
            ['name' => 'Z26', 'range' => [2000, 2400]],
            ['name' => 'Z27', 'range' => [2400, 2800]],
            ['name' => 'Z28', 'range' => [2800, 3400]],
            ['name' => 'Z29', 'range' => [3400, 4100]],
            ['name' => 'Z30', 'range' => [4100, 4900]],
            ['name' => 'Z31', 'range' => [4900, 5900]],
            ['name' => 'Z32', 'range' => [5900, 7100]],
            ['name' => 'Z33', 'range' => [7100, 8500]],
            ['name' => 'Z34', 'range' => [8500, 10000]],
            ['name' => 'Z35', 'range' => [10000, 12000]],
            ['name' => 'Z36', 'range' => [12000, 15000]],
            ['name' => 'Z37', 'range' => [15000, 18000]],
            ['name' => 'Z38', 'range' => [18000, 21000]],
            ['name' => 'Z39', 'range' => [21000, 25000]],
            ['name' => 'Z40', 'range' => [25000, 30000]],
            ['name' => 'Z41', 'range' => [30000, 37000]],
            ['name' => 'Z42', 'range' => [37000, 44000]],
            ['name' => 'Z43', 'range' => [44000, 53000]],
            ['name' => 'Z44', 'range' => [53000, 63000]],
            ['name' => 'Z45', 'range' => [63000, 76000]],
            ['name' => 'Z46', 'range' => [76000, 91000]],
            ['name' => 'Z47', 'range' => [91000, 110000]],
            ['name' => 'Z48', 'range' => [110000, 130000]],
            ['name' => 'Z49', 'range' => [130000, 160000]],
            ['name' => 'Z50', 'range' => [160000, 190000]],
            ['name' => 'Z51', 'range' => [190000, 230000]],
            ['name' => 'Z52', 'range' => [230000, 270000]],
            ['name' => 'Z53', 'range' => [270000, 330000]],
            ['name' => 'Z54', 'range' => [330000, 390000]],
            ['name' => 'Z55', 'range' => [390000, 470000]],
            ['name' => 'Z56', 'range' => [470000, 560000]],
            ['name' => 'Z57', 'range' => [560000, 680000]],
            ['name' => 'Z58', 'range' => [680000, 810000]],
            ['name' => 'Z59', 'range' => [810000, 970000]],
            ['name' => 'Z60', 'range' => [970000, 1200000]],
            ['name' => 'Z61', 'range' => [1200000, 1400000]],
            ['name' => 'Z62', 'range' => [1400000, 1700000]],
            ['name' => 'Z63', 'range' => [1700000, 2000000]],
            ['name' => 'Z64', 'range' => [2000000, 2400000]],
            ['name' => 'Z65', 'range' => [2400000, 2900000]],
            ['name' => 'Z66', 'range' => [2900000, 3500000]],
            ['name' => 'Z67', 'range' => [3500000, 4200000]],
            ['name' => 'Z68', 'range' => [4200000, 5000000]],
            ['name' => 'Z69', 'range' => [5000000, 6000000]],
            ['name' => 'Z70', 'range' => [6000000, 7200000]],
            ['name' => 'Z71', 'range' => [7200000, 8700000]],
            ['name' => 'Z72', 'range' => [8700000, 10000000]],
            ['name' => 'Z73', 'range' => [10000000, 13000000]],
            ['name' => 'Z74', 'range' => [13000000, 15000000]],
            ['name' => 'Z75', 'range' => [15000000, 18000000]],
            ['name' => 'Z76', 'range' => [18000000, 22000000]],
            ['name' => 'Z77', 'range' => [22000000, 26000000]],
            ['name' => 'Z78', 'range' => [26000000, 31000000]],
            ['name' => 'Z79', 'range' => [31000000, 37000000]],
            ['name' => 'Z80', 'range' => [37000000, 45000000]],
            ['name' => 'Z81', 'range' => [45000000, 54000000]],
            ['name' => 'Z82', 'range' => [54000000, 65000000]],
            ['name' => 'Z83', 'range' => [65000000, 78000000]],
            ['name' => 'Z84', 'range' => [78000000, 93000000]],
            ['name' => 'Z85', 'range' => [93000000, 93000000]],
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
