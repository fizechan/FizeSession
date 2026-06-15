<?php
require_once "../vendor/autoload.php";

use Fize\Session\Session;

Session::start();

$_SESSION['admin'] = [
    'name' => '陈峰展',
    'age'  => 30,
    'time' => date('Y-m-d H:i:s')
];

echo '可以输出';
var_dump($_SESSION);

Session::writeClose();  //主动触发