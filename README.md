# GitHub webhook authentication middleware. 
[![License](https://img.shields.io/github/license/anfly0/PSR-15-github-auth)](https://badges.mit-license.org)
[![Build Status](https://travis-ci.com/anfly0/PSR-15-github-auth.svg?branch=master)](https://travis-ci.com/anfly0/PSR-15-github-auth)
> Simple PSR-15 middleware for authenticating GitHub webhooks.
---

## Installation

```shell
$ composer require anfly0/psr-15-github-auth
```
---
## Example
### Install dependencies for the following example:
```Shell
composer require anfly0/psr-15-github-auth
composer require slim/slim ^4.0.0-beta
composer require slim/psr7
composer require monolog/monolog
``` 
```php
<?php
/**
 * Minimal example using slim 4.0.0-beta and monolog 
 */
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\StreamFactory;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use Anfly0\Middleware\GitHub\Auth;

require './vendor/autoload.php';

// Logger setup
$logger = new Logger('example');
$logger->pushHandler(new StreamHandler('./example.log', Logger::INFO));

// Slim setup
$app = AppFactory::create();

// Middleware setup
$auth = new Auth('test', $app->getResponseFactory(), new StreamFactory());
$auth->setLogger($logger);

// If the request has no body, set the body to the contents of php://input.
$body = function (Request $request, $handler) {
    if ($request->getBody()->getSize() === 0) {
        $streamFactory = new StreamFactory();
        $request = $request->withBody($streamFactory->createStreamFromFile('php://input'));
    }
    return $handler->handle($request);
};


// Setup route and add middleware
$app->post('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write('OK');
    return $response;
})
->addMiddleware($auth)
->add($body);

$app->run();
```


---

## FAQ

- **Authentication fails when I expect it to succeed!?**
    1. Make sure that the correct secret is passed to the constructor when the middleware object is instantiated.
    2. Make sure that $request->getBody() === file_get_content('php://input').
        - i.e The ServerRequest object that is passed to the process method actually contains the request body.

- **How do I get the logging to work?**
    1. Simply pass a [PSR-3](https://www.php-fig.org/psr/psr-3/) compliant logger the setLogger method
---


## License



- **[MIT license](http://opensource.org/licenses/mit-license.php)**
- Copyright 2019 © Viktor Hellström
