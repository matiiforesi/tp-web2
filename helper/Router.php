<?php

class Router
{
    private $config;
    private $defaultController;
    private $defaultMethod;

    public function __construct($config, $defaultController, $defaultMethod)
    {
        $this->config = $config;
        $this->defaultController = $defaultController;
        $this->defaultMethod = $defaultMethod;
    }

    public function dispatch($controllerName, $method)
    {
        $controller = $this->getController($controllerName);
        $method = $this->getMethod($controller, $method);
        $controller->{$method}();
    }

    private function getController($controllerName)
    {
        $factoryMethod = $this->getFactoryMethodName($controllerName);

        if (!method_exists($this->config, $factoryMethod)) {
            $factoryMethod = $this->getFactoryMethodName($this->defaultController);
        }

        return $this->config->{$factoryMethod}();
    }

    private function getFactoryMethodName($controllerName)
    {
        return "get" . ucfirst($controllerName) . "Controller";
    }

    private function getMethod($controller, $method)
    {
        // is_callable (y no method_exists) para que los metodos privados y los magicos no se puedan invocar desde la URL.
        $esRuta = is_string($method) && strpos($method, '__') !== 0 && is_callable([$controller, $method]);
        return $esRuta ? $method : $this->defaultMethod;
    }
}
