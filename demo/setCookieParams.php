<?php
require_once "../vendor/autoload.php";

use Fize\Session\Session;

$rst1 = Session::setCookieParams(10);
$rst2 = Session::setCookieParams(10, '/');
$rst3 = Session::setCookieParams(10, '/', 'localhost');
$rst4 = Session::setCookieParams(10, '/', 'localhost', true);
$rst5 = Session::setCookieParams(10, '/', 'localhost', true, true);
Session::start();

var_dump($rst1);
var_dump($rst2);
var_dump($rst3);
var_dump($rst4);
var_dump($rst5);