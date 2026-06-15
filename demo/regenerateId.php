<?php
require_once "../vendor/autoload.php";

use Fize\Session\Session;

Session::start();
$id1 = Session::id();
$rst = Session::regenerateId();
$id2 = Session::id();
var_dump($id1);
var_dump($rst);
var_dump($id2);