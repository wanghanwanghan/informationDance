<?php

namespace App\HttpController\Models\Provide;

use App\HttpController\Models\ModelBase;

class BarchChargingLog extends ModelBase
{

    const TYPE_TRIPARTITE_QICHACHA = 1;//企查查
    const TYPE_TRIPARTITE_TAOSU = 2;//淘数
    const TYPE_TRIPARTITE_XI_NAN = 3;//西南
    const TYPE_TRIPARTITE_FAHAI = 4;//法海

    const TYPE_GONGSHANG = 1;                       //工商信息
    const TYPE_GONGSHANG_JB = 11;                //基本信息
    const TYPE_GONGSHANG_GD = 12;               //股东
    const TYPE_GONGSHANG_SJKGR = 13;           //实际控制人
    const TYPE_GONGSHANG_QYZYGLR = 14;        //企业主要管理人
    const TYPE_GONGSHANG_FZJG = 15;               //分支机构
    const TYPE_GONGSHANG_BGXX = 16;               //变更信息
    const TYPE_GONGSHANG_FRBG = 17;          //法人变更
    const TYPE_GONGSHANG_JYYC = 18;             //经营异常

    const TYPE_CAIWU = 2;                           //财务
    const TYPE_CAIWU_8 = 21;                           //财务

    const TYPE_SIFA = 3;                            //司法
    const TYPE_SIFA_KTGG = 1;                       //开庭公告
    const TYPE_SIFA_PJWC = 2;                       //判决文书
    const TYPE_SIFA_FYGG = 3;                       //法院公告
    const TYPE_SIFA_ZXGG = 4;                       //执行公告
    const TYPE_SIFA_SXGG = 5;                       //失信公告
    const TYPE_SIFA_SFCFDJKY = 6;                   //司法查封冻结扣押
    const TYPE_SIFA_SFPM = 7;                       //司法拍卖

    const TYPE_SHESHUI = 4;                         //涉税
    const TYPE_SHESHUI_QSGG = 41;                    //欠税公告
    const TYPE_SHESHUI_SSCFGS = 42;                  //涉税处罚公示
    const TYPE_SHESHUI_SWFZCHGS = 43;                //税务非正常户公示
    const TYPE_SHESHUI_NSXYDJ = 44;                  //纳税信用等级
    const TYPE_SHESHUI_SWDJ = 45;                    //税务登记
    const TYPE_SHESHUI_SWXK = 46;                    //税务许可

    const TYPE_ZHISHICHANQUAN = 5;                  //知识产权
    const TYPE_ZHISHICHANQUAN_SB = 51;                  //商标
    const TYPE_ZHISHICHANQUAN_ZL = 52;                  //专利
    const TYPE_ZHISHICHANQUAN_RJZZQ = 53;                  //软件著作权
    const TYPE_ZHISHICHANQUAN_ZPZZQ = 54;                  //作品著作权
    const TYPE_ZHISHICHANQUAN_QYZSCX = 55;                  //企业证书查询

    const TYPE_JINRONGJIANGUAN = 6;                 //金融监管
    const TYPE_JINRONGJIANGUAN_YHXZCF = 61;                 //央行行政处罚
    const TYPE_JINRONGJIANGUAN_YBJHCFGS = 62;                 //银保监会处罚公示
    const TYPE_JINRONGJIANGUAN_ZJCFGS = 63;                 //证监处罚公示
    const TYPE_JINRONGJIANGUAN_ZJHXKPFDJ = 64;                 //证监会许可批复登记
    const TYPE_JINRONGJIANGUAN_WHJCF = 65;                 //外汇局处罚
    const TYPE_JINRONGJIANGUAN_WHJXK = 66;                 //外汇局许可


    public static $type_map = [
        self::TYPE_GONGSHANG => [
            self::TYPE_GONGSHANG_JB => [ self::TYPE_TRIPARTITE_TAOSU => 'taoshuRegisterInfo'],
            self::TYPE_GONGSHANG_GD => [ self::TYPE_TRIPARTITE_TAOSU => 'taoshuGetShareHolderInfo'],
            self::TYPE_GONGSHANG_SJKGR => [ self::TYPE_TRIPARTITE_TAOSU => ''],
            self::TYPE_GONGSHANG_QYZYGLR => [ self::TYPE_TRIPARTITE_TAOSU => ''],
            self::TYPE_GONGSHANG_FZJG => [ self::TYPE_TRIPARTITE_TAOSU => ''],
            self::TYPE_GONGSHANG_BGXX => [ self::TYPE_TRIPARTITE_TAOSU => ''],
            self::TYPE_GONGSHANG_FRBG => [ self::TYPE_TRIPARTITE_TAOSU => ''],
            self::TYPE_GONGSHANG_JYYC => [ self::TYPE_TRIPARTITE_TAOSU => 'taoshuGetOperatingExceptionRota'],
        ],
        self::TYPE_CAIWU => [],
        self::TYPE_SIFA => [
            self::TYPE_SIFA_KTGG => [self::TYPE_TRIPARTITE_FAHAI => ''],
            self::TYPE_SIFA_PJWC => [self::TYPE_TRIPARTITE_FAHAI => 'fahaiGetCpws'],
            self::TYPE_SIFA_FYGG => [self::TYPE_TRIPARTITE_FAHAI => ''],
            self::TYPE_SIFA_ZXGG => [self::TYPE_TRIPARTITE_FAHAI => ''],
            self::TYPE_SIFA_SXGG => [self::TYPE_TRIPARTITE_FAHAI => ''],
            self::TYPE_SIFA_SFCFDJKY => [self::TYPE_TRIPARTITE_FAHAI => ''],
            self::TYPE_SIFA_SFPM => [self::TYPE_TRIPARTITE_FAHAI => ''],
        ],
    ];
    protected $tableName = 'information_dance_barch_charging_log';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}