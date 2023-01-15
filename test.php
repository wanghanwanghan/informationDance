<?php

$list = [
    'b_code_nic_csrc',
    'b_code_nic_gics',
    'b_code_nic_sws',
    'code_ca04',
    'code_ca11',
    'code_ca16',
    'code_ca24',
    'code_ca84',
    'code_ca85',
    'code_ca86',
    'code_ca87',
    'code_ca88',
    'code_ca89',
    'code_ce02',
    'code_ex02',
    'code_region',
    'aggre_en_h',
    'company_inv',
    'company_basic',
    'company_manager',
    'case_check',
    'company_investment',
    'company_filiation',
    'company_modify',
    'company_stock_impawn',
    'company_abnormity',
    'company_ar_modify',
    'company_ar_capital',
    'company_ar_forguaranteeinfo',
    'company_ar_forinvestment',
    'company_ar_alterstockinfo',
    'company_ar_socialfee',
    'company_ar_websiteinfo',
    'company_ar',
    'company_ar_asset',
    'case_all',
    'company_certificate',
    'case_yzwfsx',
    'company_ipr',
    'company_ipr_change',
    'company_cancel_info',
    'company_history_name',
    'company_history_inv',
    'company_history_manager',
    'company_class_h',
];

sort($list);

foreach ($list as $one) {
    echo "GRANT SELECT ON `hd_saic`.`{$one}` TO 'dts_user'@'%';" . PHP_EOL;
}

echo "GRANT SELECT ON `hd_saic_extension`.`dataplus_intro_h1` TO 'dts_user'@'%';" . PHP_EOL;
echo "GRANT SELECT ON `hd_saic_extension`.`dataplus_intro_h5` TO 'dts_user'@'%';" . PHP_EOL;
echo "GRANT SELECT ON `hd_saic_extension`.`dataplus_intro_h6` TO 'dts_user'@'%';" . PHP_EOL;
echo "GRANT SELECT ON `hd_saic_extension`.`aqsiq_ancc_h` TO 'dts_user'@'%';" . PHP_EOL;


