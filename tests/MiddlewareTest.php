<?php declare(strict_types=1);

namespace Yolk;

# Describe Middleware

class MockMiddleware
{
    public function __invoke(Request $request)
    {
        return new Request([ 'processed' => true ]);
    }
}

beforeEach(function() {
    $this->middleware = new Middleware;
    $this->request = new Request([ 'a' => '1', 'b' => '2' ]);
});

# --- all -------------------------------------------------
it("queues a middleware with type 'all'", function() {
    $this->middleware->all(fn($r) => $r);

    $h = $this->middleware->handlers()[0];
    expect($h->handler)->toBeCallable();
    expect($h->type)->toEqual(Middleware::ALL);
});


# --- only ------------------------------------------------
it("queues a middleware with type 'only'", function() {
    $this->middleware->only('login', fn($r) => $r);

    $h = $this->middleware->handlers()[0];
    expect($h->handler)->toBeCallable();
    expect($h->type)->toEqual(Middleware::ONLY);
});


# --- except ----------------------------------------------
it("queues a middleware with type 'except'", function() {
    $this->middleware->except('login', fn($r) => $r);

    $h = $this->middleware->handlers()[0];
    expect($h->handler)->toBeCallable();
    expect($h->type)->toEqual(Middleware::EXCEPT);
});


# --- process ---------------------------------------------
it("calls middleware classes", function() {
    $this->middleware->all(MockMiddleware::class);
    $result = $this->middleware->process(new Dispatcher, '/', $this->request);
    expect($result['processed'])->toBeTrue();
});

it("calls middleware closures", function() {
    $this->middleware->all(fn() => new Request([ 'processed' => true ]));
    $result = $this->middleware->process(new Dispatcher, '/', $this->request);
    expect($result['processed'])->toBeTrue();
});

it("calls middleware in order of definition", function() {
    $this->middleware
         ->all(fn() => new Request([ 'processed' => '1' ]))
         ->all(fn() => new Request([ 'processed' => '2' ]));
    $result = $this->middleware->process(new Dispatcher, '/', $this->request);
    expect($result['processed'])->toEqual(2);
});

it("does not call 'only' middleware for requests that don't match", function() {
    $this->middleware->only('/login', fn() => new Request([ 'processed' => true ]));
    $result = $this->middleware->process(new Dispatcher, '/', $this->request);
    expect($result['processed'])->toBeNull();
});

it("calls 'only' middleware for requests that match", function() {
    $this->middleware->only('/login', fn() => new Request([ 'processed' => true ]));
    $result = $this->middleware->process(new Dispatcher, '/login', $this->request);
    expect($result['processed'])->toBeTrue();
});

it("calls 'only' middleware for the root path, if it matches", function() {
    $this->middleware->only('/', fn() => new Request([ 'processed' => true ]));
    $result = $this->middleware->process(new Dispatcher, '/', $this->request);
    expect($result['processed'])->toBeTrue();
});

it("does not call 'except' middleware for requests that match", function() {
    $this->middleware->except('/login', fn() => new Request([ 'processed' => true ]));
    $result = $this->middleware->process(new Dispatcher, '/login', $this->request);
    expect($result['processed'])->toBeNull();
});

it("calls 'except' middleware for requests that don't match", function() {
    $this->middleware->except('/login', fn() => new Request([ 'processed' => true ]));
    $result = $this->middleware->process(new Dispatcher, '/', $this->request);
    expect($result['processed'])->toBeTrue();
});

it("does not calls 'except' middleware for the root path, if it matches", function() {
    $this->middleware->except('/', fn() => new Request([ 'processed' => true ]));
    $result = $this->middleware->process(new Dispatcher, '/', $this->request);
    expect($result['processed'])->toBeNull();
});

it("calls 'all' middleware for all requests", function() {
    $this->middleware->all(fn() => new Request([ 'processed' => true ]));
    $result = $this->middleware->process(new Dispatcher, '/login', $this->request);
    expect($result['processed'])->toBeTrue();
});

it("passes the result request to the next middleware", function() {
    $this->middleware->all(fn() => new Request([ 'processed' => true ]));
    $this->middleware->all(fn($r) => $r);
    $result = $this->middleware->process(new Dispatcher, '/', $this->request);
    expect($result['processed'])->toBeTrue();
});

it("returns the result response", function() {
    $this->middleware->all(fn() => new Response);
    $this->middleware->all(fn() => new Request([ 'processed' => true ]));
    $result = $this->middleware->process(new Dispatcher, '/', $this->request);
    expect($result)->toBeInstanceOf(Response::class);
});

it("throws InvalidResponse if a middleware response is invalid", function() {
    $this->middleware->all(fn() => "invalid");
    expect(fn() => $this->middleware->process(new Dispatcher, '/', $this->request))
        ->toThrow(Exceptions\InvalidResponse::class);
});
