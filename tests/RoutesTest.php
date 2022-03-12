<?php declare(strict_types=1);

namespace Yolk;

beforeEach(function() {
    $this->router = new Routes;
});

# --- resolve ---------------------------------------------
it("throws an exception for missing routes", function() {
    expect(fn() => $this->router->resolve('GET', '/not-there'))
        ->toThrow(Exceptions\RouteNotFound::class);
});

it("throws an exception for missing actions", function() {
    $this->router->get('/overview', TestClass::class);

    expect(fn() => $this->router->resolve('POST', '/overview'))
        ->toThrow(Exceptions\MethodNotAllowed::class);
});

it("supports GET requests", function() {
    $this->router->get('/overview', TestClass::class);

    $route = $this->router->resolve('GET', '/overview');

    expect($route->controller)->toEqual(TestClass::class);
    expect($route->parameters)->toEqual([]);
});

it("supports POST requests", function() {
    $this->router->post('/overview', TestClass::class);

    $route = $this->router->resolve('POST', '/overview');

    expect($route->controller)->toEqual(TestClass::class);
    expect($route->parameters)->toEqual([]);
});

it("supports path parameters", function() {
    $this->router->get('/articles/:year/:month/comments', TestClass::class);

    $route = $this->router->resolve('GET', '/articles/2022/01/comments');

    expect($route->controller)->toEqual(TestClass::class);
    expect($route->parameters)->toEqual([ 'year' => '2022', 'month' => '01' ]);
});

it("accepts routes with valid parameters", function() {
    $this->router->patterns([ 'year' => '20\d\d' ]);
    $this->router->get('/articles/:year', TestClass::class);

    $route = $this->router->resolve('GET', '/articles/2022');

    expect($route->controller)->toEqual(TestClass::class);
    expect($route->parameters)->toEqual([ 'year' => '2022' ]);
});

it("refuses routes with invalid parameters", function() {
    $this->router->patterns([ 'year' => '19\d\d' ]);
    $this->router->get('/articles/:year', TestClass::class);

    expect(fn() => $this->router->resolve('GET', '/articles/2022'))
        ->toThrow(Exceptions\RouteNotFound::class);
});

it("supports closures as controllers", function() {
    $this->router->get('/help', fn($request) => "response");

    $route = $this->router->resolve('GET', '/help');
    expect($route->controller)->toBeInstanceOf(\Closure::class);
});


# --- path ------------------------------------------------
it("generates paths from named routes", function() {
    $this->router->get('/overview', TestClass::class, 'home');

    expect($this->router->path('home'))
        ->toEqual('/overview');
});

it("throws an exception for missing named routes", function() {
    expect(fn() => $this->router->path('missing'))
        ->toThrow(\InvalidArgumentException::class);
});

it("fills in single parameters in named routes", function() {
    $this->router->get('/articles/:year', TestClass::class, 'year');

    expect($this->router->path('year', '2022'))
        ->toEqual('/articles/2022');
});

it("fills in keyed parameters in named routes", function() {
    $this->router->get('/articles/:year', TestClass::class, 'year');
    $params = [ 'year' => '2022' ];

    expect($this->router->path('year', $params))
        ->toEqual('/articles/2022');
});

it("fills in multiple parameters in named routes", function() {
    $this->router->get('/articles/:year/:month', TestClass::class, 'year');
    $params = [ 'year' => '2022', 'month' => '01' ];

    expect($this->router->path('year', $params))
        ->toEqual('/articles/2022/01');
});

it("throws an exception if named routes parameters are missing", function() {
    $this->router->get('/articles/:year/:month', TestClass::class, 'year');
    $params = [ 'month' => '01' ];

    expect(fn() => $this->router->path('year', $params))
        ->toThrow(\InvalidArgumentException::class);
});

it("throws an exception with one parameter is not enough", function() {
    $this->router->get('/articles/:year/:month', TestClass::class, 'year');

    expect(fn() => $this->router->path('year', '2022'))
        ->toThrow(\InvalidArgumentException::class);
});

# --- resolveError ----------------------------------------
it("returns null if the given error does not have a handler", function() {
    expect($this->router->resolveError(429))->toBeNull();
});

it("returns the configured handler for the given error", function() {
    $this->router->error(418, fn() => "error 418");
    expect($this->router->resolveError(418))->toBeInstanceOf(\Closure::class);
});
