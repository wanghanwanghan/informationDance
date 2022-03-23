<?php

namespace App\HttpController\Models\Provide;

use App\HttpController\Models\ModelBase;

class BarchChargingLog extends ModelBase
{

    const TYPE_JI_BEN = 1;//基本信息
    const TYPE_GU_DONG = 2;//股东信息
    const TYPE_JING_SHANG_YI_CHANG = 3;//经商异常
    const TYOPE_NIAN_BAO = 4;//财务
    const TYPE_PAN_JUE = 5;//裁判文书

    const TYPE_QICHACHA = 1;//企查查
    const TYPE_TAOSU = 2;//淘数
    const TYPE_XI_NAN = 3;//西南
    const TYPE_FAHAI = 4;//法海
    public $a = [
        '工商信息'=>["基本信息",'股东','实际控制人','企业主要管理人','分支机构','变更信息','法人变更','经营异常'],
        '财务' => [''],
        '司法' => ['开庭公告','判决文书','法院公告','执行公告','失信公告','司法查封冻结扣押','司法拍卖'],
        '涉税' => ['欠税公告','涉税处罚公示','税务非正常户公示','纳税信用等级','税务登记','税务许可'],
        '知识产权' => ['商标','专利','软件著作权','作品著作权','企业证书查询'],
        '金融监管' => ['央行行政处罚','银保监会处罚公示','证监处罚公示','','']
    ];
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