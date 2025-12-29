<?php

namespace Myhealth\Classes;

use Myhealth\Core\Logger;

class Route
{
    private static $instance;

    private $currentRoute;

    private function __construct()
    {
        if (!isset($_ENV['ROUTEDEBUG'])) {
            $_ENV['ROUTEDEBUG'] = false;
        }
    }
    
    private static function getInstance()
    {
        if (isset(self::$instance)) {
            return self::$instance;
        }

        self::$instance = new Route();
        return self::$instance;
    }

    public static function get(array|string $name, mixed $route)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return;
        }

        $inst = self::getInstance();

        if (!$inst->matchName($name)) {
            return;
        }

        $inst->callRoute($name, $route);
    }

    public static function post(array|string $name, mixed $route)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $inst = self::getInstance();
        if (!$inst->matchName($name)) {
            return;
        }

        $inst->callRoute($name, $route);
    }

    public static function any(string $name, mixed $route)
    {
        self::get($name, $route);
        self::post($name, $route);
    }
    
    public static function getCurrentRoute()
    {
        $inst = self::getInstance();
        return $inst->currentRoute ?? '';
    }
    
    public static function getRequestedRoute()
    {
        $inst = self::getInstance();
        $routeName = str_replace($_SERVER['SCRIPT_NAME'].'/', '', $_SERVER['PHP_SELF']);
        return $routeName;
    }

    public static function debugEnable()
    {
        $_ENV['ROUTEDEBUG'] = 'true';
    }

    public static function debugDisable()
    {
        $_ENV['ROUTEDEBUG'] = 'false';
    }

    private function matchName(array|string $name)
    {
        $inst = self::getInstance();
        if (is_array($name)) {
            foreach($name as $n) {
                if ($inst->matchName($n)) {
                    return true;
                }
            }

            return false;
        }

        $routeName = str_replace($_SERVER['SCRIPT_NAME'].'/', '', $_SERVER['PHP_SELF']);
        if ($_ENV['ROUTEDEBUG'] === 'true') {
            Logger::debug("Looking for {$name}");
            Logger::debug("SCRIPT_NAME: {$_SERVER['SCRIPT_NAME']}");
            Logger::debug("PHP_SELF: {$_SERVER['PHP_SELF']}");
        }
        
        if ($routeName === $name ||
            "/{$routeName}" === $name ||
            str_starts_with($routeName, $name.'/') ||
            str_starts_with("/{$routeName}/", $name.'/')) {
            $inst->currentRoute = $name;

            if ($_ENV['ROUTEDEBUG'] === 'true') {
                Logger::debug("Matched route: {$name}");
            }
            
            return true;
        }

        return false;
    }

    private function callRoute(array|string $name, mixed $route)
    {
        if (is_callable($route)) {
            $route();
            exit;
        }

        if (is_array($route)) {
            $controller = $route[0];
            $method = $route[1];
            (new $controller())->$method();
            exit;
        }
    }
}