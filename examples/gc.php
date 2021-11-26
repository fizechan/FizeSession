<?php
require_once "../vendor/autoload.php";

use Fize\Session\Session;

Session::start();

$num = Session::gc();
var_dump($num);