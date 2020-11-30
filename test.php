<?php

use wanghanwanghan\someUtils\control;

include './vendor/autoload.php';


$str = "11111111111-666666-" . time();


var_dump(control::aesEncode($str, 'PHP_is_the_best_language_in_the_world'));

