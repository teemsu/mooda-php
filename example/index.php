<?php

define('DIRECTORY_ROOT', __DIR__);
define('CONTROLLERS_DIRECTORY', (DIRECTORY_ROOT . '/application/controllers/'));
define('MODELS_DIRECTORY', (DIRECTORY_ROOT . '/application/models/'));
define('VIEWS_DIRECTORY', (DIRECTORY_ROOT . '/application/views/'));
define('SERVICES_DIRECTORY', (DIRECTORY_ROOT . '/application/services/'));
define('DEVELOPMENT_MODE', TRUE);

require (DIRECTORY_ROOT . '/../src/autoload.php');

define('ROUTE_URI', (empty($_SERVER['PATH_INFO']) ? 'Home/index' : $_SERVER['PATH_INFO']));

$router = new \Mooda\Systems\Router();
$router->Go(ROUTE_URI);
