<?php
require_once "../vendor/autoload.php";

use fize\session\Session;

Session::start();

$id = Session::id();
var_dump($id);