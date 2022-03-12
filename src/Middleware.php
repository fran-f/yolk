<?php declare(strict_types=1);

namespace Yolk;

class Middleware
{
    private array $handlers = [];

    const ALL = 'all';
    const ONLY = 'only';
    const EXCEPT = 'except';

    public function handlers() : array
    {
        return $this->handlers;
    }

    public function all(/* string | callable */ $handler) : self
    {
        $this->handlers[] = (object) [
            'handler' => $handler,
            'type' => self::ALL,
        ];
        return $this;
    }

    public function only(string $pattern, /* string | callable */ $handler) : self
    {
        $this->handlers[] = (object) [
            'handler' => $handler,
            'type' => self::ONLY,
            'pattern' => $pattern,
        ];
        return $this;
    }

    public function except(string $pattern, /* string | callable */ $handler) : self
    {
        $this->handlers[] = (object) [
            'handler' => $handler,
            'type' => self::EXCEPT,
            'pattern' => $pattern,
        ];
        return $this;
    }

    public function process(Dispatcher $dispatcher, string $path, Request $request)
    {
        $progress = $request;
        foreach ($this->handlers as $handler) {
            $result = $this->execute($dispatcher, $path, $handler, $progress);
            if ($result == null) {
                continue;
            }
            if ($result instanceof Request) {
                $progress = $result;
                continue;
            }
            if ($result instanceof Response) {
                return $result;
            }
            throw new Exceptions\InvalidResponse($result, "Middleware");
        }
        return $progress;
    }

    private function execute(Dispatcher $dispatcher, string $path, $handler, Request $request)
    {
        if ($handler->type == self::ALL) {
            return $dispatcher->execute($handler->handler, $request);
        }

        $isMatch = $this->match(trim($path, '/'), trim($handler->pattern, '/'));
        $isOnly = $handler->type == self::ONLY;
        $isExcept = $handler->type == self::EXCEPT;
        $doProcess = $isMatch && $isOnly || !$isMatch && $isExcept;

        return $doProcess
            ? $dispatcher->execute($handler->handler, $request)
            : $request;
    }

    private function match(string $path, string $pattern) : bool
    {
        $regex = "#^{$pattern}$#i";
        return preg_match($regex, $path) === 1;
    }
}
