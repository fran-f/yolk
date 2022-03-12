<?php declare(strict_types=1);

namespace Yolk;

# --- output ----------------------------------------------
# Can't test: generates output
# it outputs the given http status
# it outputs the given headers
# it outputs the given body content

# --- dump ---------------------------------------------
it('returns the given status', function() {
    $status = 403;
    $response = new Response($status);
    expect($response->dump()->status)->toEqual($status);
});

it('returns the given headers', function() {
    $headers = [ 'X-One' => 1, 'X-Two' => 2 ];
    $response = (new Response)->headers($headers);
    expect($response->dump()->headers)->toEqualCanonicalizing($headers);
});

it('returns the given body string', function() {
    $body = 'Hello there!';
    $response = (new Response)->body($body);
    expect($response->dump()->body)->toEqual($body);
});

it('returns the result of the given body callable', function() {
    $body = 'Hello there!';
    $response = (new Response)->body(fn() => $body);
    expect($response->dump()->body)->toEqual($body);
});


# --- header ----------------------------------------------
it('sets the value of a header', function() {
    $name = 'Content-Type';
    $value = 'application/json';
    $response = (new Response)->header($name, $value);
    expect($response->dump()->headers)->toHaveKey($name, $value);
});

it('overrides a previously set header', function() {
    $name = 'Content-Type';
    $value = 'application/json';
    $response = (new Response)->header($name, 'OLD')->header($name, $value);
    expect($response->dump()->headers)->toHaveKey($name, $value);
});


# --- headers ---------------------------------------------
it('sets all passed headers', function() {
    $headers = [ 'X-One' => '1', 'X-Two' => '2' ];
    $response = (new Response)->headers($headers);
    expect($response->dump()->headers)->toEqualCanonicalizing($headers);
});

it('overrides previously set headers', function() {
    $headers = [ 'X-One' => '1', 'X-Two' => '2' ];
    $response = (new Response)->headers([ 'X-One' => '9' ])->headers($headers);
    expect($response->dump()->headers)->toEqualCanonicalizing($headers);
});

it('leaves other headers in place', function() {
    $headers = [ 'X-One' => '1', 'X-Two' => '2' ];
    $response = (new Response)->header('X-Three', '3')->headers($headers);
    expect($response->dump()->headers)->toHaveKey('X-Three', '3');
});


# --- body ------------------------------------------------
it('sets the body contents of the response', function() {
    $body = 'Hello there!';
    $response = (new Response)->body($body);
    expect($response->dump()->body)->toEqual($body);
});

# --- contentType -----------------------------------------
it("sets the Content-Type header to the given value", function() {
    $response = new Response;
    $response->contentType('application/json');
    expect($response->dump()->headers)
        ->toHaveKey('Content-Type', 'application/json');
});

# --- static json -----------------------------------------
it("returns a response with the JSON content type", function() {
    $response = Response::json([ 'x' => 3 ]);
    expect($response->dump()->headers)
        ->toHaveKey('Content-Type', 'application/json');
});

it("returns a response with the payload encoded as json", function() {
    $response = Response::json([ 'x' => 3 ]);
    expect($response->dump()->body)
        ->toEqual('{"x":3}');
});

# --- static view -----------------------------------------
it("returns an instance of Responses\View", function() {
    $response = Response::view('home', [ 'x' => 3 ]);
    expect($response)->toBeInstanceOf(Responses\View::class);
});

it("passes the view to the View instance", function() {
    $callback = fn($view, $context) => "view is {$view}";
    $response = Response::view('home', [ 'x' => 3 ]);
    $response->renderWith($callback);

    expect($response->dump()->body)->toEqual('view is home');
});

it("passed the context to the View instance", function() {
    $callback = fn($view, $context) => "x is {$context['x']}";
    $response = Response::view('home', [ 'x' => 3 ]);
    $response->renderWith($callback);

    expect($response->dump()->body)->toEqual('x is 3');
});

