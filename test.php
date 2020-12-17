<?php

include './vendor/autoload.php';

$data = [
    '2018' => ['xx'=>1],
    '2019' => ['xx'=>2],
    '2020' => ['xx'=>3],
];

for ($i = 0; $i < 3; $i++) {
    $j = $i;
    foreach ($data as $key => $val) {
        if ($j !== 0) {
            $j--;
            continue;
        }

        echo $data[$key]['xx'].PHP_EOL;
        break;
    }
}






