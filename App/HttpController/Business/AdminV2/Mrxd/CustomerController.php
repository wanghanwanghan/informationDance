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

    //添加新的客户
    function addNewCustomer(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']?:1;
        $pageSize =  $requestData['pageSize']?:100;
        $total = 2;

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

    //添加新的客户联系人
    function addNewCustomerContacts(): bool
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

    //获取客户相关的选项
    function getCustomerOptions(): bool
    {
        $requestData =  $this->getRequestData();
        $retrunDatas = [
           'classification' => [
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
            'revenue_scale' => [
                'describe' => '营收规模',
                'options' => [
                    5 => '10万以下',
                    10 => '10万-50万元',
                    15 => '50万元-100万',
                ],
            ],
            'employees_number' => [
                'describe' => '企业人数',
                'options' => [
                    5 => '10人以下',
                    10 => '10人-50人',
                    15 => '50人-100人',
                ],
            ],
            'status' => [
                'describe' => '融资阶段',
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

    //更改客户相关的选项 TODO 历史记录
    function changeCustomerOptions(): bool
    {
        $requestData =  $this->getRequestData();
        $retrunDatas = [
            'classification' => [
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
            'revenue_scale' => [
                'describe' => '营收规模',
                'options' => [
                    5 => '10万以下',
                    10 => '10万-50万元',
                    15 => '50万元-100万',
                ],
            ],
            'employees_number' => [
                'describe' => '企业人数',
                'options' => [
                    5 => '10人以下',
                    10 => '10人-50人',
                    15 => '50人-100人',
                ],
            ],
            'status' => [
                'describe' => '融资阶段',
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

}