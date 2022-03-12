<?php declare(strict_types=1);

namespace Yolk;

use Exception;
use RuntimeException;

class Dispatcher
{
    private $injector;

    public function __construct(callable $injector = null)
    {
        $this->injector = $injector;
    }

    public function execute($controller, Request $request)
    {
        return $this->closure($controller)($request);
    }

    public function executeError($controller, Exception $ex = null) : Response
    {
        return $this->closure($controller)($ex);
    }

    private function closure($controller) : callable
    {
        if (is_callable($controller)) {
            return $controller;
        }

        $instance = is_callable($this->injector)
            ? ($this->injector)($controller)
            : new $controller;

        if (!is_object($instance)) {
            throw new RuntimeException("Invalid controller class {$controller}");
        }
        if (!method_exists($controller, '__invoke')) {
            throw new RuntimeException("Controller class {$controller} lacks the __invoke() method");
        }

        return $instance;
    }
}
