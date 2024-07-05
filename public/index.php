<?php
spl_autoload_register(function ($class_name) {
    $class_name = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
    $path = realpath(__DIR__.'/../'.$class_name.'.php');
    include $path;
});
require '../vendor/autoload.php';
$app = new \App\framework\Application();
$app->process();