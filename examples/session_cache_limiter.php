<?php
require_once "../vendor/autoload.php";

use fize\session\Session;

Session::cacheLimiter('private');
$cache_limiter = Session::cacheLimiter();

echo "The cache limiter is now set to $cache_limiter<br />";