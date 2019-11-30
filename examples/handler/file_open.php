<?php
require_once "../../vendor/autoload.php";

use fize\session\Session;

$class = '\\fize\\session\\handler\\File';
$handler = new $class();
Session::setSaveHandler($handler);
Session::savePath( __DIR__ . '/../../temp');

Session::start();

echo '打开 session';