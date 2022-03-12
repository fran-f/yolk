<?php declare(strict_types=1);

namespace Yolk\Responses;

# --- output ----------------------------------------------
it("calls the given callback to render the body", function() {
    $called = false;
    $callback = function() use (&$called) { $called = true; return ""; };
    $response = new View('home', [ 'x' => 3 ]);
    $response->renderWith($callback);

    $response->dump();

    expect($called)->toBeTrue();
});

it("renders the given view", function() {
    $callback = fn($view, $context) => "view is {$view}";
    $response = new View('home', [ 'x' => 3 ]);
    $response->renderWith($callback);

    expect($response->dump()->body)->toEqual('view is home');
});

it("renders the view with the given context", function() {
    $callback = fn($view, $context) => "x is {$context['x']}";
    $response = new View('home', [ 'x' => 3 ]);
    $response->renderWith($callback);

    expect($response->dump()->body)->toEqual('x is 3');
});


