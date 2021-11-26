<?php
require_once "../vendor/autoload.php";

use Fize\Session\Session;

$str = Session::savePath('../temp');
Session::start();

$_SESSION['admin'] = [
    'name' => '陈峰展',
    'age'  => 30,
    'time' => date('Y-m-d H:i:s')
];

var_dump($str);

$str_now = Session::savePath();
var_dump($str_now);