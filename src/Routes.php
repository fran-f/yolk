<?php declare(strict_types=1);

namespace Yolk;

use InvalidArgumentException;
use Yolk\Exceptions\RouteNotFound;
use Yolk\Exceptions\MethodNotAllowed;

class Routes
{
    private array $errors = [];
    private array $names = [];
    private array $routes = [];
    private array $patterns = [];

    public function get(string $path, $controller, ?string $name = null) : self
    {
        $this->add('GET', $path, $controller, $name);
        return $this;
    }

    public function post(string $path, $controller, ?string $name = null) : self
    {
        $this->add('POST', $path, $controller, $name);
        return $this;
    }

    public function add(string $method, string $path, $controller, ?string $name = null) : void
    {
        $path = '/' . trim($path, '/');
        if ($name) {
            $this->names[$name] = $path;
        }
        if (!isset($this->routes[$path])) {
            $this->routes[$path] = [];
        }
        $this->routes[$path][$method] = $controller;
    }

    public function patterns(array $patterns) : self
    {
        $this->patterns = array_merge($this->patterns, $patterns);
        return $this;
    }

    public function error(int $status, $controller) : self
    {
        $this->errors[$status] = $controller;
        return $this;
    }

    public function resolve(string $method, string $path) : object
    {
        list($actions, $parameters) = $this->matchRoute($path);
        if (!$actions) {
            throw new RouteNotFound;
        }
        if (!isset($actions[$method])) {
            throw new MethodNotAllowed;
        }

        return (object) [
            'controller' => $actions[$method],
            'parameters' => $parameters,
        ];
    }

    public function resolveError(int $status)
    {
        return $this->errors[$status] ?? null;
    }

    public function path(string $name, $params = []) : string
    {
        if (!isset($this->names[$name])) {
            throw new InvalidArgumentException("Unknown route name '{$name}'");
        }
        $path = $this->names[$name];
        $result = is_array($params)
            ? $this->replaceNamedParameters($path, $params)
            : $this->replaceSingleParameter($path, $params);

        $isIncomplete = strpos($result, ':') !== false;
        if ($isIncomplete) {
            $count = is_array($params) ? count($params) : 1;
            throw new InvalidArgumentException(
                "Not enough parameters to generate path from {$path}, {$count} given."
            );
        }
        return $result;
    }

    private function replaceNamedParameters(string $path, array $params) : string
    {
        $result = $path;
        foreach ($params as $key => $value) {
            $result = str_replace(":{$key}", urlencode((string) $value), $result);
        }
        return $result;
    }

    private function replaceSingleParameter(string $path, string $param) : string
    {
        return preg_replace('#:[^/]+#', urlencode($param), $path, 1);
    }

    private function matchRoute(string $path) : array
    {
        $path = '/' . trim($path, '/');
        foreach ($this->routes as $route => $actions) {
            if ($route == $path) {
                return [ $actions, [] ];
            }
            $regex = $this->convertToRegex($route);
            if (preg_match($regex, $path, $matches)) {
                return [ $actions, $this->getNamedMatches($matches) ];
            }
        }
        return [ [], [] ];
    }

    private function convertToRegex(string $route) : string
    {
        $patterns = $this->patterns;
        $pattern = preg_replace_callback(
            '#:(\w+)#',
            function ($m) use ($patterns) {
                return isset($patterns[$m[1]])
                    ? "(?P<{$m[1]}>{$patterns[$m[1]]})"
                    : "(?P<{$m[1]}>[^/]+)";
            },
            $route
        );

        if (!is_string($pattern)) {
            throw new InvalidArgumentException(
                "The route pattern '$route' cannot be parsed"
            );
        }

        return '#^' . $pattern . '$#u';
    }

    private function getNamedMatches(array $matches) : array
    {
        $numericKeys = array_filter(array_keys($matches), 'is_numeric');
        return array_diff_key($matches, array_flip($numericKeys));
    }
}
