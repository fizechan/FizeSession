<?php
require_once "../../../vendor/autoload.php";

use fize\session\handler\Database;
use fize\session\Session;

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
$handler = new Database($config);
Session::setSaveHandler($handler);

Session::start();

var_dump($_SESSION);

echo '读取 session';