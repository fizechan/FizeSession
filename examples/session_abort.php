<?php
require_once "../vendor/autoload.php";

use fize\session\Session;

Session::start();
if(!isset($_SESSION['count'])){
    $_SESSION['count'] = 1;
}else{
    $_SESSION['count']++;
}
Session::abort();
echo $_SESSION['count'];