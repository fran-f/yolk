<?php declare(strict_types=1);

namespace Yolk;

class MockHttp extends ApacheHttpRequest
{
    private string $method;
    private string $path;

    public function __construct(string $method, string $path)
    {
        $this->method = $method;
        $this->path = $path;
    }

    public function method() : ?string
    {
        return $this->method;
    }

    public function get() : array
    {
        return [ 'path' => $this->path ];
    }

    public function post() : array
    {
        return [];
    }

    public function body() : string
    {
        return "";
    }
}

class OneTwoThreeController
{
    public function __invoke($req) : array
    {
        return [ 'x' => '123' ];
    }
}

function subject($controller) {
    $app = new App('https://example.com/');
    $app->routes()
        ->get('/overview', $controller, 'overview')
        ->error(418, fn() => (new Response(418))->body("I'm a teapot."));
    return $app;
}

beforeEach(function() {
    $this->http = new MockHttp('GET', '/overview');
});


# --- run -------------------------------------------------
it("returns a Response if the controller produces one", function() {
    $app = subject(fn() => new Response(200));
    $response = $app->run($this->http);
    expect($response->dump()->status)->toEqual(200);
});

# -- if the controller returns a Responses\View
it("renders the view with the given callback", function() {
    $app = subject(fn() => new Responses\View('home'));
    $app->renderViewsWith(fn($view, $context) => "view is {$view}");
    $response = $app->run($this->http);
    expect($response->dump()->body)->toEqual("view is home");
});

# -- if the controller returns a status code
it("calls the matching error route", function() {
    $app = subject(fn() => 429);
    $response = $app->run($this->http);
    expect($response->dump()->status)->toEqual(429);
});

it("returns a custom error Response if defined", function() {
    $app = subject(fn() => 418);
    $response = $app->run($this->http);
    expect($response->dump()->status)->toEqual(418);
    expect($response->dump()->body)->toContain('teapot');
});

# -- if the controller returns an array
it("returns an OK response with the default view", function() {
    $app = subject(fn() => [ 'key' => 'value' ]);
    $app->renderViewsWith(fn($view, $data) => var_export($data, true));
    $response = $app->run($this->http);
    expect($response->dump()->status)->toEqual(200);
    expect($response->dump()->body)->toContain('value');
});

it("uses the controller array to render the view", function() {
    $payload = [ 'x' => '99' ];
    $app = subject(fn() => $payload);
    $app->renderViewsWith(fn($view, $data) => "<p>{$data['x']}</p>");
    $response = $app->run($this->http);
    expect($response->dump()->body)->toContain("99");
});

it("passes the controller name to the view renderer", function() {
    $payload = [ 'x' => '99' ];
    $app = subject(OneTwoThreeController::class);
    $app->renderViewsWith(fn($view, $data) => "<p>{$view}</p>");
    $response = $app->run($this->http);
    expect($response->dump()->body)->toContain("OneTwoThreeController");
});

it("passes 'closure' to the view renderer, if the controller is not a class", function() {
    $app = subject(fn() => [ 'x' => '99' ]);
    $app->renderViewsWith(fn($view, $data) => "<p>{$view}</p>");
    $response = $app->run($this->http);
    expect($response->dump()->body)->toContain("closure");
});

# -- if the controller returns a string
it("returns a 302 Response", function() {
    $app = subject(fn() => "/overview");
    $response = $app->run($this->http);
    expect($response)->toBeInstanceOf(Response::class);
    expect($response->dump()->status)->toEqual(302);
});

it("returns a redirect to the given absolute URL", function() {
    $url = 'https://www.php.net/';
    $app = subject(fn() => $url);
    $response = $app->run($this->http);
    expect($response->dump()->status)->toEqual(302);
    expect($response->dump()->headers)->toHaveKey('Location', $url);
});

it("returns a redirect converting the given relative URL to absolute", function() {
    $app = subject(fn() => ':overview');
    $response = $app->run($this->http);
    expect($response->dump()->status)->toEqual(302);
    expect($response->dump()->headers)->toHaveKey('Location', 'https://example.com/overview');
});

it("returns a redirect converting the given route name to a URL", function() {
    $app = subject(fn() => '/other');
    $response = $app->run($this->http);
    expect($response->dump()->status)->toEqual(302);
    expect($response->dump()->headers)->toHaveKey('Location', 'https://example.com/other');
});

# -- if the controllers returns an invalid response
it("returns a 500 Response", function() {
    $app = subject(fn() => true);
    $response = $app->run($this->http);
    expect($response->dump()->status)->toEqual(500);
});

