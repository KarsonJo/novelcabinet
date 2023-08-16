<?php

use KarsonJo\Utilities\Route\RESTAPISupport;
use NovelCabinet\Services\Route\APIRoute;
use NovelCabinet\Services\Route\ThemePageRoute;

require_once('debug.php');
require_once('Services/book-setup.php');
// require_once('Theme/utility.php');
// require_once('Theme/load-resources.php');

foreach (glob(__DIR__ . '/Theme/*.php') as $file) {
    require_once($file);
}

foreach (glob(__DIR__ . '/Services/User/*.php') as $file) {
    require_once($file);
}

require_once('BookPost/bootloader.php');
ThemePageRoute::init();
APIRoute::init();
RESTAPISupport::addJavascriptSupport();