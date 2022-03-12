<?php declare(strict_types=1);

namespace Yolk\Responses;

use Closure;
use Yolk\Response;

class View extends Response
{
    private string $view;
    private array $context;
    private $renderer;

    public function __construct(string $view, array $context = [])
    {
        parent::__construct();
        $this->view = $view;
        $this->context = $context;
    }

    public function renderWith(Closure $callback) : self
    {
        $this->renderer = $callback;
        return $this;
    }

    protected function renderBody() : string
    {
        return ($this->renderer)($this->view, $this->context);
    }
}
