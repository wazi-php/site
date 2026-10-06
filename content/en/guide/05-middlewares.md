# 5. Middlewares

A middleware is **a step placed around your code**. It sees the request on the way in and the response on the way out, like the layers of an onion:

```text
request ─►  [ A  ─►  [ B  ─►  [ your code ]  ─►  B ]  ─►  A ]  ─► response
```

You use one for whatever concerns several routes at once: checking that a visitor is signed in, adding a header, measuring a response time.

## Writing a middleware

A middleware is a class that implements `MiddlewareInterface` (the PSR-15 standard). It receives the request and "what comes next":

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Wazi\Http\Response;
use Wazi\Http\Session;

final readonly class ConnexionRequise implements MiddlewareInterface
{
    public function __construct(private Session $session) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!is_string($this->session->get('utilisateur'))) {
            return new Response(303, ['Location' => '/connexion']);
        }

        return $handler->handle($request);
    }
}
```

A middleware can do three things:

- **not call what comes next** and answer by itself, as above. The controller is then not even built;
- **modify the request** before calling what comes next: `$handler->handle($request->withAttribute('utilisateur', $nom))`;
- **modify the response** on the way back:

```php
$response = $handler->handle($request);

return $response->withHeader('Cache-Control', 'no-store');
```

Like any service, a middleware asks for what it needs in its constructor.

## In front of a route

```php
$app->router->get('/notes', [NoteController::class, 'liste'], [ConnexionRequise::class]);

#[Get('/notes', [ConnexionRequise::class])]
public function liste(): ResponseInterface { /* ... */ }
```

You give the **class name**: the container builds it only if the route is called. You can also give an object that is already built.

With several middlewares, the first in the list is the outermost: it acts first on the request, and last on the response.

## In front of every route

```php
$app = new Kernel(middlewares: [MesureDuTemps::class]);
```

These middlewares run **before the router**: they see every request go by, including those that match no route.

One detail: an error page (404, 500...) is born from an exception, and it is built **outside** the middlewares. A middleware that modifies the response on the way back will therefore not see an error page go by: the exception passes through it. To act anyway, wrap the call in a `try` / `finally`.

## The ones Wazi places itself

In this order, from the outermost to the innermost:

| Middleware | Role | Present |
| --- | --- | --- |
| `SecurityHeaders` | Adds the security headers to every response | always, unless `securityHeaders: null` |
| `CsrfCookie` | Carries the form token in a cookie | always |
| `SessionMiddleware` | Reads and saves the session | if you gave `sessions:` |
| *yours* | | |
| *the router* | | |
| `CsrfProtection` | Refuses a form without the right token | always, on every route |
| *the route's* | | |

To see this list applied to a precise address of your application, with your own middlewares in their place: `wazi explain notes/3`. See [The console](13-console.md).

### The security headers

`SecurityHeaders` adds to every response four headers that ask the browser to protect your visitors. The most important is the **content security policy** (CSP): the list of what the page is allowed to load.

By default, it is strict only about what is dangerous, JavaScript:

- style sheets, fonts, images, videos, frames: allowed from your site and from any `https` site;
- **scripts**: only the `.js` files of your own site, and the `<script>` tags written in your Kioo templates.

To load JavaScript from another site:

```php
use Wazi\Middleware\SecurityHeaders;

$app = new Kernel(securityHeaders: new SecurityHeaders(scripts: ['https://cdn.jsdelivr.net']));
```

Still refused: event attributes (`onclick="..."`), and a `<script>` written in a PHP string without going through a template. If a script does not run, the browser's console (the F12 key) says what was blocked.

A header already set by your controller is never replaced: that is how you make an exception for a single page.

To write the whole policy yourself: `new SecurityHeaders(contentSecurityPolicy: "default-src 'self'")`.

Next: [Kioo templates](06-kioo.md).
