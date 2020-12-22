<?php

include './vendor/autoload.php';



$arr = [
    '北京' => [
        'total' => 1,
        'num' => 2,
    ],
    '上海' => [
        'total' => 3,
        'num' => 4,
    ],
    '广州' => [
        'total' => 5,
        'num' => 6,
    ],
];




dd(\wanghanwanghan\someUtils\control::sortArrByKey($arr,'total'));





