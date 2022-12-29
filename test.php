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
    'case_all',
    'case_check',
    'case_yzwfsx',
    'company_abnormity',
    'company_ar',
    'company_ar_alterstockinfo',
    'company_ar_asset',
    'company_ar_capital',
    'company_ar_forguaranteeinfo',
    'company_ar_forinvestment',
    'company_ar_modify',
    'company_ar_socialfee',
    'company_ar_websiteinfo',
    'company_basic',
    'company_cancel_info',
    'company_certificate',
    'company_class_h',
    'company_filiation',
    'company_history_inv',
    'company_history_manager',
    'company_history_name',
    'company_inv',
    'company_investment',
    'company_ipr',
    'company_ipr_change',
    'company_liquidation',
    'company_manager',
    'company_modify',
    'company_mort',
    'company_mort_change',
    'company_mort_pawn',
    'company_mort_people',
    'company_stock_impawn',
];

foreach ($list as $one) {

    echo "GRANT ALL PRIVILEGES ON `hd_saic`.`{$one}` TO 'dts_user'@'%';" . PHP_EOL;

}


