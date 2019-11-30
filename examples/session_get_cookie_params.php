<?php
require_once "../vendor/autoload.php";

use fize\session\Session;

Session::start();

$info = Session::getCookieParams();
var_dump($info);