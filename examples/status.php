<?php
require_once "../vendor/autoload.php";

use Fize\Session\Session;

unset($_SESSION);

$status1 = Session::status();

Session::start();

$status2 = Session::status();

$_SESSION['admin'] = [
    'name' => '陈峰展',
    'age'  => 30,
    'time' => date('Y-m-d H:i:s')
];

$status3 = Session::status();

var_dump($status1);
var_dump($status2);
var_dump($status3);