<?php
require_once "../vendor/autoload.php";

use Fize\Session\Session;

Session::start();

$id = Session::id();
var_dump($id);