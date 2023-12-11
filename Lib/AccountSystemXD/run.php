<?php

include './src/Suning/SuningBank.php';

var_dump(dirname(__DIR__));exit;


$obj = (new AccountSystemXD\Suning\SuningBank())
    ->setAppCli(getcwd().'/File/cli_pri.pem')
    ->setAppSer(getcwd().'/File/server_pub.pem')
    ->setBaseUrl('https://fsoftssit.suningbank.com:2443/fsoftssit1/')
    ->setSslCert(getcwd().'/File/cert.pem')
    ->setSslPrivate(getcwd().'/File/private.pem')
    ->setSslPwd('wanghan123')
    ->setParams();





