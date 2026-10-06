# 1. Getting started

## What you need

- **PHP 8.5** or newer, with the `mbstring` extension (it is almost always there already). To check: `php -v`.
- **Composer**, the tool that installs PHP libraries. To check: `composer -V`.

## Install

Wazi is not published on Packagist, Composer's directory, yet. Until then, get the repository:

```bash
git clone https://github.com/wazi-php/wazi.git
cd wazi
composer install
```

## A first page

Create a file named `bonjour.php` in the `examples/` directory:

```php
<?php

declare(strict_types=1);

use Wazi\Http\Response;
use Wazi\Kernel\Kernel;

require __DIR__ . '/../vendor/autoload.php';

$app = new Kernel();

$app->router->get('/', function () {
    return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], '<h1>Bonjour !</h1>');
});

$app->run();
```

Start the development server that ships with PHP:

```bash
php -S localhost:8000 examples/bonjour.php
```

Open http://localhost:8000: the page is displayed. To stop the server: `Ctrl+C`.

This file does three things, and every Wazi application does the same:

1. **create the kernel** (`new Kernel()`), which assembles the pieces;
2. **declare routes**: "for this address, run this code";
3. **answer** (`$app->run()`).

The code of a route always returns a **response**: a status code (200: all is well), headers, a body.

## Seeing your errors

By default, Wazi runs in **production mode**: when something fails, the visitor sees a neutral page, with no detail. That is what you want online.

While you develop, ask for the detail:

```php
$app = new Kernel(development: true);
```

The browser then shows what happened, in which file and on which line. This mode is never guessed: you have to write it. Never turn it on for a site that is online. The [Errors](10-erreurs.md) page explains what is displayed, and where to find the rest.

## Laying out a real project

A single file is enough to try things out. As soon as the project grows, lay it out like this:

```text
my-project/
├── app.php            Your application: settings, services, routes
├── public/            The ONLY directory visible from a browser
│   ├── index.php      The entry point: loads app.php and answers
│   └── app.css        Files served as they are: styles, scripts, images
├── src/               Your code: controllers, services
├── views/             Your Kioo templates
├── var/               What the application writes: sessions, files
├── .env               Your settings and secrets (never shared)
├── wazi               The project's console: loads app.php and runs a command
└── vendor/            The libraries installed by Composer
```

The rule that matters: **only `public/` is visible from the Internet**. Your code, your settings and the sessions sit above it, out of reach. Wazi checks this: it refuses a `.env` file or a sessions directory placed inside the public directory.

With this layout, the site is started by Wazi's console, which serves the public directory:

```bash
wazi serve
```

See [The console](13-console.md).

The [demo application](../../examples/demo/README.md) is laid out exactly this way. It is the best model to copy.

### `app.php`: the application, built once

In a laid-out project, the application is no longer built in `index.php` but in a file of its own, `app.php`, at the root. It reads the settings, creates the kernel, declares services and routes, then **returns** the application:

```php
<?php

declare(strict_types=1);

use Wazi\Config\Config;
use Wazi\Http\ServerRequestCreator;
use Wazi\Kernel\Kernel;

$config = Config::fromEnvFile(__DIR__ . '/.env');

$app = new Kernel(
    development: $config->bool('APP_DEBUG', false),
    requestCreator: new ServerRequestCreator(
        trustedHosts: $config->list('APP_HOSTS', []),
        trustedProxies: $config->list('APP_TRUSTED_PROXIES', []),
    ),
    views: __DIR__ . '/views',
    sessions: __DIR__ . '/var/sessions',
);

$app->router->addController(App\ArticleController::class);

return $app;
```

Each argument is explained on the page that covers it. None is required: `new Kernel()` works.

`public/index.php` then has only two useful lines left: load the classes, then load the application and ask it to answer.

```php
<?php

declare(strict_types=1);

use Wazi\Kernel\Kernel;

require __DIR__ . '/../vendor/autoload.php';

Kernel::load(__DIR__ . '/../app.php')->run();
```

`Kernel::load()` loads `app.php` and checks that it did return the application. If you forget the `return $app;`, it tells you.

Why two files? Because the site is not alone in needing the application. The console loads it too, for instance to list your routes (`wazi routes`). By building it in a single place, the site and the console see exactly the same thing.

One consequence: `app.php` is executed on every console command. You **declare** things there; you do not write to a file there, you do not send an email there.

## The path of a request

When a browser asks for a page, here is what happens, in order:

```text
browser
    │
    ▼
public/index.php          loads app.php, which creates the kernel and declares the routes
    │
    ▼
Kernel                    builds the request from what PHP received
    │
    ▼
Middlewares               security headers, form token, session, then yours
    │
    ▼
Router                    finds the route that matches the address
    │
    ▼
Route middlewares         form protection, then the ones you put on the route
    │
    ▼
Your code                 the function, or the controller's method; it returns a response
    │
    ▼
browser                   the response goes back through the middlewares, then it is sent
```

If an exception is thrown along the way, it becomes an error page.

Nothing is hidden: each step is a class in `src/`, which you can open. In your editor, a click on `Kernel`, `Router` or `Response` takes you there.

Next: [Routes](02-routes.md).
