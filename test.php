<?php

use pepper\Helper;
use pepper\SnowFlake;

ini_set('display_errors','on');
error_reporting(E_ALL);

require_once 'vendor/autoload.php';

$helper = new Helper();
var_dump($helper->uniqueString());
var_dump($helper->isValidIP('127.0.0.1'));
var_dump($helper->ip2bin('127.0.0.1'));

echo '<hr/>';

try {
    $sf = new SnowFlake(5, 5);
    echo $sf->getId();
} catch (Exception $e) {
}