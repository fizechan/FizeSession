<?php
require_once "../../vendor/autoload.php";

use fize\session\Session;

Session::start();

$_SESSION['admin'] = [
    'name' => '陈峰展',
    'age'  => 30
];

$data = Session::encode();

Session::destroy();
//unset($_SESSION);
//重新启动会话

Session::start();

$rst = Session::decode($data);
var_dump($rst);
var_dump($_SESSION);
