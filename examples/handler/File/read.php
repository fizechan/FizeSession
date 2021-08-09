<?php
require_once "../../../vendor/autoload.php";

use fize\session\handler\File;
use fize\session\Session;

$handler = new File();
Session::setSaveHandler($handler);
Session::savePath(__DIR__ . '/../../temp');

Session::start();

var_dump($_SESSION);

echo '读取 session';