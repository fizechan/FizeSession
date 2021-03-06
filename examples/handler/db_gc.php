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
Session::cacheExpire(1);
Session::setCookieParams(60);

Session::start(['gc_maxlifetime' => 60]);
Session::gc();

echo '垃圾回收 session';