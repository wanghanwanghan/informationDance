<?php

namespace App\HttpController\Service\LongXin;

use EasySwoole\Component\Singleton;

class FinanceRange
{
    use Singleton;

    //通用
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
            ['name' => 'Z01', 'range' => [0, 5]],
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

    //元起
    public $range_yuanqi = [
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
            ['name' => '1', 'range' => [10000, 5000]],
            ['name' => '2', 'range' => [5000, 3000]],
            ['name' => '3', 'range' => [3000, 2000]],
            ['name' => '4', 'range' => [2000, 1000]],
            ['name' => '5', 'range' => [1000, 500]],
            ['name' => '6', 'range' => [500, 100]],
            ['name' => '7', 'range' => [100, 0]],
            ['name' => '8', 'range' => [0, 100]],
            ['name' => '9', 'range' => [100, 200]],
            ['name' => '10', 'range' => [200, 300]],
            ['name' => '11', 'range' => [300, 400]],
            ['name' => '12', 'range' => [400, 500]],
            ['name' => '13', 'range' => [500, 600]],
            ['name' => '14', 'range' => [600, 800]],
            ['name' => '15', 'range' => [800, 1000]],
            ['name' => '16', 'range' => [1000, 1200]],
            ['name' => '17', 'range' => [1200, 1400]],
            ['name' => '18', 'range' => [1400, 1600]],
            ['name' => '19', 'range' => [1600, 1800]],
            ['name' => '20', 'range' => [1800, 2000]],
            ['name' => '21', 'range' => [2000, 2200]],
            ['name' => '22', 'range' => [2200, 2400]],
            ['name' => '23', 'range' => [2400, 2600]],
            ['name' => '24', 'range' => [2600, 2800]],
            ['name' => '25', 'range' => [2800, 3000]],
            ['name' => '26', 'range' => [3000, 3100]],
            ['name' => '27', 'range' => [3100, 3200]],
            ['name' => '28', 'range' => [3200, 3300]],
            ['name' => '29', 'range' => [3300, 3400]],
            ['name' => '30', 'range' => [3400, 3500]],
            ['name' => '31', 'range' => [3500, 3600]],
            ['name' => '32', 'range' => [3600, 3700]],
            ['name' => '33', 'range' => [3700, 3800]],
            ['name' => '34', 'range' => [3800, 3900]],
            ['name' => '35', 'range' => [3900, 4000]],
            ['name' => '36', 'range' => [4000, 4100]],
            ['name' => '37', 'range' => [4100, 4200]],
            ['name' => '38', 'range' => [4200, 4300]],
            ['name' => '39', 'range' => [4300, 4400]],
            ['name' => '40', 'range' => [4400, 4500]],
            ['name' => '41', 'range' => [4500, 4600]],
            ['name' => '42', 'range' => [4600, 4700]],
            ['name' => '43', 'range' => [4700, 4800]],
            ['name' => '44', 'range' => [4800, 4900]],
            ['name' => '45', 'range' => [4900, 5000]],
            ['name' => '46', 'range' => [5000, 5200]],
            ['name' => '47', 'range' => [5200, 5400]],
            ['name' => '48', 'range' => [5400, 5600]],
            ['name' => '49', 'range' => [5600, 5800]],
            ['name' => '50', 'range' => [5800, 6000]],
            ['name' => '51', 'range' => [6000, 6200]],
            ['name' => '52', 'range' => [6200, 6400]],
            ['name' => '53', 'range' => [6400, 6600]],
            ['name' => '54', 'range' => [6600, 6800]],
            ['name' => '55', 'range' => [6800, 7000]],
            ['name' => '56', 'range' => [7000, 7200]],
            ['name' => '57', 'range' => [7200, 7400]],
            ['name' => '58', 'range' => [7400, 7600]],
            ['name' => '59', 'range' => [7600, 7800]],
            ['name' => '60', 'range' => [7800, 8000]],
            ['name' => '61', 'range' => [8000, 8200]],
            ['name' => '62', 'range' => [8200, 8400]],
            ['name' => '63', 'range' => [8400, 8600]],
            ['name' => '64', 'range' => [8600, 8800]],
            ['name' => '65', 'range' => [8800, 9000]],
            ['name' => '66', 'range' => [9000, 9200]],
            ['name' => '67', 'range' => [9200, 9400]],
            ['name' => '68', 'range' => [9400, 9600]],
            ['name' => '69', 'range' => [9600, 9800]],
            ['name' => '70', 'range' => [9800, 10000]],
            ['name' => '71', 'range' => [10000, 20000]],
            ['name' => '72', 'range' => [20000, 30000]],
        ]
    ];
    public $rangeRatio_yuanqi = [
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
            ['name' => 'A', 'range' => [-100, 0]],
            ['name' => 'B', 'range' => [0, 0.2]],
            ['name' => 'C', 'range' => [0.2, 0.4]],
            ['name' => 'D', 'range' => [0.4, 0.6]],
            ['name' => 'E', 'range' => [0.6, 0.8]],
            ['name' => 'F', 'range' => [0.8, 1.0]],
            ['name' => 'G', 'range' => [1.0, 2.0]],
        ]
    ];

    function getRange($attr): array
    {
        return $this->{$attr};
    }


}
