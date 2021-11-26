<?php
require_once "../vendor/autoload.php";

use Fize\Session\Session;

Session::start();

$_SESSION['admin'] = [
    'name' => '陈峰展',
    'age'  => 30,
    'time' => date('Y-m-d H:i:s')
];

Session::reset();
var_dump($_SESSION);  // 最后一次保存值