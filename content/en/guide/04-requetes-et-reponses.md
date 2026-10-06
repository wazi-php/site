# 4. Requests and responses

The web comes down to one exchange: the browser sends a **request**, the server returns a **response**. In Wazi, these are two objects that follow the PSR-7 standard: what you learn here holds in the other PHP frameworks.

## The request

To receive it, ask for it as an argument:

```php
use Psr\Http\Message\ServerRequestInterface;

public function creer(ServerRequestInterface $request): ResponseInterface
```

| What you are looking for | How to read it |
| --- | --- |
| What follows the `?` in the address | `$request->getQueryParams()['page'] ?? null` |
| The fields of a form sent with POST | `$request->getParsedBody()` |
| A route parameter | the argument of the same name, or `$request->getAttribute('id')` |
| A header | `$request->getHeaderLine('Accept')` |
| A cookie | `$request->getCookieParams()['theme'] ?? null` |
| An uploaded file | `$request->getUploadedFiles()['avatar'] ?? null` |
| The raw body (JSON, for instance) | `(string) $request->getBody()` |
| The method, the address | `$request->getMethod()`, `$request->getUri()->getPath()` |
| The visitor's IP address | `$request->getAttribute('client_ip')` |

### Everything that comes from the request must be checked

A visitor can send anything: a missing field, an array instead of a string, a string of ten thousand characters. Before using a value, check **that it is there, and its type**:

```php
$formulaire = (array) $request->getParsedBody();
$titre = is_string($formulaire['titre'] ?? null) ? trim($formulaire['titre']) : '';
```

A field named `titre[]` in a form arrives as an array: without `is_string()`, `trim()` would fail.

For a form, the `Validator` does these checks for you, field by field: see [Forms](09-formulaires.md).

### JSON

`getParsedBody()` only holds the fields of an HTML form. For a JSON request, decode the body yourself:

```php
$donnees = json_decode((string) $request->getBody(), true);

if (!is_array($donnees)) {
    return new Response(400, ['Content-Type' => 'text/plain; charset=utf-8'], 'JSON expected.');
}
```

### An uploaded file

```php
$avatar = $request->getUploadedFiles()['avatar'] ?? null;

if ($avatar instanceof UploadedFileInterface && $avatar->getError() === UPLOAD_ERR_OK) {
    $avatar->moveTo(__DIR__ . '/../var/avatars/' . $utilisateurId . '.png');
}
```

Two security rules:

- `getClientFilename()` and `getClientMediaType()` return what the browser **claims**. An attacker writes whatever they want there. **Never build the destination path from the name given by the visitor**: choose the name and the extension yourself;
- do not store received files in the `public/` directory unchecked: a `.php` file dropped there would be executed by the server.

### What Wazi refuses before you do

Some requests never reach your code:

| Request | Response |
| --- | --- |
| A body larger than 8 MB | 413 |
| A malformed host name, or one missing from your `trustedHosts` | 400 |
| A malformed request | 400 |

The size limit can be set: `new ServerRequestCreator(maxBodySize: 2 * 1024 * 1024)`. Trusted hosts are explained in [Going live](12-deploiement.md).

## The response

```php
use Wazi\Http\Response;

return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], '<h1>Hello</h1>');
```

Three arguments: the **status code**, the **headers**, the **body**.

### The useful status codes

| Code | Meaning | When |
| --- | --- | --- |
| 200 | All is well | A page is displayed |
| 303 | Go and look elsewhere, with a GET | After a successful form |
| 400 | The request is incorrect | Unreadable JSON |
| 403 | Access refused | The visitor is not allowed |
| 404 | Not found | The requested article does not exist |
| 422 | The content does not fit | A badly filled form, displayed again with its errors |
| 500 | Failure | An unexpected exception (Wazi answers for you) |

### Common responses

A page, with a template (see [Kioo](06-kioo.md)):

```php
return $this->kioo->page('articles/liste', ['articles' => $articles]);
return $this->kioo->page('articles/introuvable', ['id' => $id], 404);
```

A redirect:

```php
return new Response(303, ['Location' => '/articles']);
```

JSON:

```php
return new Response(200, ['Content-Type' => 'application/json'], json_encode($donnees, JSON_THROW_ON_ERROR));
```

Plain text:

```php
return new Response(200, ['Content-Type' => 'text/plain; charset=utf-8'], 'Message received.');
```

Always state `Content-Type`: it is what tells the browser how to read the body.

### A redirect never follows an address it received

```php
// ⚠ Dangerous: the visitor chooses where they are sent.
return new Response(303, ['Location' => $request->getQueryParams()['retour']]);
```

Someone can craft a link to your site that sends people on to theirs, taking advantage of the trust your address inspires. Redirect to an address written in your code, or chosen from a list.

## Objects that do not change

A request and a response are **immutable**: the `with...()` methods do not modify the object, they return **a new one**.

```php
$response->withHeader('Cache-Control', 'no-store');              // ⚠ does nothing: the result is lost
$response = $response->withHeader('Cache-Control', 'no-store');  // correct
```

PHP and your editor warn you when the result of a `with...()` is ignored.

Why? An object that does not change cannot be modified behind your back by another piece of code. What you hold remains what you read.

## Do not write with `echo`

A Wazi application never calls `echo` or `header()`: it **returns** a response, and the kernel sends it. A forgotten `echo` is detected and reported with an error.

Next: [Middlewares](05-middlewares.md).
