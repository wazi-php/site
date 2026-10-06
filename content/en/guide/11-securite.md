# 11. Security

Wazi's principle: **the default setting is the safe setting**. What is dangerous takes an explicit gesture, with a name, placed in a precise spot. Every refusal explains why.

This page takes stock: what Wazi does without you, then what remains your responsibility. No framework makes an application safe by itself.

## What Wazi does for you

| Attack | What it is after | The protection | Detail |
| --- | --- | --- | --- |
| Code injected into a page (XSS) | Getting another visitor's browser to run a script | Kioo escapes everything it displays; the security policy refuses scripts that do not come from you | [Kioo](06-kioo.md), [Middlewares](05-middlewares.md) |
| Request forgery (CSRF) | Making a signed-in visitor act without knowing | A token checked on every request that changes something | [Forms](09-formulaires.md) |
| Session theft or fixation | Taking the place of a signed-in visitor | `HttpOnly`, `SameSite`, `Secure` cookie; an identifier impossible to guess, never adopted if it comes from elsewhere | [Sessions](08-sessions.md) |
| Clickjacking | Displaying your site in an invisible frame | `X-Frame-Options: DENY` | [Middlewares](05-middlewares.md) |
| Information leaks | Reading an error message, a trace, a setting | Production mode by default; debug bar reserved for development mode, for your own computer, and with no secret at all | [Errors](10-erreurs.md), [Debug bar](15-barre-de-debogage.md) |
| Leaked secrets | Downloading the `.env` file or the sessions | Refusal to start if they are in the public directory | [Configuration](07-configuration.md) |
| Leaks through the pages' source code | Reading your working comments | A template's comments are never written to the page | [Kioo](06-kioo.md) |
| Crafted addresses | Climbing up the directories with `..` | An address with a dangerous segment matches no route | [Routes](02-routes.md) |
| Oversized requests | Saturating the server | Body refused beyond 8 MB | [Requests](04-requetes-et-reponses.md) |
| Forged headers | Passing for another host, another IP address | Host validated; `X-Forwarded-*` ignored except from your declared proxies | [Going live](12-deploiement.md) |
| Log forgery | Slipping fake lines into the log | Values that came from a visitor are cleaned before going in | [Errors](10-erreurs.md) |

## The ways out, and their names

Turning a protection off is possible, but it shows in the code. Search a project for these words to know where it takes risks:

| What you write | What you turn off |
| --- | --- |
| `{valeur \| unsafe_raw}` | Kioo's escaping, for this value |
| `[WithoutCsrf::class]` | The token check, for this route |
| `new Kernel(development: true)` | The discretion of the error pages |
| `new Kernel(securityHeaders: null)` | The security headers |
| `SecurityHeaders::WITHOUT_POLICY` | The content security policy |
| `Config::fromEnvFile($f, unsafeAllowPublicLocation: true)` | The refusal of a `.env` file in the public directory |
| `new Kernel(unsafeAllowWritableCompiledViews: true)` | The refusal to read prepared templates from a directory PHP can write to |

There is no switch that turns a protection off "everywhere".

## What remains your responsibility

### Check what comes in

Everything that comes from a request can be anything: fields, address parameters, cookies, headers, files. Check presence, type, length, and membership of a list when there is one.

### Check rights, not just the sign-in

"Are they signed in?" is not enough. For each object, also ask: **"is it really theirs?"**

```php
// ⚠ Any signed-in visitor can delete any note.
$this->carnet->supprimer($id);

// The note is only deleted if it belongs to the one asking.
$this->carnet->supprimer($id, $auteur);
```

This is the most widespread mistake in web applications. The safest thing is to make it impossible: in the demo, every method of [`Carnet`](../../examples/demo/src/Carnet.php) requires the author.

### Passwords

- `password_hash()` to keep their hash, `password_verify()` to compare. Never the password itself, never `md5()` or `sha1()`.
- A failure message that does not say whether the account exists.
- A limit on the number of attempts: Wazi does not provide it.

### Comparing a secret

Token, signature, key: with `hash_equals()`, never with `==`. The time an ordinary comparison takes gradually reveals the right value.

### Received files

- You choose the name and the extension of the saved file, never the visitor.
- Store them outside `public/`, or in a directory where the server does not run PHP.
- Check the content, not the type announced by the browser.

### Redirects

To an address written in your code, never to an address received in the request.

### The database

A value is never written into the SQL: you write a placeholder (`?`), and give the value separately. The database receives the two apart, and SQL injection becomes impossible.

```php
$notes = $db->select('SELECT * FROM notes WHERE auteur = ?', [$auteur]);
```

Wazi has no method that pastes a value into a query. What remains your responsibility: never assemble SQL yourself with a variable that came from a visitor, and never let them choose a table or column name. See [The database](14-base-de-donnees.md).

### Your dependencies

Every installed library is code that you run. Before going live, and regularly afterwards:

```bash
composer audit
```

This command reports the known vulnerabilities in what you have installed.

### HTTPS

Online, a site without HTTPS has no security: everything that travels can be read and altered on the way, cookies included. See [Going live](12-deploiement.md).

## Reporting a vulnerability in Wazi

Do not write it in a public issue. The procedure is in the repository's [`SECURITY.md`](../../SECURITY.md) file.

Next: [Going live](12-deploiement.md).
