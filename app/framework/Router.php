<?php

namespace App\framework;

use ReflectionMethod;

class Router {
    private static $router;
    private array $routes;

    public $controllersPath = '../app/controllers';

    private function __construct() {
    }

    public static function getRouter(): self {
        if (!isset(self::$router)) {
            self::$router = new self();
        }
        return self::$router;
    }

    public function run() {
        try {
            //загрузка по умолчанию
            $url = parse_url($_SERVER['REQUEST_URI']);
            $path = $url['path'];
            $method = $_SERVER['REQUEST_METHOD'];
            if ($routeArr = $this->findRoute($path, $method)) {
                $route = $routeArr['route'];
                $params = $routeArr['params'];
            }
            [$controllerClass, $methodName] = $this->extractAction($route);
            $this->callAction($controllerClass, $methodName, $params ?? []);

        } catch (\Throwable $e) {
            var_dump($e->getMessage());
        }
    }

    protected function register(string $uri, string $action, string $method = 'any'): void {
        if (!isset($this->routes[$method])) $this->routes[$method] = [];
        $this->routes[$method][$uri] = $action;
    }

    public function loadRoutes($routes) {
        foreach ($routes as $uri => $route) {
            $method = 'any';
            $parts = explode(' ', $uri);
            if (count($parts) > 1) {
                $method = $parts[0];
                $uri = $parts[1];
            }
            $this->register($uri, $route, $method);
        }
    }


    public function findRoute($string, $method) {
        if ($routeArr = $this->findRouteByMethod($string, $method)) {
            return $routeArr;
        }
        if ($routeArr = $this->findRouteByMethod($string)) {
            return $routeArr;
        }
        if ($routeArr = $this->findRouteAuto($string)) {
            return $routeArr;
        }
        return false;
    }

    public function findRouteByMethod($string, $method = 'any') {
        $params = [];
        

        foreach ($this->routes[$method] as $uri => $route) {
            if (str_contains($uri, '{')) {
                $regexp = preg_match_all('/{:(.+?)}/', $uri, $matches);
                $regexpUri = $uri;
                foreach ($matches[1] as $param) {
                    $regexpUri = str_replace('{:' . $param . '}', '(.+?)', $regexpUri);
                }
                $regexp = '/^' . str_replace('/', '\/', $regexpUri) . '$/';
                $final = preg_match_all($regexp, $string, $matchesValues);
                if ($final) {
                    foreach ($matches[1] as $k => $var) {
                        $params[$var] = $matchesValues[$k + 1][0];
                    }
                    return compact('route', 'params');
                }

            }
            if ($uri === $string) {
                return compact('route', 'params');
            }
        };
        return false;
    }

    public function findRouteAuto($string) {
        return ['route' => $string, 'params' => []];
    }

    public function callAction($controllerClass, $methodName, $rawParams = []) {
        //посмотрим какие параметры нужны
        $controllerClass = 'App\\controllers\\' . str_replace('/', '\\', $controllerClass);

        try {
            $reflectionMethod = new ReflectionMethod($controllerClass, $methodName);
        } catch (\Throwable $e) {
            throw new \Exception('not found', 404);
        }

        $parameters = $reflectionMethod->getParameters();
        $readyParams = [];
        foreach ($parameters as $parameter) {
            $parameterName = $parameter->getName();
            $parameterValue = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
            if (!$rawParams[$parameterName]) {
                //пробуем получить параметр из GET
                if ($_GET[$parameterName] ?? null) {
                    $parameterValue = $_GET[$parameterName] ?? null;
                }
            } else {
                $parameterValue = $rawParams[$parameterName];
            }

            if ($parameterValue === null) {
                throw new \Exception('Parameter ' . $parameterName . ' not found', 500);
            }
            $readyParams[$parameterName] = $parameterValue;
        }
        $controller = new $controllerClass();
        call_user_func_array([$controller, $methodName], $readyParams);
    }

    //получить список контроллеров
    public function controllersList() {
        function glob_recursive($dir, $pattern) {
            $files = glob($dir . '/' . $pattern);
            foreach (glob($dir . '/*', GLOB_ONLYDIR) as $subdir) {
                $files = array_merge($files, glob_recursive($subdir, $pattern));
            }
            return $files;
        }

        $files = glob_recursive($this->controllersPath, '*Controller.php');
        $list = [];
        foreach ($files as $file) {
            $list[] = strtolower(str_replace([$this->controllersPath . '/', 'Controller.php'], '', $file));
        }
        return $list;
    }

    public function modulesList() {
        $directories = array_filter(scandir($this->controllersPath), function ($item) {
            return is_dir($this->controllersPath . '/' . $item) && !in_array($item, array('.', '..'));
        });
        return $directories;
    }

    public function checkRouteExists($route) {

    }

    public function extractAction($string) {
        $parts = array_values(array_filter((explode('/', $string))));
        if (count($parts) == 0) {
            $controllerName = 'SiteController';
            $actionName = 'actionIndex';
        }
        if (count($parts) == 1) {
            if (in_array($parts[0], $this->modulesList())) {
                $controllerName = $parts[0] . '/SiteController';
                $actionName = 'actionIndex';
            }
            if (in_array($parts[0], $this->controllersList())) {
                $controllerName = ucfirst($parts[0]) . 'Controller';
                $actionName = 'actionIndex';
            }
        }
        if (count($parts) == 2) {
            if (in_array($parts[0], $this->modulesList())) {
                $controllerName = ucfirst($parts[0]) . '/SiteController';
                $actionName = 'action' . ucfirst($parts[1]);
            }
            if (in_array($parts[0], $this->controllersList())) {
                $controllerName = ucfirst($parts[0]) . 'Controller';
                $actionName = 'action' . ucfirst($parts[1]);
            }
        }
        if (count($parts) == 3) {
            $controllerName = $parts[0] . '/' . $parts[1] . 'Controller';
            $actionName = 'action' . ucfirst($parts[2]);
        }
        return [$controllerName, $actionName];
    }

}