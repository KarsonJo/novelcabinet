<?php


require_once('Modules/book-setup.php');
// require_once('Theme/utility.php');
require_once('debug.php');
// require_once('Theme/load-resources.php');

foreach (glob(__DIR__ . '/Theme/*.php') as $file) {
    require_once($file);
}
