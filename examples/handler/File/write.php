<?php
require_once "../../../vendor/autoload.php";

use fize\session\handler\File;
use fize\session\Session;

$handler = new File();
Session::setSaveHandler($handler);
Session::savePath(__DIR__ . '/../../temp');

Session::start();

$_SESSION['admin'] = [
    'name' => '中华人民共和国',
    'age'  => 30,
    'time' => date('Y-m-d H:i:s')
];

echo '写入 session';