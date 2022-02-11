<?php

//#分销商可销售产品接口地址
$wsdl = 'http://oa.ssap.com.cn/sys/webservice/sysSynchroGetOrgWebService?wsdl';

//实例化对象
$client = new SoapClient($wsdl);

//接口参数。
$param1 = [
    'returnOrgType' => '',
    'returnType' => '',
];

//接口方法。
$ret1 = $client->getElementsBaseInfo($param1);

var_dump($ret1);exit;


//将XML数据转换成数组
$array = (array)$ret1;

//转换成simplexml_load_string对象
$v = simplexml_load_string($array['return']);

//数组定义
$Varr = $v->ybproducts->fzhproducts->product;

//获取到具体的值
for ($i = 0; $i < count($Varr); $i++) {
    echo $Varr[$i]->prod_id;
    echo $Varr[$i]->product_name;
    echo $Varr[$i]->prod_code;
    echo $Varr[$i]->prod_category;
    echo $Varr[$i]->supply_id;
    echo $Varr[$i]->price;
    echo $Varr[$i]->parprice;
    echo $Varr[$i]->total_ticket_num;
    echo $Varr[$i]->inventory;
    echo $Varr[$i]->product_name;
    echo $Varr[$i]->product_name;
    echo '<br/>';
}


//获取接口所有方法及参数
// print_r($client->__getfunctions());
// print_r($client->__getTypes());

