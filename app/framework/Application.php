<?php
namespace App\framework;

class Application {
    public $container;
    public $name = 'Framework';
    public $routes = [];

    public function __construct() {
        El::$app = $this;
        $this->routes = include ('../config/routes.php');
    }

    public function process() {
        $router = Router::getRouter();
        $router->loadRoutes($this->routes);
        $router->run();
    }
}