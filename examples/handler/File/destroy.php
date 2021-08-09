<?php
require_once "../../../vendor/autoload.php";

use fize\session\handler\File;
use fize\session\Session;

$handler = new File();
Session::setSaveHandler($handler);
Session::savePath(__DIR__ . '/../../temp');

Session::start();

Session::destroy();

echo '删除 session';