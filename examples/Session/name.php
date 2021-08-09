<?php
require_once "../../vendor/autoload.php";

use fize\session\Session;

$rst = Session::name('phpsession');
var_dump($rst);

Session::start();

$name = Session::name();
var_dump($name);