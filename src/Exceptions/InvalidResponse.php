<?php declare(strict_types=1);

namespace Yolk\Exceptions;

class InvalidResponse extends \Exception
{
    public function __construct($value, string $role = 'Controller')
    {
        $type = gettype($value);
        $dump = var_export($value, true);
        parent::__construct("{$role} returned invalid response of type {$type}: {$dump}");
    }
}
