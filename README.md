# Yolk - A toy framework for small projects

Yolk is a small router-plus-dispatcher library for PHP applications running
under Apache.

- Routes to controllers
- Request pre-processors (middleware)
- Structured access to request parameters
- Friendly syntax for common responses
- Path generation for named routes
- Error pages and exception handling
- Supports dependency injection


## Sample front controller
```php
<?php
require 'vendor/autoload.php';

$app = new Yolk\App('https://example.com/');

$app->routes()
    ->get('/', HomeController::class)
    ->get('/admin', AdminController::class)
    ->get('/login', LoginController::class, 'login_page')
    ->post('/login', AuthenticateUser::class);

$app->routes()
    ->error(404, NotFoundController::class);

$app->middleware()
    ->only('/admin|/admin/.*', RequireLogin::class);

$app->renderViewsWith(
  fn($view, $data) => (new Twig\Engine)->render($view, $data))
);

$app->run()->output;
```

## Sample route controller
```php
<?php
use Yolk\Request;

class HomeController
{
    public function __invoke(Request $request)
    {
        return [
            'user' => Session::getLoggedInUser(),
            'articles' => Blog::getRecent(),
        ];
    }
}
```


## Request parameters

Controllers are invoked with a single parameter, an instance of `Yolk\Request`
which wraps all request parameters, in order of priority: route elements, query
string (GET), and body parameters (POST).

The request object can be accessed as an array. Missing parameters will have
`null` as value.

```php
$email = $request['email'];
```

The `optional()` and `required()` methods support accessing multiple parameters
at the same time. If a `required()` parameter is missing, Yolk will immediately
return a 400 response.

```php
list($name, $surname) = $request->required('name', 'surname');

# returns a single value
$phone = $request->optional('phone');

# returns an array
$address = $request->optional('address1', 'address2', 'address3');

# returns an associative array
$address = $request->optional([ 'address1', 'address2', 'address3' ]);
```

The `all()` method returns the full set of parameters.


## Short responses

Controllers can return an instance of `Yolk\Response` (which supports status
codes, headers, etc.) but they can also return shortcut values:

- integers for status codes

  ```php
  return 404;
  ```

  Yolk will call the error controller registered for that status code. If no
  controller has been configured, it will render a basic error page.

- strings for 302 redirects

  ```php
  # absolute URLs are used as-is
  return 'https://example.com';

  # relative URLs will be prepended with the base URL
  return '/admin'; # → https://example.com/admin

  # route names starting with a ':' will be converted
  return ':login_page'; # → https://example.com/login
  ```

- arrays to render a default view

  ```php
  return [
    'user' => $user,
    'balance' => $balance,
  ];
  ```

  Yolk will pass the controller name and the array to the rendering callback,
  which can derive the name of the view and render its content.


## Middleware

Before reaching a controller, Yolk can pass the `Request` object to a chain of
pre-processors. Each can inspect the Request, and decide to leave it as it is
and continue the chain (returning `null`), replace it (returning a new `Request`),
or interrupt the chain (returning a `Response`).

Pre-processors can be configured for all or a subset of the routes:

```php
$app->middleware()
    ->all(ValidateCsrfToken::class)
    ->except('/login', RequireAuthentication::class)
    ->only('/api/.*', EnforceRateLimit::class);
```


## Dependency injection

By default, Yolk creates instances of controllers without passing any
parameters. To rely on a dependency injection library, use the `injectWith()`
method:

```php
$app = new Yolk\App('https://example.com/');

$di = new SomeLibrary\DependencyInjection()
$app->injectWith(fn($class) => $di->inject($class));

$app->run()->output;
```
