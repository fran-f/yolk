<?php declare(strict_types=1);

namespace Yolk;

use Closure;
use Exception;
use Yolk\Exceptions\InvalidResponse;
use Yolk\Exceptions\MethodNotAllowed;
use Yolk\Exceptions\MissingParameter;
use Yolk\Exceptions\RouteNotFound;

class App
{
    private ?Closure $injector = null;
    private ?Closure $logger;
    private ?Closure $renderer;
    private Middleware $middleware;
    private Routes $routes;
    private string $baseUrl;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/') . '/';
    }

    public function middleware(Middleware $middleware = null)
    {
        if ($middleware) {
            $this->middleware = $middleware;
            return;
        }
        if (isset($this->middleware)) {
            return $this->middleware;
        }
        $this->middleware = new Middleware;
        return $this->middleware;
    }

    public function routes(Routes $routes = null)
    {
        if ($routes) {
            $this->routes = $routes;
            return;
        }
        if (isset($this->routes)) {
            return $this->routes;
        }
        $this->routes = new Routes;
        return $this->routes;
    }

    public function injectWith(callable $injector) : self
    {
        $this->injector = $injector;
        return $this;
    }

    public function renderViewsWith(callable $renderer) : self
    {
        $this->renderer = $renderer;
        return $this;
    }

    public function logExceptionsWith(callable $logger) : self
    {
        $this->logger = $logger;
        return $this;
    }

    public function run(HttpRequest $http = null) : Response
    {
        $http = $http ?: new ApacheHttpRequest;
        $method = strtoupper($http->method());
        $path = $http->get()['path'] ?? '/';
        try {
            return $this->resolveAndCall($method, $path, $http);
        } catch (MissingParameter $ex) {
            return $this->callError(400, $ex);
        } catch (RouteNotFound $ex) {
            return $this->callError(404, $ex);
        } catch (MethodNotAllowed $ex) {
            return $this->callError(405, $ex);
        } catch (Exception $ex) {
            $this->logException($ex);
            return $this->callError(500, $ex);
        }
    }

    public function path(string $name, ...$params) : string
    {
        return $this->routes->path($name, ...$params);
    }

    public function url(string $name, ...$params) : string
    {
        return $this->baseUrl . ltrim($this->routes->path($name, ...$params), '/');
    }

    private function resolveAndCall(string $method, string $path, HttpRequest $http) : Response
    {
        $route = $this->routes->resolve($method, $path);
        $request = Request::fromGlobals($http, $route->parameters);

        if (isset($this->middleware)) {
            $partial = $this->middleware->process($this->dispatcher(), $path, $request);
            if ($partial instanceof Response) {
                return $partial;
            }
            $request = $partial;
        }

        $result = $this->dispatcher()->execute(
            $route->controller,
            $request
        );
        return $this->convertToResponse($result, $route->controller);
    }

    private function convertToResponse($result, $controller) : Response
    {
        if ($result instanceof Responses\View) {
            $result->renderWith($this->renderer);
            return $result;
        }
        if ($result instanceof Response) {
            return $result;
        }
        if (is_string($result)) {
            $url = $this->convertToRedirect($result);
            return (new Response(302))->header('Location', $url);
        }
        if (is_int($result)) {
            return $this->callError($result);
        }
        if (is_array($result)) {
            $view = $controller instanceof Closure? 'closure' : $controller;
            $renderer = $this->renderer;
            return (new Response)->body(fn() => $renderer($view, $result));
        }

        throw new InvalidResponse($result);
    }

    private function convertToRedirect(string $value) : string
    {
        $first = $value[0];
        if ($first == ':') {
            $path = trim($this->routes->path(substr($value, 1)), '/');
            return $this->baseUrl . $path;
        }
        if ($first == '/') {
            $path = trim($value, '/');
            return $this->baseUrl . $path;
        }
        return $value;
    }

    private function callError(int $status, Exception $ex = null) : Response
    {
        $errorController = $this->routes->resolveError($status);
        if ($errorController) {
            return $this->dispatcher()->executeError($errorController, $ex);
        }

        return (new Response($status))
            ->body("<h1>Error {$status}</h1><p>No handler defined.</p>");
    }

    private function logException(Exception $ex) : void
    {
        if (!isset($this->logger) || !is_callable($this->logger)) {
            return;
        }
        ($this->logger)($ex);
    }

    private function dispatcher() : Dispatcher
    {
        return new Dispatcher($this->injector);
    }
}
