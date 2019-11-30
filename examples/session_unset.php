<?php
require_once "../vendor/autoload.php";

use fize\session\Session;

Session::start();

$_SESSION['admin'] = [
    'name' => '陈峰展',
    'age'  => 30,
    'time' => date('Y-m-d H:i:s')
];

var_dump($_SESSION);

Session::unset();

var_dump($_SESSION);