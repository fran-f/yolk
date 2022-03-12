<?php declare(strict_types=1);

namespace Yolk;

use RuntimeException;

beforeEach(function() {
    $this->d = new Dispatcher;
});

class MockController
{
    public static $construct = false;
    public static ?Request $request = null;

    public function __construct($value = true)
    {
        self::$construct = $value;
    }

    public function __invoke(Request $request)
    {
        self::$request = $request;
        return 200;
    }
}

# --- execute ---------------------------------------------
it("instantiate and call the given controller", function() {
    $this->d->execute(MockController::class, new Request);

    expect(MockController::$request)->toBeTruthy();
});

it("returns the response of the controller", function() {
    $result = $this->d->execute(MockController::class, new Request);

    expect($result)->toBe(200);
});

it("throws if the controller cannot be invoked", function() {
    expect(fn() => $this->d->execute(\DateTime::class, new Request))
        ->toThrow(RuntimeException::class);
});

it("calls the controller with the given request", function() {
    $r = new Request;
    $this->d->execute(MockController::class, $r);

    expect(MockController::$request)->toBe($r);
});

it("uses a callback to instantiate controllers", function() {
    $d = new Dispatcher(fn($class) => new $class('yes'));
    $d->execute(MockController::class, new Request);

    expect(MockController::$construct)->toEqual('yes');
});

it("throws if the callback does not instantiate an object", function() {
    $d = new Dispatcher(fn($class) => [ 'an' => 'array' ]);

    expect(fn() => $d->execute(MockController::class, new Request))
        ->toThrow(RuntimeException::class);
});

it("supports closures as controllers", function() {
    $controller = fn($request) => "response";
    $response = $this->d->execute($controller, new Request);

    expect($response)->toEqual("response");
});

