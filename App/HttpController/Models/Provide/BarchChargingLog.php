<?php

namespace App\HttpController\Models\Provide;

use App\HttpController\Models\ModelBase;

class BarchChargingLog extends ModelBase
{
    const TYPE_JI_BEN = 1;//基本信息
    const TYPE_GU_DONG = 2;//股东信息
    const TYPE_JING_SHANG_YI_CHANG = 3;//经商异常
    const TYOPE_NIAN_BAO = 4;//年报
    const TYPE_PAN_JUE = 5;//裁判文书

    const TYPE_QICHACHA = 1;//企查查
    const TYPE_TAOSU = 2;//淘数
    const TYPE_XI_NAN = 3;//西南
    const TYPE_FAHAI = 4;//法海

    public static $type_map = [
        self::TYPE_JI_BEN => [
            self::TYPE_QICHACHA => 'qichachaRegisterInfo',
            self::TYPE_TAOSU => 'taoshuRegisterInfo',
        ],
        self::TYPE_GU_DONG => [
            self::TYPE_QICHACHA,
            self::TYPE_TAOSU => 'taoshuGetShareHolderInfo',
        ],
        self::TYPE_JING_SHANG_YI_CHANG => [
            self::TYPE_QICHACHA => 'qichahchaGetOpException',
            self::TYPE_TAOSU => 'taoshuGetOperatingExceptionRota',
        ],
        self::TYOPE_NIAN_BAO => [
            self::TYPE_XI_NAN =>'xinanGetFinanceNotAuth'
        ],
        self::TYPE_PAN_JUE => [
            self::TYPE_FAHAI => 'fahaiGetCpws'
        ],
    ];
    protected $tableName = 'information_dance_barch_charging_log';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}