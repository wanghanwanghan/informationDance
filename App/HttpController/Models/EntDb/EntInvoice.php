<?php

namespace App\HttpController\Models\EntDb;

use App\HttpController\Models\ModelBase;

class EntInvoice extends ModelBase
{
    protected $tableName = 'invoice';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    function addSuffix(string $code, string $type): EntInvoice
    {
        //01增值税专用发票 *** 本次蚂蚁用 type1
        //02货运运输业增值税专用发票
        //03机动车销售统一发票
        //04增值税普通发票 *** 本次蚂蚁用 type1
        //10增值税普通发票电子 *** 本次蚂蚁用 type1
        //11增值税普通发票卷式 *** 本次蚂蚁用 type1
        //14通行费电子票 *** 本次蚂蚁用 type2
        //15二手车销售统一发票

        $sql = <<<Eof
CREATE TABLE `invoice_type1_0` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `fpdm` varchar(16) NOT NULL DEFAULT '' COMMENT '发票代码',
  `fphm` varchar(16) NOT NULL DEFAULT '' COMMENT '发票号码',
  `kplx` varchar(4) NOT NULL DEFAULT '' COMMENT '开票类型 0-蓝字 1-红字',
  `xfsh` varchar(32) NOT NULL DEFAULT '' COMMENT '销售方纳税人识别号',
  `xfmc` varchar(128) NOT NULL DEFAULT '' COMMENT '销售方名称',
  `xfdzdh` varchar(128) NOT NULL DEFAULT '' COMMENT '销售方地址电话',
  `xfyhzh` varchar(128) NOT NULL DEFAULT '' COMMENT '销售方银行账号',
  `gfsh` varchar(32) NOT NULL DEFAULT '' COMMENT '购买方纳税人识别号',
  `gfmc` varchar(128) NOT NULL DEFAULT '' COMMENT '购买方名称',
  `gfdzdh` varchar(128) NOT NULL DEFAULT '' COMMENT '购买方地址电话',
  `gfyhzh` varchar(128) NOT NULL DEFAULT '' COMMENT '购买方银行账号',
  `gmflx` varchar(4) NOT NULL DEFAULT '' COMMENT '购买方类型 1企业 2个人 3其他',
  `kpr` varchar(16) NOT NULL DEFAULT '' COMMENT '开票人',
  `skr` varchar(16) NOT NULL DEFAULT '' COMMENT '收款人',
  `fhr` varchar(16) NOT NULL DEFAULT '' COMMENT '复核人',
  `yfpdm` varchar(16) NOT NULL DEFAULT '' COMMENT '原发票代码 kplx为1时必填',
  `yfphm` varchar(16) NOT NULL DEFAULT '' COMMENT '原发票号码 kplx为1时必填',
  `je` varchar(32) NOT NULL DEFAULT '' COMMENT '金额',
  `se` varchar(32) NOT NULL DEFAULT '' COMMENT '税额',
  `jshj` varchar(32) NOT NULL DEFAULT '' COMMENT '价税合计 单位元 2位小数',
  `bz` varchar(256) NOT NULL DEFAULT '' COMMENT '备注',
  `zfbz` varchar(4) NOT NULL DEFAULT '' COMMENT '作废标志 0-未作废 1-作废',
  `zfsj` varchar(16) NOT NULL DEFAULT '' COMMENT '作废时间',
  `kprq` varchar(16) NOT NULL DEFAULT '' COMMENT '开票日期',
  `kprq_sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序用',
  `fplx` varchar(4) NOT NULL DEFAULT '' COMMENT '发票类型代码 01 02 03 04 10 11 14 15',
  `fpztDm` varchar(4) NOT NULL DEFAULT '' COMMENT '发票状态代码 0-正常 1-失控 2-作废 3-红字 4-异常票',
  `slbz` varchar(4) NOT NULL DEFAULT '' COMMENT '税率标识 0-不含税税率 1-含税税率',
  `rzdklBdjgDm` varchar(4) NOT NULL DEFAULT '' COMMENT '认证状态',
  `rzdklBdrq` varchar(16) NOT NULL DEFAULT '' COMMENT '认证日期',
  `direction` varchar(4) NOT NULL DEFAULT '' COMMENT '01-购买方 02-销售方',
  `nsrsbh` varchar(32) NOT NULL DEFAULT '' COMMENT '查询企业税号',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fpdm_fphm_direction_index` (`fpdm`,`fphm`,`direction`) USING BTREE,
  KEY `nsrsbh_direction_kprq_sort_index` (`nsrsbh`,`direction`,`kprq_sort`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='发票样式type1表';
Eof;


        $this->tableName(implode('_', [
            $this->tableName,
            $type,
            $this->suffixNum($code),
        ]));

        return $this;
    }

    //只含有26个字母或者数字的并且都是半角的字符串，转换成数字，用于hash分表
    function suffixNum(string $str): int
    {
        $j = 0;
        for ($i = 0; $i < strlen($str); $i++) {
            is_numeric($str[$i]) ? $j += $str[$i] : $j += ord($str[$i]);
        }
        return $j % 10;
    }
}
