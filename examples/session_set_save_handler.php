<?php
require_once "../vendor/autoload.php";

use fize\session\Session;

$class = '\\fize\\session\\handler\\Database';
$config = [
    'db' => [
        'type' => 'mysql',
        'config' => [
            'host'     => 'localhost',
            'user'     => 'root',
            'password' => '123456',
            'dbname'   => 'gm_test'
        ]
    ],
    'table' => 'sys_session'
];
$handler = new $class($config);
Session::setSaveHandler($handler);

Session::start();

var_dump($_SESSION);

$_SESSION['admin'] = [
    'name' => '陈峰展',
    'age'  => 30,
    'time' => date('Y-m-d H:i:s')
];

var_dump($_SESSION);

Session::writeClose();