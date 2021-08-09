<?php
require_once "../../vendor/autoload.php";

use fize\session\Session;

Session::cacheExpire(30);
$cache_expire = Session::cacheExpire();

Session::start();

echo "The cached session pages expire after $cache_expire minutes";