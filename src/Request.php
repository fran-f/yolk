<?php declare(strict_types=1);

namespace Yolk;

use RuntimeException;
use Yolk\Exceptions\MissingParameter;

class Request implements \ArrayAccess
{
    public static function fromGlobals(HttpRequest $http, array $extra = []) : self
    {
        return new self(array_merge(
            $http->get(),
            $http->post(),
            $extra
        ));
    }

    private array $params;

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    public function all() : array
    {
        return $this->params;
    }

    public function optional(...$keys)
    {
        $first = reset($keys);
        if (is_array($first)) {
            // return associative array
            return array_merge(
                array_fill_keys($first, null),
                array_intersect_key($this->params, array_flip($first))
            );
        }

        // return single value
        if (count($keys) == 1) {
            return $this->params[reset($keys)] ?? null;
        }
        // return array
        return array_values(array_merge(
            array_fill_keys($keys, null),
            array_intersect_key($this->params, array_flip($keys))
        ));
    }

    public function required(...$keys)
    {
        $first = reset($keys);
        $returnAssoc = is_array($first);

        $required = is_array($first) ? $first : $keys;
        $params = $this->nonEmptyParams();
        $found = array_intersect_key($params, array_flip($required));
        if (count($found) != count($required)) {
            $missing = array_diff($required, array_keys($params));
            throw new MissingParameter($missing);
        }
        if ($returnAssoc) {
            return $found;
        }
        return count($required) == 1 ? reset($found) : array_values($found);
    }

    public function offsetExists($offset) : bool
    {
        return isset($this->params[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->params[$offset] ?? null;
    }

    public function offsetUnset($offset) : void
    {
        // read only, not implemented
        throw new RuntimeException();
    }

    public function offsetSet($offset, $value) : void
    {
        // read only, not implemented
        throw new RuntimeException();
    }

    private function nonEmptyParams() : array
    {
        return array_filter(
            $this->params,
            fn($value) => is_array($value) || trim($value) !== ""
        );
    }
}
