# 2. Routes

A route connects **a method and an address** to **some code**.

```php
$app->router->get('/articles', function () {
    return new Response(200, [], 'The list of articles');
});
```

## Methods

The method says what the visitor wants to do. Each has its own function:

```php
$app->router->get('/articles', ...);            // read
$app->router->post('/articles', ...);           // create, or submit a form
$app->router->put('/articles/{id:int}', ...);   // replace
$app->router->patch('/articles/{id:int}', ...); // change in part
$app->router->delete('/articles/{id:int}', ...);// delete
```

For a route that answers several methods:

```php
$app->router->add(['GET', 'POST'], '/contact', ...);
```

Two things to know:

- a `GET` route also answers `HEAD` requests (the same headers, without the body);
- an HTML form can only send `GET` and `POST`. `PUT`, `PATCH` and `DELETE` are for requests sent by JavaScript or by another program.

**Reading changes nothing.** Anything that changes something (creating, deleting, signing out) goes through `POST` or another writing method, never through `GET`: a link can be followed by a robot, preloaded by the browser, or placed on another site.

## Parameters

A part of the address written between braces is a parameter:

```php
use Psr\Http\Message\ResponseInterface;

$app->router->get('/articles/{id:int}', function (int $id): ResponseInterface {
    return new Response(200, [], "Article no. $id");
});
```

`{id:int}` in the address, `int $id` in the function: **the same name**, so the value arrives in the argument. `/articles/42` calls the function with `$id = 42`.

After the colon comes the **constraint**: what the parameter is allowed to contain.

| Constraint | Accepts | Your code receives |
| --- | --- | --- |
| `{name}` or `{name:any}` | any text, without `/` | a string |
| `{id:int}` | a positive whole number: `0`, `7`, `42` | an `int` |
| `{slug:slug}` | lowercase letters, digits and hyphens: `my-first-article` | a string |
| `{id:uuid}` | a universal identifier: `123e4567-e89b-12d3-a456-426614174000` | a string |

An address that does not respect the constraint does not match the route: `/articles/abc` gives a 404 error, and your code is not called. So you never have to check that `$id` really is a number.

Why a closed list, and not regular expressions? A badly written expression can take several seconds to answer a crafted address. Wazi's are written once and bounded in length.

Parameters are also set on the request: `$request->getAttribute('id')`.

## Which route is chosen

The router goes through the routes **in the order you declared them** and takes the first one that matches. So order matters:

```php
$app->router->get('/articles/new', ...);       // first the precise address
$app->router->get('/articles/{slug}', ...);    // then the address with a parameter
```

The other way round, "new" would be taken for a slug.

Also worth knowing:

- `/articles` and `/articles/` are **two different addresses**;
- declaring the same method twice for the same address is an error, reported at startup.

## When nothing matches

| Situation | Response |
| --- | --- |
| No route for this address | 404, page not found |
| The address exists, but not for this method | 405, with the `Allow` header listing the accepted methods |

You have nothing to write for this.

## What the code of a route receives

Wazi fills each argument of your function this way, in this order:

1. an argument whose type is `ServerRequestInterface` receives **the request**;
2. an argument that bears the name of a route parameter receives **its value**;
3. otherwise, its default value, if it has one;
4. otherwise, it is an error: Wazi does not guess.

```php
use Psr\Http\Message\ServerRequestInterface;

$app->router->get('/articles/{id:int}', function (ServerRequestInterface $request, int $id) {
    $page = $request->getQueryParams()['page'] ?? '1';   // what follows the "?" in the address
    // ...
});
```

What follows the `?` in the address (`?page=2`) is not part of the route: you read it from the request. See [Requests and responses](04-requetes-et-reponses.md).

## The routes of a controller

As soon as there are more than a few routes, you put them in classes, and write each route above its method:

```php
use Wazi\Routing\Attribute\Get;
use Wazi\Routing\Attribute\Post;

final class ArticleController
{
    #[Get('/articles')]
    public function liste(): ResponseInterface { /* ... */ }

    #[Get('/articles/{id:int}')]
    public function voir(int $id): ResponseInterface { /* ... */ }

    #[Post('/articles')]
    public function creer(ServerRequestInterface $request): ResponseInterface { /* ... */ }
}
```

Then, in `app.php`, one line per controller:

```php
$app->router->addController(ArticleController::class);
```

There is one attribute per method: `#[Get]`, `#[Post]`, `#[Put]`, `#[Patch]`, `#[Delete]`.

Wazi does not scan any directory looking for controllers: you declare them one by one. That way you always know where a route comes from. A public method without an attribute is not a route.

To see all the routes of your application, in the order the router tries them:

```bash
wazi routes
```

The next page explains controllers: [Controllers and services](03-controleurs-et-services.md).

## The middlewares of a route

The last argument of a route is the list of middlewares that guard it:

```php
$app->router->get('/admin', [AdminController::class, 'accueil'], [ConnexionRequise::class]);

#[Get('/admin', [ConnexionRequise::class])]
public function accueil(): ResponseInterface { /* ... */ }
```

See [Middlewares](05-middlewares.md).
