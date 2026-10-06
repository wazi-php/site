# 3. Controllers and services

## Two roles, two kinds of classes

- A **service** does a job: storing articles, sending an email, computing a price. It knows nothing about the web.
- A **controller** turns a request into a response. It reads what the visitor asks for, hands the work to services, and returns a page.

Keeping them apart makes each class short and readable, and the service reusable elsewhere (in a test, in a command).

```php
// The service: it knows nothing about the web.
final class Catalogue
{
    /** @return list<string> */
    public function titres(): array
    {
        return ['First article', 'Second article'];
    }
}

// The controller: it turns a request into a response.
final class ArticleController
{
    public function __construct(private readonly Catalogue $catalogue) {}

    #[Get('/articles')]
    public function liste(): ResponseInterface
    {
        return new Response(200, ['Content-Type' => 'text/plain; charset=utf-8'], implode("\n", $this->catalogue->titres()));
    }
}
```

```php
$app->router->addController(ArticleController::class);
```

Nobody wrote `new ArticleController(new Catalogue())`. That is the container's job.

To start from a controller that is already written and commented, the console creates one, with its page: `wazi make:controller Article`. See [The console](13-console.md).

## The container

To build a controller, you first have to build what it needs, and what its needs need. The **container** does it for you: it reads the constructor of the requested class, builds each expected object, and does the same again for each of them.

The rule to remember: **what a class needs is asked for in its constructor.**

```php
public function __construct(
    private readonly Catalogue $catalogue,
    private readonly Kioo $kioo,          // the template engine
    private readonly Session $session,    // the visitor's session
) {}
```

Each service is built **only once** per request: two classes that ask for a `Catalogue` receive the same object.

The kernel's container is available as `$app->container`.

### When the container cannot guess

On its own, it only knows how to build **objects**. Two cases need an explanation.

**The class needs a string or a number** (a path, an address, a secret). You give a recipe:

```php
$app->container->set(Catalogue::class, fn () => new Catalogue(__DIR__ . '/../var/articles.json'));
```

The recipe only runs on the first request for the service. It receives the container, to fetch other services from it:

```php
use Wazi\Container\Container;

$app->container->set(Lettre::class, fn (Container $c) => new Lettre($c->get(Catalogue::class), 'contact@example.com'));
```

**The class asks for an interface.** You say which class to provide:

```php
$app->container->bind(Messagerie::class, MessagerieSmtp::class);
```

### What the kernel already puts in it

| Class | Available |
| --- | --- |
| `Wazi\View\Kioo` | if you gave `views:` to the kernel; configured with `addFilter()` and `share()` |
| `Wazi\Http\Session` | if you gave `sessions:` to the kernel |
| `Wazi\Http\CsrfToken` | always |
| `Wazi\Http\CspNonce` | always |

### The errors you will meet

The container explains what it is missing. For instance, if a constructor asks for a `string $chemin` with no recipe, the message names the class and the argument, and shows the `set()` line to write. A circular dependency (A asks for B, which asks for A) is detected and described.

### A security rule

**Never** pass to `$container->get()` a string that came from a request: the visitor would choose which class your application builds. Class names are written in your code.

## The methods of a controller

A controller method receives the request and the route's parameters, like a function does (see [Routes](02-routes.md)):

```php
#[Get('/articles/{id:int}')]
public function voir(ServerRequestInterface $request, int $id): ResponseInterface
```

**Services**, on the other hand, are not asked for here but in the constructor. This is deliberate: reading the constructor shows at a glance everything the class depends on.

A route method must be **public** and return a **response**. Otherwise, Wazi says so at startup or at call time, with the correction to make.

## Without attributes

Attributes are a convenience. The same route can also be written:

```php
$app->router->get('/articles/{id:int}', [ArticleController::class, 'voir']);
```

The controller is only built if the route is called.

Next: [Requests and responses](04-requetes-et-reponses.md).
