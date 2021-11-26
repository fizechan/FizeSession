<?php
require_once "../../../vendor/autoload.php";

use Fize\Session\Handler\File;
use Fize\Session\Session;

$handler = new File();
Session::setSaveHandler($handler);
Session::savePath(__DIR__ . '/../../temp');

Session::start();

var_dump($_SESSION);

echo '读取 session';