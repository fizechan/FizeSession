<?php
require_once "../vendor/autoload.php";

use fize\session\Session;

$id = Session::createId();
var_dump($id);

$id = Session::createId('pres-');
var_dump($id);