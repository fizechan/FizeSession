<?php
require_once "../../vendor/autoload.php";

use fize\session\Session;

$rst = Session::moduleName('redis');
var_dump($rst);

Session::savePath('192.168.56.101:6379');
Session::start();

$name = Session::moduleName();
var_dump($name);