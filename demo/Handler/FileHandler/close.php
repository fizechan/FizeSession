<?php
require_once "../../../vendor/autoload.php";

use Fize\Session\Handler\FileHandler;
use Fize\Session\Session;

$handler = new FileHandler();
Session::setSaveHandler($handler);
Session::savePath(__DIR__ . '/../../temp');

Session::start();

Session::writeClose();

echo '关闭 session';