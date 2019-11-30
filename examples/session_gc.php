<?php
require_once "../vendor/autoload.php";

use fize\session\Session;

Session::start();

$num = Session::gc();
var_dump($num);