<?php
require_once "../../../vendor/autoload.php";

use Fize\Session\Handler\File;
use Fize\Session\Session;

$handler = new File();
Session::setSaveHandler($handler);
Session::savePath(__DIR__ . '/../../temp');
Session::cacheExpire(1);
Session::setCookieParams(60);

Session::start(['gc_maxlifetime' => 60]);
Session::gc();

echo '垃圾回收 session';