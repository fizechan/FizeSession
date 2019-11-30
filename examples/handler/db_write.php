<?php
require_once "../../vendor/autoload.php";

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

$_SESSION['admin'] = [
    'name' => '中华人民共和国',
    'age'  => 30,
    'time' => date('Y-m-d H:i:s')
];

echo '写入 session';