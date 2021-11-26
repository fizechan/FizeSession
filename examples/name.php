<?php
require_once "../vendor/autoload.php";

use Fize\Session\Session;

$rst = Session::name('phpsession');
var_dump($rst);

Session::start();

$name = Session::name();
var_dump($name);