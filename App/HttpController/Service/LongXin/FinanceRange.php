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
            'C_ASSGROL_yoy',//40净资产同比
            'A_ASSGROL_yoy',//41平均资产总额同比
            'CA_ASSGROL_yoy',//42平均净资产同比
            'A_VENDINCL_yoy',//43企业人均产值同比
            'A_PROGROL_yoy',//44企业人均盈利同比
            'EQUITYL',
            'EQUITYL_new',
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
            ['name' => '1', 'range' => [-10000, -5000]],
            ['name' => '2', 'range' => [-5000, -3000]],
            ['name' => '3', 'range' => [-3000, -2000]],
            ['name' => '4', 'range' => [-2000, -1000]],
            ['name' => '5', 'range' => [-1000, -500]],
            ['name' => '6', 'range' => [-500, -100]],
            ['name' => '7', 'range' => [-100, 0]],
            ['name' => '7.5', 'range' => [0, 0]],
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
            'C_ASSGROL_yoy',//40净资产同比
            'A_ASSGROL_yoy',//41平均资产总额同比
            'CA_ASSGROL_yoy',//42平均净资产同比
            'A_VENDINCL_yoy',//43企业人均产值同比
            'A_PROGROL_yoy',//44企业人均盈利同比
            'EQUITYL',
            'EQUITYL_new',
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

    //投中
    public $range_touzhong = [
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
            ['name' => 'F64', 'range' => [-30000000000, -20000000000]],
            ['name' => 'F63', 'range' => [-20000000000, -17000000000]],
            ['name' => 'F62', 'range' => [-17000000000, -14000000000]],
            ['name' => 'F61', 'range' => [-14000000000, -12000000000]],
            ['name' => 'F60', 'range' => [-12000000000, -9700000000]],
            ['name' => 'F59', 'range' => [-9700000000, -8100000000]],
            ['name' => 'F58', 'range' => [-8100000000, -6800000000]],
            ['name' => 'F57', 'range' => [-6800000000, -5600000000]],
            ['name' => 'F56', 'range' => [-5600000000, -4700000000]],
            ['name' => 'F55', 'range' => [-4700000000, -3900000000]],
            ['name' => 'F54', 'range' => [-3900000000, -3300000000]],
            ['name' => 'F53', 'range' => [-3300000000, -2700000000]],
            ['name' => 'F52', 'range' => [-2700000000, -2300000000]],
            ['name' => 'F51', 'range' => [-2300000000, -1900000000]],
            ['name' => 'F50', 'range' => [-1900000000, -1600000000]],
            ['name' => 'F49', 'range' => [-1600000000, -1300000000]],
            ['name' => 'F48', 'range' => [-1300000000, -1100000000]],
            ['name' => 'F47', 'range' => [-1100000000, -910000000]],
            ['name' => 'F46', 'range' => [-910000000, -760000000]],
            ['name' => 'F45', 'range' => [-760000000, -630000000]],
            ['name' => 'F44', 'range' => [-630000000, -530000000]],
            ['name' => 'F43', 'range' => [-530000000, -440000000]],
            ['name' => 'F42', 'range' => [-440000000, -370000000]],
            ['name' => 'F41', 'range' => [-370000000, -300000000]],
            ['name' => 'F40', 'range' => [-300000000, -250000000]],
            ['name' => 'F39', 'range' => [-250000000, -210000000]],
            ['name' => 'F38', 'range' => [-210000000, -180000000]],
            ['name' => 'F37', 'range' => [-180000000, -150000000]],
            ['name' => 'F36', 'range' => [-150000000, -120000000]],
            ['name' => 'F35', 'range' => [-120000000, -100000000]],
            ['name' => 'F34', 'range' => [-100000000, -85000000]],
            ['name' => 'F33', 'range' => [-85000000, -71000000]],
            ['name' => 'F32', 'range' => [-71000000, -59000000]],
            ['name' => 'F31', 'range' => [-59000000, -49000000]],
            ['name' => 'F30', 'range' => [-49000000, -41000000]],
            ['name' => 'F29', 'range' => [-41000000, -34000000]],
            ['name' => 'F28', 'range' => [-34000000, -28000000]],
            ['name' => 'F27', 'range' => [-28000000, -24000000]],
            ['name' => 'F26', 'range' => [-24000000, -20000000]],
            ['name' => 'F25', 'range' => [-20000000, -16000000]],
            ['name' => 'F24', 'range' => [-16000000, -14000000]],
            ['name' => 'F23', 'range' => [-14000000, -11000000]],
            ['name' => 'F22', 'range' => [-11000000, -9500000]],
            ['name' => 'F21', 'range' => [-9500000, -7900000]],
            ['name' => 'F20', 'range' => [-7900000, -6600000]],
            ['name' => 'F19', 'range' => [-6600000, -5500000]],
            ['name' => 'F18', 'range' => [-5500000, -4600000]],
            ['name' => 'F17', 'range' => [-4600000, -3800000]],
            ['name' => 'F16', 'range' => [-3800000, -3200000]],
            ['name' => 'F15', 'range' => [-3200000, -2700000]],
            ['name' => 'F14', 'range' => [-2700000, -2400000]],
            ['name' => 'F13', 'range' => [-2400000, -2200000]],
            ['name' => 'F12', 'range' => [-2200000, -2000000]],
            ['name' => 'F11', 'range' => [-2000000, -1800000]],
            ['name' => 'F10', 'range' => [-1800000, -1600000]],
            ['name' => 'F09', 'range' => [-1600000, -1400000]],
            ['name' => 'F08', 'range' => [-1400000, -1200000]],
            ['name' => 'F07', 'range' => [-1200000, -1000000]],
            ['name' => 'F06', 'range' => [-1000000, -800000]],
            ['name' => 'F05', 'range' => [-800000, -600000]],
            ['name' => 'F04', 'range' => [-600000, -400000]],
            ['name' => 'F03', 'range' => [-400000, -200000]],
            ['name' => 'F02', 'range' => [-200000, -100000]],
            ['name' => 'F01', 'range' => [-100000, -1]],
            ['name' => 'Z00', 'range' => [-1, 1]],
            ['name' => 'Z01', 'range' => [1, 100000]],
            ['name' => 'Z02', 'range' => [100000, 200000]],
            ['name' => 'Z03', 'range' => [200000, 400000]],
            ['name' => 'Z04', 'range' => [400000, 600000]],
            ['name' => 'Z05', 'range' => [600000, 800000]],
            ['name' => 'Z06', 'range' => [800000, 1000000]],
            ['name' => 'Z07', 'range' => [1000000, 1200000]],
            ['name' => 'Z08', 'range' => [1200000, 1400000]],
            ['name' => 'Z09', 'range' => [1400000, 1600000]],
            ['name' => 'Z10', 'range' => [1600000, 1800000]],
            ['name' => 'Z11', 'range' => [1800000, 2000000]],
            ['name' => 'Z12', 'range' => [2000000, 2200000]],
            ['name' => 'Z13', 'range' => [2200000, 2400000]],
            ['name' => 'Z14', 'range' => [2400000, 2700000]],
            ['name' => 'Z15', 'range' => [2700000, 3200000]],
            ['name' => 'Z16', 'range' => [3200000, 3800000]],
            ['name' => 'Z17', 'range' => [3800000, 4600000]],
            ['name' => 'Z18', 'range' => [4600000, 5500000]],
            ['name' => 'Z19', 'range' => [5500000, 6600000]],
            ['name' => 'Z20', 'range' => [6600000, 7900000]],
            ['name' => 'Z21', 'range' => [7900000, 9500000]],
            ['name' => 'Z22', 'range' => [9500000, 11000000]],
            ['name' => 'Z23', 'range' => [11000000, 14000000]],
            ['name' => 'Z24', 'range' => [14000000, 16000000]],
            ['name' => 'Z25', 'range' => [16000000, 20000000]],
            ['name' => 'Z26', 'range' => [20000000, 24000000]],
            ['name' => 'Z27', 'range' => [24000000, 28000000]],
            ['name' => 'Z28', 'range' => [28000000, 34000000]],
            ['name' => 'Z29', 'range' => [34000000, 41000000]],
            ['name' => 'Z30', 'range' => [41000000, 49000000]],
            ['name' => 'Z31', 'range' => [49000000, 59000000]],
            ['name' => 'Z32', 'range' => [59000000, 71000000]],
            ['name' => 'Z33', 'range' => [71000000, 85000000]],
            ['name' => 'Z34', 'range' => [85000000, 100000000]],
            ['name' => 'Z35', 'range' => [100000000, 120000000]],
            ['name' => 'Z36', 'range' => [120000000, 150000000]],
            ['name' => 'Z37', 'range' => [150000000, 180000000]],
            ['name' => 'Z38', 'range' => [180000000, 210000000]],
            ['name' => 'Z39', 'range' => [210000000, 250000000]],
            ['name' => 'Z40', 'range' => [250000000, 300000000]],
            ['name' => 'Z41', 'range' => [300000000, 370000000]],
            ['name' => 'Z42', 'range' => [370000000, 440000000]],
            ['name' => 'Z43', 'range' => [440000000, 530000000]],
            ['name' => 'Z44', 'range' => [530000000, 630000000]],
            ['name' => 'Z45', 'range' => [630000000, 760000000]],
            ['name' => 'Z46', 'range' => [760000000, 910000000]],
            ['name' => 'Z47', 'range' => [910000000, 1100000000]],
            ['name' => 'Z48', 'range' => [1100000000, 1300000000]],
            ['name' => 'Z49', 'range' => [1300000000, 1600000000]],
            ['name' => 'Z50', 'range' => [1600000000, 1900000000]],
            ['name' => 'Z51', 'range' => [1900000000, 2300000000]],
            ['name' => 'Z52', 'range' => [2300000000, 2700000000]],
            ['name' => 'Z53', 'range' => [2700000000, 3300000000]],
            ['name' => 'Z54', 'range' => [3300000000, 3900000000]],
            ['name' => 'Z55', 'range' => [3900000000, 4700000000]],
            ['name' => 'Z56', 'range' => [4700000000, 5600000000]],
            ['name' => 'Z57', 'range' => [5600000000, 6800000000]],
            ['name' => 'Z58', 'range' => [6800000000, 8100000000]],
            ['name' => 'Z59', 'range' => [8100000000, 9700000000]],
            ['name' => 'Z60', 'range' => [9700000000, 12000000000]],
            ['name' => 'Z61', 'range' => [12000000000, 14000000000]],
            ['name' => 'Z62', 'range' => [14000000000, 17000000000]],
            ['name' => 'Z63', 'range' => [17000000000, 20000000000]],
            ['name' => 'Z64', 'range' => [20000000000, 24000000000]],
            ['name' => 'Z65', 'range' => [24000000000, 29000000000]],
            ['name' => 'Z66', 'range' => [29000000000, 35000000000]],
            ['name' => 'Z67', 'range' => [35000000000, 42000000000]],
            ['name' => 'Z68', 'range' => [42000000000, 50000000000]],
            ['name' => 'Z69', 'range' => [50000000000, 60000000000]],
            ['name' => 'Z70', 'range' => [60000000000, 72000000000]],
            ['name' => 'Z71', 'range' => [72000000000, 87000000000]],
            ['name' => 'Z72', 'range' => [87000000000, 100000000000]],
            ['name' => 'Z73', 'range' => [100000000000, 130000000000]],
            ['name' => 'Z74', 'range' => [130000000000, 150000000000]],
            ['name' => 'Z75', 'range' => [150000000000, 180000000000]],
            ['name' => 'Z76', 'range' => [180000000000, 220000000000]],
            ['name' => 'Z77', 'range' => [220000000000, 260000000000]],
            ['name' => 'Z78', 'range' => [260000000000, 310000000000]],
            ['name' => 'Z79', 'range' => [310000000000, 370000000000]],
            ['name' => 'Z80', 'range' => [370000000000, 450000000000]],
            ['name' => 'Z81', 'range' => [450000000000, 540000000000]],
            ['name' => 'Z82', 'range' => [540000000000, 650000000000]],
            ['name' => 'Z83', 'range' => [650000000000, 780000000000]],
            ['name' => 'Z84', 'range' => [780000000000, 930000000000]],
            ['name' => 'Z85', 'range' => [930000000000, 940000000000]],
        ]
    ];
    public $rangeRatio_touzhong = [
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
            'C_ASSGROL_yoy',//40净资产同比
            'A_ASSGROL_yoy',//41平均资产总额同比
            'CA_ASSGROL_yoy',//42平均净资产同比
            'A_VENDINCL_yoy',//43企业人均产值同比
            'A_PROGROL_yoy',//44企业人均盈利同比
            'EQUITYL',
            'EQUITYL_new',
        ],
        [
            ['name' => 'R00', 'range' => [-40, -30]],
            ['name' => 'R01', 'range' => [-30, -20]],
            ['name' => 'R02', 'range' => [-20, -15]],
            ['name' => 'R03', 'range' => [-15, -10]],
            ['name' => 'R04', 'range' => [-10, -8]],
            ['name' => 'R05', 'range' => [-8, -6.5]],
            ['name' => 'R06', 'range' => [-6.5, -5]],
            ['name' => 'R07', 'range' => [-5, -4.5]],
            ['name' => 'R08', 'range' => [-4.5, -4]],
            ['name' => 'R09', 'range' => [-4, -3.5]],
            ['name' => 'R10', 'range' => [-3.5, -3]],
            ['name' => 'R11', 'range' => [-3, -2.75]],
            ['name' => 'R12', 'range' => [-2.75, -2.5]],
            ['name' => 'R13', 'range' => [-2.5, -2.25]],
            ['name' => 'R14', 'range' => [-2.25, -2]],
            ['name' => 'R15', 'range' => [-2, -1.8]],
            ['name' => 'R16', 'range' => [-1.8, -1.6]],
            ['name' => 'R17', 'range' => [-1.6, -1.4]],
            ['name' => 'R18', 'range' => [-1.4, -1.3]],
            ['name' => 'R19', 'range' => [-1.3, -1.2]],
            ['name' => 'R20', 'range' => [-1.2, -1.1]],
            ['name' => 'R21', 'range' => [-1.1, -1]],
            ['name' => 'R22', 'range' => [-1, -0.9]],
            ['name' => 'R23', 'range' => [-0.9, -0.85]],
            ['name' => 'R24', 'range' => [-0.85, -0.8]],
            ['name' => 'R25', 'range' => [-0.8, -0.75]],
            ['name' => 'R26', 'range' => [-0.75, -0.7]],
            ['name' => 'R27', 'range' => [-0.7, -0.65]],
            ['name' => 'R28', 'range' => [-0.65, -0.6]],
            ['name' => 'R29', 'range' => [-0.6, -0.55]],
            ['name' => 'R30', 'range' => [-0.55, -0.5]],
            ['name' => 'R31', 'range' => [-0.5, -0.45]],
            ['name' => 'R32', 'range' => [-0.45, -0.4]],
            ['name' => 'R33', 'range' => [-0.4, -0.35]],
            ['name' => 'R34', 'range' => [-0.35, -0.3]],
            ['name' => 'R35', 'range' => [-0.3, -0.25]],
            ['name' => 'R36', 'range' => [-0.25, -0.2]],
            ['name' => 'R37', 'range' => [-0.2, -0.15]],
            ['name' => 'R38', 'range' => [-0.15, -0.1]],
            ['name' => 'R39', 'range' => [-0.1, -0.05]],
            ['name' => 'R40', 'range' => [-0.05, 0]],
            ['name' => 'R41', 'range' => [0, 0]],
            ['name' => 'R42', 'range' => [0, 0.05]],
            ['name' => 'R43', 'range' => [0.05, 0.1]],
            ['name' => 'R44', 'range' => [0.1, 0.15]],
            ['name' => 'R45', 'range' => [0.15, 0.2]],
            ['name' => 'R46', 'range' => [0.2, 0.25]],
            ['name' => 'R47', 'range' => [0.25, 0.3]],
            ['name' => 'R48', 'range' => [0.3, 0.35]],
            ['name' => 'R49', 'range' => [0.35, 0.4]],
            ['name' => 'R50', 'range' => [0.4, 0.45]],
            ['name' => 'R51', 'range' => [0.45, 0.5]],
            ['name' => 'R52', 'range' => [0.5, 0.55]],
            ['name' => 'R53', 'range' => [0.55, 0.6]],
            ['name' => 'R54', 'range' => [0.6, 0.65]],
            ['name' => 'R55', 'range' => [0.65, 0.7]],
            ['name' => 'R56', 'range' => [0.7, 0.75]],
            ['name' => 'R57', 'range' => [0.75, 0.8]],
            ['name' => 'R58', 'range' => [0.8, 0.85]],
            ['name' => 'R59', 'range' => [0.85, 0.9]],
            ['name' => 'R60', 'range' => [0.9, 1]],
            ['name' => 'R61', 'range' => [1, 1.1]],
            ['name' => 'R62', 'range' => [1.1, 1.2]],
            ['name' => 'R63', 'range' => [1.2, 1.3]],
            ['name' => 'R64', 'range' => [1.3, 1.4]],
            ['name' => 'R65', 'range' => [1.4, 1.6]],
            ['name' => 'R66', 'range' => [1.6, 1.8]],
            ['name' => 'R67', 'range' => [1.8, 2]],
            ['name' => 'R68', 'range' => [2, 2.25]],
            ['name' => 'R69', 'range' => [2.25, 2.5]],
            ['name' => 'R70', 'range' => [2.5, 2.75]],
            ['name' => 'R71', 'range' => [2.75, 3]],
            ['name' => 'R72', 'range' => [3, 3.5]],
            ['name' => 'R73', 'range' => [3.5, 4]],
            ['name' => 'R74', 'range' => [4, 4.5]],
            ['name' => 'R75', 'range' => [4.5, 5]],
            ['name' => 'R76', 'range' => [5, 6.5]],
            ['name' => 'R77', 'range' => [6.5, 8]],
            ['name' => 'R78', 'range' => [8, 10]],
            ['name' => 'R79', 'range' => [10, 15]],
            ['name' => 'R80', 'range' => [15, 20]],
            ['name' => 'R81', 'range' => [20, 30]],
            ['name' => 'R82', 'range' => [30, 40]],
        ]
    ];

    function getRange($attr): array
    {
        return $this->{$attr};
    }


}
