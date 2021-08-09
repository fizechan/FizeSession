<?php
require_once "../../../vendor/autoload.php";

use fize\session\handler\File;
use fize\session\Session;

$handler = new File();
Session::setSaveHandler($handler);
Session::savePath(__DIR__ . '/../../temp');

Session::start();

Session::writeClose();

echo '关闭 session';