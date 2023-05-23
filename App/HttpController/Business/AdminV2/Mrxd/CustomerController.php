<?php

namespace App\HttpController\Business\AdminV2\Mrxd;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Business\OnlineGoods\Mrxd\DaiKuanController;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminUserBussinessOpportunityUploadRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\AdminUserWechatInfoUploadRecord;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\AdminV2\DeliverDetailsHistory;
use App\HttpController\Models\AdminV2\DeliverHistory;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\AdminV2\FinanceLog;
use App\HttpController\Models\AdminV2\MailReceipt;
use App\HttpController\Models\AdminV2\ToolsUploadQueue;
use App\HttpController\Models\MRXD\OnlineGoodsCommissionGrantDetails;
use App\HttpController\Models\MRXD\OnlineGoodsCommissions;
use App\HttpController\Models\MRXD\OnlineGoodsDaikuanBank;
use App\HttpController\Models\MRXD\OnlineGoodsDaikuanProducts;
use App\HttpController\Models\MRXD\OnlineGoodsTiXianJiLu;
use App\HttpController\Models\MRXD\OnlineGoodsUser;
use App\HttpController\Models\MRXD\OnlineGoodsUserBaoXianOrder;
use App\HttpController\Models\MRXD\OnlineGoodsUserDaikuanOrder;
use App\HttpController\Models\MRXD\OnlineGoodsUserInviteRelation;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Models\RDS3\CompanyInvestor;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\XinDong\XinDongService;

