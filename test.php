<?php

use wanghanwanghan\someUtils\control;

include './vendor/autoload.php';

$return=[
    2019=>[123],
    2018=>[456],
    2020=>[789],
];

krsort($return);

$return=array_slice($return,0,2,true);

var_dump($return);

