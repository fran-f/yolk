<?php

namespace Yolk;

class ApacheHttpRequest implements HttpRequest
{
    public function method() : ?string
    {
        return $_SERVER['REQUEST_METHOD'] ?? null;
    }

    public function body() : string
    {
        return file_get_contents('php://input') ?: '';
    }

    public function get() : array
    {
        return $_GET;
    }

    public function post() : array
    {
        return $_POST;
    }
}
