<?php

use app\Configuration;

session_start();
// require_once("app/Configuration.php");
require_once __DIR__ . "/app/Configuration.php";

$configuration = new Configuration();
$router = $configuration->getRouter();

$router->dispatch(
    Request::get("controller", "grupo"),
    Request::get("method", "show")
);
