<?php
require_once "../../../vendor/autoload.php";

use fize\session\handler\File;
use fize\session\Session;

$class = '\\fize\\session\\handler\\File';
$handler = new File();
Session::setSaveHandler($handler);
Session::savePath(__DIR__ . '/../../temp');

Session::start();

echo '打开 session';