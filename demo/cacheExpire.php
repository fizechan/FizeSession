<?php
require_once "../vendor/autoload.php";

use Fize\Session\Session;

Session::cacheExpire(30);
$cache_expire = Session::cacheExpire();

Session::start();

echo "The cached session pages expire after $cache_expire minutes";