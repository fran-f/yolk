<?php declare(strict_types=1);

namespace Yolk\Exceptions;

class MissingParameter extends \Exception
{
    public function __construct(array $missing)
    {
        parent::__construct(
            "These required parameters are missing: " . implode(', ', $missing)
        );
    }
}
