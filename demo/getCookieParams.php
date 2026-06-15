<?php
require_once "../vendor/autoload.php";

use Fize\Session\Session;

Session::start();

$info = Session::getCookieParams();
var_dump($info);