class CustomerController extends ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function getAllCustomers_doc(): bool
    {
        $doc =  [
            '接口说明' => "客户管理-列表",
            '方法名' => "getAllCustomers",
            '域名+路由' => "http://dsjrapi.meirixindong.com/pc/v1/user/getAllCustomers",
            '请求方式' => "POST/GET",
            '请求参数' => [
                '手机号' => [
                    'key' => 'phone',
                    '说明' => '登录成功后会返回',
                ],
                '鉴权TOKEN' => [
                    'key' => 'x-token',
                    '说明' => '登录成功后会返回，传到header里',
                ],
            ],
            '参数返回' => [
                '编号/客户ID' => [
                    'key' => 'id',
                    '说明' => '',
                ],
                '客户编号' => [
                    'key' => 'customer_number',
                    '说明' => '',
                ],
                '客户名称' => [
                    'key' => 'customer_name',
                    '说明' => '',
                ],
                '客户经理' => [
                    'key' => 'sales_info.user_name',
                    '说明' => 'sales_info是数组',
                ],
                '客户来源' => [
                    'key' => 'source_cname',
                    '说明' => '',
                ],
                '客户分类' => [
                    'key' => 'level_cname',
                    '说明' => '',
                ],
                '销售阶段' => [
                    'key' => 'stage_cname',
                    '说明' => '',
                ],
                '联系方式' => [
                    '说明' => '是数组，包含有多个联系人',
                    '联系人姓名' =>  [
                        "key" => "contact_info.name",
                    ],
                    '联系人职位' =>  [
                        "key" => "contact_info.position",
                    ],
                    '联系方式' =>  [
                        "key" => "contact_info.contact",
                    ],
                    '联系类型' =>  [
                        "key" => "contact_info.contact_type",
                    ],
                    '备注' =>  [
                        "key" => "contact_info.remark",
                    ],
                ],
                '所属行业' => [
                    'key' => 'industry_cname',
                    '说明' => '',
                ],
                '所属城市' => [
                    'key' => 'city_cname',
                    '说明' => '',
                ],
                '营收规模' => [
                    'key' => 'ying_shou_cname',
                    '说明' => '',
                ],
                '客户状态/数据状态' => [
                    'key' => 'status_cname',
                    '说明' => '',
                ],
                '创建人信息/录入人信息' => [
                    'key' => 'create_by_info.user_name',
                    '说明' => 'create_by_info是数组',
                ],
                '团队人数' => [
                    'key' => 'team_members_cname',
                    '说明' => '',
                ],
                '融资阶段' => [
                    'key' => 'financing_stage_cname',
                    '说明' => '',
                ],
            ],
            '状态码说明' => 'code为200表示成功 不为200表示异常 错误信息在msg里',
        ];

        return $this->writeJson(200, $doc, '成功',false);
    }

    //客户管理 列表
    function getAllCustomers(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']?:1;
        $pageSize =  $requestData['pageSize']?:100;
        $total = 2;

        $retrunDatas = [
            [
                'id' => 1,
                'customer_number' => 'A1XX0311',
                'customer_name' => '海南松鼠速客科技有限公司',
                'level' => 5,
                'level_cname' => '重点客户',
                'sales_info' => [
                    'id' => 1,
                    'user_name' =>  '王大锤',
                ],
                'contact_info' => [
                    [
                        'id' => 1,
                        'name' =>  '王大锤',
                        'position' =>  '经理',
                    ],
                    [
                        'id' => 2,
                        'name' =>  '张姐姐',
                        'position' =>  '财务',
                    ]
                ],
                'industry_id' =>  1,
                'industry_name' =>  '互联网',
                'stage' =>  5,
                'stage_cname' =>  "需求测试中",
                'status' =>  5,
                'status_cname' =>  '正常',
                'create_by' =>  1,
                'create_by_info' => [
                    'id' => 1,
                    'user_name' =>  '王大锤',
                ],
            ],
            [
                'id' => 2,
                'customer_number' => 'A2YY0312',
                'customer_name' => '河南璐绣工程实业有限公司',
                'level' => 5,
                'level_cname' => '普通客户',
                'sales_info' => [
                    'id' => 1,
                    'user_name' =>  '王大锤',
                ],
                'contact_info' => [
                    [
                        'id' => 1,
                        'name' =>  '王大锤',
                        'position' =>  '经理',
                    ],
                    [
                        'id' => 2,
                        'name' =>  '张姐姐',
                        'position' =>  '财务',
                    ]
                ],
                'industry_info' =>  [
                    [
                        'id' =>  1,
                        'name' =>  '互联网',
                    ],
                    [
                        'id' =>  1,
                        'name' =>  '互联网',
                    ],
                ],
                'stage' =>  5,
                'stage_cname' =>  "需求测试中",
                'status' =>  10,
                'status_cname' =>  '废弃',
                'create_by' =>  1,
                'create_by_info' => [
                    'id' => 1,
                    'user_name' =>  '王大锤',
                ],
            ],
        ];

        return $this->writeJson(
            200,
            [
                'page' => $page,
                'pageSize' =>$pageSize,
                'total' => $total,
                'totalPage' => ceil( $total/ $pageSize ),
            ] ,
            $retrunDatas
            ,
            '成功',
            true,
            []
        );
    }

    function addNewCustomer_doc(): bool
    {
        $doc =  [
            '接口说明' => "客户管理-添加客户",
            '方法名' => "addNewCustomer",
            '域名+路由' => "http://dsjrapi.meirixindong.com/pc/v1/user/addNewCustomer",
            '请求方式' => "POST/GET",
            '请求参数' => [
                '手机号' => [
                    'key' => 'phone',
                    '说明' => '登录成功后会返回',
                ],
                '鉴权TOKEN' => [
                    'key' => 'x-token',
                    '说明' => '登录成功后会返回，传到header里',
                ],
                '客户名称' => [
                    'key' => 'customer_name',
                    '说明' => '',
                ],
                '分配给的客户经理' => [
                    'key' => 'sales_id',
                    '说明' => '分配的时候：下拉选择用户【用户列表数据需要从接口取】，传用户选择的id',
                ],
                '客户分类' => [
                    'key' => 'level',
                    '说明' => '具体哪些分类 从接口取',
                ],
                '销售阶段' => [
                    'key' => 'stage',
                    '说明' => '具体哪些阶段 从接口取',
                ],
                '客户来源' => [
                    'key' => 'source',
                    '说明' => '',
                ],
                '所属行业' => [
                    'key' => 'industry',
                    '说明' => '具体哪些阶段 从接口取',
                ],
                '所属城市' => [
                    'key' => 'city',
                    '说明' => '具体哪些阶段 从接口取',
                ],
                '营收规模' => [
                    'key' => 'ying_shou',
                    '说明' => '',
                ],
                '团队人数' => [
                    'key' => 'team_members',
                    '说明' => '',
                ],
                '融资阶段' => [
                    'key' => 'financing_stage',
                    '说明' => '',
                ],
                '客户状态/数据状态' => [
                    'key' => 'status_cname',
                    '说明' => '',
                ],
            ],
            '参数返回' => [

            ],
            '状态码说明' => 'code为200表示成功 不为200表示异常 错误信息在msg里',
        ];

        return $this->writeJson(200, $doc, '成功');
    }

    //添加新的客户
    function addNewCustomer(): bool
    {
        $requestData =  $this->getRequestData();

        DataModelExample::checkField(
            [

                'name' => [
                    'not_empty' => 1,
                    'field_name' => 'name',
                    'err_msg' => '客户名称不能为空',
                ],
            ],
            $requestData
        );

        return $this->writeJson(
            200,
            [] ,
            []
            ,
            '成功',
            true,
            []
        );
    }

    function addNewCustomerContacts_doc(): bool
    {
        $doc =  [
            '接口说明' => "客户管理-添加客户联系人",
            '方法名' => "addNewCustomerContacts",
            '域名+路由' => "http://dsjrapi.meirixindong.com/pc/v1/user/addNewCustomerContacts",
            '请求方式' => "POST/GET",
            '请求参数' => [
                '手机号' => [
                    'key' => 'phone',
                    '说明' => '登录成功后会返回',
                ],
                '鉴权TOKEN' => [
                    'key' => 'x-token',
                    '说明' => '登录成功后会返回，传到header里',
                ],
                '客户ID' => [
                    'key' => 'customer_id',
                    '说明' => '',
                ],
                '联系人名称' => [
                    'key' => 'name',
                    '说明' => '',
                ],
                '联系人职位' => [
                    'key' => 'position',
                    '说明' => '',
                ],
                '联系方式' => [
                    'key' => 'contact',
                    '说明' => '',
                ],
                '联系类型' => [
                    'key' => 'contact_type',
                    '说明' => '',
                ],
                '联系人备注' => [
                    'key' => 'remark',
                    '说明' => '',
                ],
            ],
            '参数返回' => [

            ],
            '状态码说明' => 'code为200表示成功 不为200表示异常 错误信息在msg里',
        ];

        return $this->writeJson(200, $doc, '成功',false);
    }

    //添加新的客户联系人
    function addNewCustomerContacts(): bool
    {
        $requestData =  $this->getRequestData();

        return $this->writeJson(
            200,
            [] ,
            []
            ,
            '成功',
            true,
            []
        );
    }

    function handOverToNewSales_doc(): bool
    {
        $doc =  [
            '接口说明' => "客户管理-转让/转移",
            '方法名' => "handOverToNewSales",
            '域名+路由' => "http://dsjrapi.meirixindong.com/pc/v1/user/handOverToNewSales",
            '请求方式' => "POST/GET",
            '请求参数' => [
                '手机号' => [
                    'key' => 'phone',
                    '说明' => '登录成功后会返回',
                ],
                '鉴权TOKEN' => [
                    'key' => 'x-token',
                    '说明' => '登录成功后会返回，传到header里',
                ],
                '销售ID' => [
                    'key' => 'sale_id',
                    '说明' => '',
                ],
                '转移日期' => [
                    'key' => 'hand_over_date',
                    '说明' => '',
                ],
            ],
            '参数返回' => [

            ],
            '状态码说明' => 'code为200表示成功 不为200表示异常 错误信息在msg里',
        ];

        return $this->writeJson(200, $doc, '成功',false);
    }

    //转移 TODO 历史记录 查看  牵扯到回款
    function handOverToNewSales(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']?:1;
        $pageSize =  $requestData['pageSize']?:100;
        $total = 2;

        return $this->writeJson(
            200,
            [] ,
            []
            ,
            '成功',
            true,
            []
        );
    }

    function getAllCustomerContacts_doc(): bool
    {
        $doc =  [
            '接口说明' => "客户管理-获取所有联系人",
            '方法名' => "getAllCustomerContacts",
            '域名+路由' => "http://dsjrapi.meirixindong.com/pc/v1/user/getAllCustomerContacts",
            '请求方式' => "POST/GET",
            '请求参数' => [
                '手机号' => [
                    'key' => 'phone',
                    '说明' => '登录成功后会返回',
                ],
                '鉴权TOKEN' => [
                    'key' => 'x-token',
                    '说明' => '登录成功后会返回，传到header里',
                ],
                '客户id' => [
                    'key' => 'customer_id',
                    '说明' => '',
                ],
            ],
            '参数返回' => [

                '联系方式id' => [
                    'key' => 'id',
                    '说明' => '',
                ],

                '联系人姓名' => [
                    'key' => 'name',
                    '说明' => '',
                ],

                '联系人职位' => [
                    'key' => 'position',
                    '说明' => '',
                ],

                '联系方式' => [
                    'key' => 'contact',
                    '说明' => '',
                ],

                '联系类型' => [
                    'key' => 'contact_type',
                    '说明' => '',
                ],

                '备注' => [
                    'key' => 'remark',
                    '说明' => '',
                ],
            ],
            '状态码说明' => 'code为200表示成功 不为200表示异常 错误信息在msg里',
        ];

        return $this->writeJson(200, $doc, '成功',false);
    }

    //获取客户所有的联系人
    function getAllCustomerContacts(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']?:1;
        $pageSize =  $requestData['pageSize']?:100;
        $total = 2;

        return $this->writeJson(
            200,
            [
                'page' => $page,
                'pageSize' =>$pageSize,
                'total' => $total,
                'totalPage' => ceil( $total/ $pageSize ),
            ] ,
            [
                [
                    [
                        'id' => 1,
                        'name' =>  '王大锤',
                        'position' =>  '经理',
                    ],
                    [
                        'id' => 2,
                        'name' =>  '张姐姐',
                        'position' =>  '财务',
                    ]
                ]
            ]
            ,
            '成功',
            true,
            []
        );
    }

    function getAllSales_doc(): bool
    {
        $doc =  [
            '接口说明' => "客户管理-获取所有联系人",
            '方法名' => "getAllCustomerContacts",
            '域名+路由' => "http://dsjrapi.meirixindong.com/pc/v1/user/getAllCustomerContacts",
            '请求方式' => "POST/GET",
            '请求参数' => [
                '手机号' => [
                    'key' => 'phone',
                    '说明' => '登录成功后会返回',
                ],
                '鉴权TOKEN' => [
                    'key' => 'x-token',
                    '说明' => '登录成功后会返回，传到header里',
                ],
                '客户id' => [
                    'key' => 'customer_id',
                    '说明' => '',
                ],
            ],
            '参数返回' => [

                '联系方式id' => [
                    'key' => 'id',
                    '说明' => '',
                ],

                '联系人姓名' => [
                    'key' => 'user_name',
                    '说明' => '',
                ],

            ],
            '状态码说明' => 'code为200表示成功 不为200表示异常 错误信息在msg里',
        ];

        return $this->writeJson(200, $doc, '成功',false);
    }

    //获取所有销售 分配用  TODO 权限问题
    function getAllSales(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']? :1;
        $pageSize =  $requestData['pageSize']? :100;
        $total = 2;

        $retrunDatas = [
            [
                'id' => 1,
                'user_name' =>  '王大锤',
            ],
            [
                'id' => 2,
                'name' =>  '张姐姐',
            ],
        ];

        return $this->writeJson(
            200,
            [
                'page' => $page,
                'pageSize' =>$pageSize,
                'total' => $total,
                'totalPage' => ceil( $total/ $pageSize ),
            ] ,
            $retrunDatas
            ,
            '成功',
            true,
            []
        );
    }


    function getCustomerOptions_doc(): bool
    {
        $doc =  [
            '接口说明' => "客户管理-获取相关选项",
            '方法名' => "getCustomerOptions",
            '域名+路由' => "http://dsjrapi.meirixindong.com/pc/v1/user/getCustomerOptions",
            '请求方式' => "POST/GET",
            '请求参数' => [
                '手机号' => [
                    'key' => 'phone',
                    '说明' => '登录成功后会返回',
                ],
                '鉴权TOKEN' => [
                    'key' => 'x-token',
                    '说明' => '登录成功后会返回，传到header里',
                ],
            ],
            '参数返回' => [

                 "详情见接口"

            ],
            '状态码说明' => 'code为200表示成功 不为200表示异常 错误信息在msg里',
        ];

        return $this->writeJson(200, $doc, '成功',false);
    }

    //获取客户相关的选项
    function getCustomerOptions(): bool
    {
        $requestData =  $this->getRequestData();
        $retrunDatas = [
           'level' => [
               'describe' => '客户分类',
               'options' => [
                   5 => '客户线索',
                   10 => '客户线索',
               ],
           ],
            'source' => [
                'describe' => '客户来源',
                'options' => [
                    5 => '企查查',
                    10 => '自己挖掘',
                ],
            ],
            'stage' => [
                'describe' => '客户跟进阶段',
                'options' => [
                    5 => '待跟进',
                    10 => '跟进中',
                    15 => '洽谈中',
                ],
            ],
            'industry' => [
                'describe' => '所属行业',
                'options' => [
                    5 => '互联网',
                    10 => '物流',
                ],
            ],
            'priority' => [
                'describe' => '优先级',
                'options' => [
                    5 => '一般',
                    10 => '重要',
                    15 => 'VIP',
                ],
            ],
            'financing_stage' => [
                'describe' => '融资阶段',
                'options' => [
                    5 => 'A轮',
                    10 => 'B轮',
                    15 => 'C轮',
                ],
            ],
            'ying_shou' => [
                'describe' => '营收规模',
                'options' => [
                    5 => '10万以下',
                    10 => '10万-50万元',
                    15 => '50万元-100万',
                ],
            ],
            'team_members' => [
                'describe' => '企业人数',
                'options' => [
                    5 => '10人以下',
                    10 => '10人-50人',
                    15 => '50人-100人',
                ],
            ],
            'status' => [
                'describe' => '客户状态',
                'options' => [
                    5 => '启用',
                    10 => '废弃',
                ],
            ],
        ];

        return $this->writeJson(
            200,
            [

            ] ,
            $retrunDatas
            ,
            '成功',
            true,
            []
        );
    }


    function changeCustomerOptions_doc(): bool
    {
        $doc =  [
            '接口说明' => "客户管理-更改相关选项",
            '方法名' => "changeCustomerOptions",
            '域名+路由' => "http://dsjrapi.meirixindong.com/pc/v1/user/changeCustomerOptions",
            '请求方式' => "POST/GET",
            '请求参数' => [
                '手机号' => [
                    'key' => 'phone',
                    '说明' => '登录成功后会返回',
                ],
                '鉴权TOKEN' => [
                    'key' => 'x-token',
                    '说明' => '登录成功后会返回，传到header里',
                ],
                '需要更改的选项' => [
                    'key' => 'change_osption_key',
                    '说明' => '指明具体要更改哪一项',
                    '具体传什么value' => [
                        "客户分类" => '若要改 客户分类 有哪些选项，传 level',
                        "客户来源" => '若要改 客户来源 有哪些选项，传 source',
                        "客户跟进阶段" => '若要改 客户跟进阶段 有哪些选项，传 stage',
                        "所属行业" => '若要改 所属行业 有哪些选项，传 industry',
                        "优先级" => '若要改 优先级 有哪些选项，传 priority',
                        "融资阶段" => '若要改 融资阶段 有哪些选项，传 financing_stage',
                        "营收规模" => '若要改 营收规模 有哪些选项，传 ying_shou',
                        "企业人数" => '若要改 企业人数 有哪些选项，传 team_members',
                        "客户状态" => '若要改 客户状态 有哪些选项，传 status',
                    ],
                ],
                '改成什么' => [
                    'key' => 'change_osption_value',
                    '说明' => '具体改为哪些选项',
                    '具体传什么value' => "用户设定的新的选项集合传到后台：json也行，数组也行，前端自己定",
                ],
            ],
            '参数返回' => [  ],
            '状态码说明' => 'code为200表示成功 不为200表示异常 错误信息在msg里',
        ];

        return $this->writeJson(200, $doc, '成功',false);
    }

    //更改客户相关的选项 TODO 历史记录
    function changeCustomerOptions(): bool
    {
        $requestData =  $this->getRequestData();

        return $this->writeJson(
            200,
            [

            ] ,
            []
            ,
            '成功',
            true,
            []
        );
    }

    //获取所有城市JSON  TODO  具体格式问题 分几级 到哪层
    function getAllCitys(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']? :1;
        $pageSize =  $requestData['pageSize']? :100;
        $total = 2;

        $retrunDatas = [

        ];

        return $this->writeJson(
            200,
            [
                'page' => $page,
                'pageSize' =>$pageSize,
                'total' => $total,
                'totalPage' => ceil( $total/ $pageSize ),
            ] ,
            $retrunDatas
            ,
            '成功',
            true,
            []
        );
    }

    //TODO 操作历史记录的问题


    //新建日程任务
    function addNewTask(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']?:1;
        $pageSize =  $requestData['pageSize']?:100;
        $total = 2;

        return $this->writeJson(
            200,
            [] ,
            []
            ,
            '成功',
            true,
            []
        );
    }

    function addNewTask_doc(): bool
    {
        $doc =  [
            '接口说明' => "客户管理-添加新的任务计划",
            '方法名' => "addNewTask",
            '域名+路由' => "http://dsjrapi.meirixindong.com/pc/v1/user/addNewTask",
            '请求方式' => "POST/GET",
            '请求参数' => [
                '手机号' => [
                    'key' => 'phone',
                    '说明' => '登录成功后会返回',
                ],
                '鉴权TOKEN' => [
                    'key' => 'x-token',
                    '说明' => '登录成功后会返回，传到header里',
                ],
                '客户ID' => [
                    'key' => 'customer_id',
                    '说明' => '',
                ],
                '跟进日期' => [
                    'key' => 'date',
                    '说明' => '',
                ],
                '跟进标题' => [
                    'key' => 'title',
                    '说明' => '',
                ],
                '具体跟进内容' => [
                    'key' => 'content',
                    '说明' => '',
                ],
                '具体跟进类型' => [
                    'key' => 'type',
                    '说明' => '具体哪些类型 需要和产品确认',
                ],
            ],
            '参数返回' => [  ],
            '状态码说明' => 'code为200表示成功 不为200表示异常 错误信息在msg里',
        ];

        return $this->writeJson(200, $doc, '成功',false);
    }


    function changeTask_doc(): bool
    {
        $doc =  [
            '接口说明' => "客户管理-添加新的任务计划",
            '方法名' => "addNewTask",
            '域名+路由' => "http://dsjrapi.meirixindong.com/pc/v1/user/addNewTask",
            '请求方式' => "POST/GET",
            '请求参数' => [
                '手机号' => [
                    'key' => 'phone',
                    '说明' => '登录成功后会返回',
                ],
                '鉴权TOKEN' => [
                    'key' => 'x-token',
                    '说明' => '登录成功后会返回，传到header里',
                ],
                '任务ID' => [
                    'key' => 'id',
                    '说明' => '',
                ],
                '跟进日期' => [
                    'key' => 'date',
                    '说明' => '',
                ],
                '跟进标题' => [
                    'key' => 'title',
                    '说明' => '',
                ],
                '具体跟进内容' => [
                    'key' => 'content',
                    '说明' => '',
                ],
                '具体跟进类型' => [
                    'key' => 'type',
                    '说明' => '具体哪些类型 需要和产品确认',
                ],
            ],
            '参数返回' => [  ],
            '状态码说明' => 'code为200表示成功 不为200表示异常 错误信息在msg里',
        ];

        return $this->writeJson(200, $doc, '成功');
    }

    //更改日程任务
    function changeTask(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']?:1;
        $pageSize =  $requestData['pageSize']?:100;
        $total = 2;

        return $this->writeJson(
            200,
            [] ,
            []
            ,
            '成功',
            true,
            []
        );
    }

    //删除日程任务
    function delTask(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']?:1;
        $pageSize =  $requestData['pageSize']?:100;
        $total = 2;

        return $this->writeJson(
            200,
            [] ,
            []
            ,
            '成功',
            true,
            []
        );
    }

    function delTask_doc(): bool
    {
        $doc =  [
            '接口说明' => "客户管理-添加新的任务计划",
            '方法名' => "delTask_doc",
            '域名+路由' => "http://dsjrapi.meirixindong.com/pc/v1/user/delTask_doc",
            '请求方式' => "POST/GET",
            '请求参数' => [
                '手机号' => [
                    'key' => 'phone',
                    '说明' => '登录成功后会返回',
                ],
                '鉴权TOKEN' => [
                    'key' => 'x-token',
                    '说明' => '登录成功后会返回，传到header里',
                ],
                '任务ID' => [
                    'key' => 'id',
                    '说明' => '',
                ],
            ],
            '参数返回' => [  ],
            '状态码说明' => 'code为200表示成功 不为200表示异常 错误信息在msg里',
        ];

        return $this->writeJson(200, $doc, '成功',false);
    }


    //获取所有日常任务
    function getAllTask(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']? :1;
        $pageSize =  $requestData['pageSize']? :100;
        $total = 2;

        $retrunDatas = [
            [
              'id' => 1,
              'date' => '2023-04-19',
              'title' => '这是标题1',
              'content' => '这是内容1',
              'type' => 5,
              'type_cname' => '线上会议跟进',
            ],
            [
                'id' => 2,
                'date' => '2023-04-19',
                'title' => '这是标题2',
                'content' => '这是内容2',
                'type' => 5,
                'type_cname' => '线下去对方公司跟进',
            ],
        ];

        return $this->writeJson(
            200,
            [
                'page' => $page,
                'pageSize' =>$pageSize,
                'total' => $total,
                'totalPage' => ceil( $total/ $pageSize ),
            ] ,
            $retrunDatas
            ,
            '成功',
            true,
            []
        );
    }

    function getAllTask_doc(): bool
    {
        $doc =  [
            '接口说明' => "客户管理-添加新的任务计划",
            '方法名' => "getAllTask",
            '域名+路由' => "http://dsjrapi.meirixindong.com/pc/v1/user/getAllTask",
            '请求方式' => "POST/GET",
            '请求参数' => [
                '手机号' => [
                    'key' => 'phone',
                    '说明' => '登录成功后会返回',
                ],
                '鉴权TOKEN' => [
                    'key' => 'x-token',
                    '说明' => '登录成功后会返回，传到header里',
                ],
            ],
            '参数返回' => [
                '任务ID' => [
                    'key' => 'id',
                    '说明' => '',
                ],
                '跟进日期' => [
                    'key' => 'date',
                    '说明' => '',
                ],
                '跟进标题' => [
                    'key' => 'title',
                    '说明' => '',
                ],
                '具体跟进内容' => [
                    'key' => 'content',
                    '说明' => '',
                ],
                '具体跟进类型' => [
                    'key' => 'type',
                    '说明' => '具体哪些类型 需要和产品确认',
                ],
            ],
            '状态码说明' => 'code为200表示成功 不为200表示异常 错误信息在msg里',
        ];

        return $this->writeJson(200, $doc, '成功',false);
    }

}