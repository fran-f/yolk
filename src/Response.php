<?php declare(strict_types=1);

namespace Yolk;

class Response
{
    private int $status;
    private array $headers = [];
    private $body = "";

    public static function view(string $view, array $context = []) : self
    {
        return new Responses\View($view, $context);
    }

    public static function json($payload) : self
    {
        return (new Response)
            ->contentType('application/json')
            ->body(json_encode($payload));
    }

    public function __construct(int $status = 200)
    {
        $this->status = $status;
    }

    public function body(/* string | callable */ $body) : self
    {
        $this->body = $body;
        return $this;
    }

    public function contentType(string $value) : self
    {
        $this->headers['Content-Type'] = $value;
        return $this;
    }

    public function header(string $name, string $value) : self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function headers(array $headers) : self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function dump() : object
    {
        return (object) [
            'status' => $this->status,
            'headers' => $this->headers,
            'body' => $this->renderBody(),
        ];
    }

    public function output() : void
    {
        $headers = array_filter($this->headers);
        header("{$_SERVER['SERVER_PROTOCOL']} {$this->status}");
        array_walk(
            $headers,
            fn($value, $name) => header("{$name}: {$value}")
        );
        echo $this->renderBody();
    }

    protected function renderBody() : string
    {
        return is_callable($this->body) ? ($this->body)() : (string) $this->body;
    }
}
