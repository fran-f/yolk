<?php

namespace Yolk;

interface HttpRequest
{
    public function method() : ?string;
    public function body() : string;
    public function get() : array;
    public function post() : array;
}
