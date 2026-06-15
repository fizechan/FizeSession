<?php
require_once "../vendor/autoload.php";

use Fize\Session\Session;

$id = Session::createId();
var_dump($id);

$id = Session::createId('pres-');
var_dump($id);