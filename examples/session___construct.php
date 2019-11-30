<?php
require_once "../vendor/autoload.php";

use fize\session\Session;

$config = [
    'save_handler'      => [
        'type'              => 'Database',
        'config'            => [
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
        ],
        'register_shutdown' => true
    ]
];
new Session($config);

$_SESSION['admin'] = [
    'name' => '中华人民共和国2',
    'age'  => 30,
    'time' => date('Y-m-d H:i:s')
];

echo '写入 session';