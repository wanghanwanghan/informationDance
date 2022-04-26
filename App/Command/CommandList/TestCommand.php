<?php

namespace App\Command\CommandList;

use App\Command\CommandBase;
use App\HttpController\Service\LongXin\LongXinService;
use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Service\Common\CommonService;
use EasySwoole\ElasticSearch\Config;
use EasySwoole\ElasticSearch\ElasticSearch;
use EasySwoole\ElasticSearch\RequestBean\Search;

class TestCommand extends CommandBase
{
    public $queryArr = [];
    function commandName(): string
    {
        return 'test';
    }

    //php easyswoole test
    //只能执行initialize里的
    function exec(array $args): ?string
    {
        parent::commendInit();

        return 'this is exec' . PHP_EOL;
    }

    //php easyswoole help test
    function help(array $args): ?string
    { 
        $postData = [
            'name' => '青岛中燃银达油品有限公司',
            'property1' => '91233004061750358N',
            'company_org_type' => '有限责任公司',
            'reg_location' => '青海省',
            'ying_shou_gui_mo' => 'A6',
            'min_estiblish_time' => '2019-12-30',
            'max_estiblish_time' => '2020-12-30',
            'business_scope' => '热力生产和供应',
            'min_reg_capital' => '100万人民币',
            'max_reg_capital' => '30000万人民币',
            'reg_status' => '在营',
            'si_ji_fen_lei_code' => 'D4430',
            'gao_xin_ji_shu' => '',
            'deng_ling_qi_ye' => '',
            'tuan_dui_ren_shu' =>'',
            'shang_pin_data'=>'',
            'app_data' => '',
            'yi_ban_ren' => '',
            'shang_shi_xin_xi' => '',
            'web' => '',
            'tong_xun_di_zhi',
            'app'=>'',
            'page' => 1,
            'size' => 20,
        ];
        
        $res = (new LongXinService())->advancedSearch($postData);

        return    json_encode( $res) . PHP_EOL; 

    } 
}