it("logs an InvalidResponse exception", function() {
    $wasCalled = false;
    $app = subject(fn() => true);
    $app->logExceptionsWith(function($ex) use (&$wasCalled) {
        $wasCalled = $ex instanceof Exceptions\InvalidResponse;
    });

    $app->run($this->http);
    expect($wasCalled)->toBeTrue();
});

# -- if the route does not exist
it("returns a 404 Response", function() {
    $http = new MockHttp('GET', '/other');
    $app = subject(fn() => 200);
    $response = $app->run($http);
    expect($response->dump()->status)->toEqual(404);
});

# -- if the route does not support the given method
it("returns a 405 Response", function() {
    $http = new MockHttp('POST', '/overview');
    $app = subject(fn() => 200);
    $response = $app->run($http);
    expect($response->dump()->status)->toEqual(405);
});


# -- if the request is missing a parameter
it("returns a 400 Response", function() {
    $app = subject(fn($req) => $req->required('missing'));
    $response = $app->run($this->http);
    expect($response->dump()->status)->toEqual(400);
});


# -- if the any other exception is raised
it("returns a 500 Response (2)", function() {
    $app = subject(function() {
        throw new \RuntimeException;
    });
    $response = $app->run($this->http);
    expect($response->dump()->status)->toEqual(500);
});

it("logs the exception if a logger is configured", function() {
    $wasCalled = false;
    $app = subject(function() {
        throw new \RuntimeException;
    });
    $app->logExceptionsWith(function($ex) use (&$wasCalled) {
        $wasCalled = true;
    });

    $app->run($this->http);
    expect($wasCalled)->toBeTrue();
});


# --- routes ------------------------------------------
it("uses the given Routes", function() {
    $app = subject(fn($req) => 200);
    $app->routes((new Routes)->get('/overview', fn() => new Response(418)));

    $response = $app->run($this->http);
    expect($response->dump()->status)->toEqual(418);
});

it("creates a new Routes, when called without parameters", function() {
    $app = subject(fn() => []);
    expect($app->routes())->toBeInstanceOf(Routes::class);
});

it("returns an existing Routes, when called without parameters and one is set", function() {
    $app = subject(fn() => []);
    $routes = $app->routes();
    expect($routes)->toBe($app->routes());
});


# --- middleware ------------------------------------------
it("uses the given Middleware", function() {
    $app = subject(fn($req) => 200);
    $app->middleware((new Middleware)->all(fn() => new Response(418)));

    $response = $app->run($this->http);
    expect($response->dump()->status)->toEqual(418);
});

it("creates a new Middleware, when called without parameters", function() {
    $app = subject(fn() => []);
    expect($app->middleware())->toBeInstanceOf(Middleware::class);
});

it("returns an existing Middleware, when called without parameters and one is set", function() {
    $app = subject(fn() => []);
    $middleware = $app->middleware();
    expect($middleware)->toBe($app->middleware());
});

it("processes the request via middleware", function() {
    $middleware = (new Middleware)
        ->except('/overview', fn() => new Request([ 'status' => 400 ]))
        ->only('/overview', fn() => new Request([ 'status' => 300 ]));
    $app = subject(fn($req) => $req['status']);
    $app->middleware($middleware);

    $response = $app->run($this->http);
    expect($response->dump()->status)->toEqual(300);
});

it("returns the response of matching middleware", function() {
    $middleware = (new Middleware)
        ->except('/overview', fn() => new Response(400))
        ->only('/overview', fn() => new Response(300));
    $app = subject(fn() => 200);
    $app->middleware($middleware);

    $response = $app->run($this->http);
    expect($response->dump()->status)->toEqual(300);
});


# --- path ------------------------------------------------
it("returns the relative URL for a route", function() {
    $app = subject(fn() => 200);
    $app->routes()
        ->patterns([ 'year' => '20\d\d' ])
        ->get('/archives/:year', fn() => 200, 'yearly_archives');

    expect($app->path('yearly_archives', '2022'))->toEqual('/archives/2022');
});


# --- url -------------------------------------------------
it("returns the absolute URL for a route", function() {
    $app = subject(fn() => 200);
    $app->routes()
        ->patterns([ 'year' => '20\d\d' ])
        ->get('/archives/:year', fn() => 200, 'yearly_archives');

    expect($app->url('yearly_archives', '2022'))
        ->toEqual('https://example.com/archives/2022');
});
