<?php
require_once "../../../vendor/autoload.php";

use Fize\Session\Handler\DatabaseHandler;
use Fize\Session\Session;

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
$handler = new DatabaseHandler($config);
Session::setSaveHandler($handler);

Session::start();

Session::writeClose();

echo '关闭 session';