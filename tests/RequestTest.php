<?php declare(strict_types=1);

namespace Yolk;

use RuntimeException;

class SampleHttp implements HttpRequest
{
    public function method() : ?string
    {
        return 'GET';
    }

    public function body() : string
    {
        return "";
    }

    public function get() : array
    {
        return [ 'a' => 'GET', 'get' => 'yes' ];
    }

    public function post() : array
    {
        return [ 'a' => 'POST', 'post' => 'yes' ];
    }
}

# --- implements ArrayAccess ------------------------------

it("works as an array for reading parameters", function() {
    $request = new Request([ 'a' => '1', 'b' => '2' ]);

    expect($request['a'])->toEqual('1');
    expect($request['b'])->toEqual('2');
    expect($request['c'])->toBeNull();
});

it("works as an array for checking parameters", function() {
    $request = new Request([ 'a' => '1', 'b' => '2' ]);

    expect(isset($request['a']))->toBeTrue();
    expect(isset($request['b']))->toBeTrue();
    expect(isset($request['c']))->toBeFalse();
});

it("cannot be written to", function() {
    $request = new Request;

    expect(function() use ($request) {
        $request['a'] = 'value';
    })->toThrow(RuntimeException::class);

    expect(function() use ($request) {
        unset($request['a']);
    })->toThrow(RuntimeException::class);
});

# --- all -------------------------------------------------

it("returns all parameters", function() {
    $params = [ 'a' => '1', 'b' => '2' ];
    $request = new Request($params);

    expect($request->all())->toEqual($params);
});

# --- optional --------------------------------------------

it("returns a single optional parameter", function() {
    $params = [ 'a' => '1', 'b' => '2' ];
    $request = new Request($params);

    expect($request->optional('a'))->toEqual('1');
});

it("returns null for a single missing optional parameter", function() {
    $params = [ 'a' => '1', 'b' => '2' ];
    $request = new Request($params);

    expect($request->optional('c'))->toBeNull();
});

it("returns only the requested optional parameters", function() {
    $params = [ 'a' => '1', 'b' => '2', 'c' => '3' ];
    $request = new Request($params);

    expect($request->optional('b', 'c'))->toEqual([ '2', '3' ]);
});

it("returns null for missing optional parameters", function() {
    $params = [ 'a' => '1', 'b' => '2' ];
    $request = new Request($params);

    expect($request->optional('a', 'c'))->toEqual([ '1', null ]);
});

it("return an associative array if the keys are an array", function() {
    $params = [ 'a' => '1', 'b' => '2', 'c' => '3' ];
    $request = new Request($params);

    expect($request->optional([ 'b', 'c' ]))
        ->toEqual([ 'b' => '2', 'c' => '3' ]);
});

# --- required --------------------------------------------

it("returns a single required parameter", function() {
    $params = [ 'a' => '1', 'b' => '2' ];
    $request = new Request($params);

    expect($request->required('a'))->toEqual('1');
});

it("returns multiple required parameters", function() {
    $params = [ 'a' => '1', 'b' => '2' ];
    $request = new Request($params);

    expect($request->required('a', 'b'))->toEqual([ '1', '2' ]);
});

it("return an associative array if the required keys are an array", function() {
    $params = [ 'a' => '1', 'b' => '2', 'c' => '3' ];
    $request = new Request($params);

    expect($request->required([ 'b', 'c' ]))
        ->toEqual([ 'b' => '2', 'c' => '3' ]);
});

it("throws an exception if a required parameter is missing", function() {
    $params = [ 'a' => '1', 'b' => '2' ];
    $request = new Request($params);

    expect(fn() => $request->required('c'))
        ->toThrow(Exceptions\MissingParameter::class);
});

# --- fromGlobals -----------------------------------------

it("includes GET parameters", function() {
    $request = Request::fromGlobals(new SampleHttp);

    expect($request['get'])->toBeTruthy();
});

it("includes POST parameters", function() {
    $request = Request::fromGlobals(new SampleHttp);

    expect($request['post'])->toBeTruthy();
});

it("includes custom parameters", function() {
    $request = Request::fromGlobals(new SampleHttp, [ 'custom' => 'yes' ]);

    expect($request['custom'])->toBeTruthy();
});

it("gives priority to custom parameters over body parameters", function() {
    $request = Request::fromGlobals(new SampleHttp, [ 'a' => 'custom' ]);

    expect($request['a'])->toEqual('custom');
});

it("gives priority to body parameters over GET parameters", function() {
    $request = Request::fromGlobals(new SampleHttp);

    expect($request['a'])->toEqual('POST');
});
