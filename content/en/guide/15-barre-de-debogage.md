# 15. The debug bar

While you develop, Wazi adds a bar at the bottom of your pages. It answers the question every beginner asks in front of a page: **what just happened?**

```text
 W │ REQUÊTE GET /notes/3 · 200 · 12 ms │ ROUTE NoteController::voir │ MIDDLEWARES 3 │ TEMPLATES 1 · 7,0 ms │ BASE 2 · 0,8 ms │ SESSION 1 clé(s) │ WAZI PHP 8.5
```

Click a section: it unfolds above the bar.

## What it shows

| Section | What you read there |
| --- | --- |
| **Requête** (request) | The method, the path, the response code, the duration, the memory; the **names** of the received fields |
| **Route** | The pattern of the chosen route, the code that ran, its parameters, its rank, its middlewares; the routes it hides |
| **Middlewares** | The ones every request goes through, in order |
| **Templates** | The templates written, and the time of each |
| **Base** (database) | The page's SQL queries, with their placeholders (`?`) and their duration; a query repeated in a loop is reported |
| **Session** | The **names** of the session's keys |
| **Wazi** | The versions of PHP and of Wazi |

A response that is an error (404, 422...) is highlighted.

It is the same information as `wazi explain`, but for the request that just took place, in front of you.

## Turning it on

There is nothing to do: the bar appears as soon as the kernel is in development mode.

```php
$app = new Kernel(development: $config->bool('APP_DEBUG', false));
```

To develop without it:

```php
$app = new Kernel(development: true, debugBar: false);
```

## Seeing your SQL queries

The database is created by your `app.php`: that is where you tell it to report its queries to the bar.

```php
$app->container->set(Database::class, static fn(): Database => Database::fromUrl(
    $config->string('DATABASE_URL', 'sqlite:var/app.sqlite'),
    __DIR__,
)->withTracer($app->tracer));
```

The **Base** section then lists each query of the page, in order, with its duration:

```text
1. 0,4 ms    SELECT * FROM notes WHERE auteur = ? ORDER BY id DESC
2. 0,2 ms    SELECT COUNT(*) FROM notes WHERE auteur = ?
```

You read the **text** of the query there, as you wrote it, with its placeholders. Never the values: they may be passwords or personal data.

If the same query comes back three times or more, the section reports it. It is almost always a query run inside a loop: fetching the author of each note, one note at a time, when a single query would return them all.

## When it does not appear

The bar is only written if **all** these conditions are met:

1. the kernel is in **development mode**;
2. the request comes from **your computer** (`127.0.0.1` or `::1`);
3. the request did **not go through a proxy**;
4. the site is opened under a **local name**: `localhost`, `something.localhost`, `127.0.0.1`.

And it is only added to a **complete HTML page**. A JSON response, a redirect, a file or an error page has none.

If you develop in a container, behind a tunnel or on a remote machine, the bar will therefore not be displayed. This is deliberate: in those cases, type `wazi explain` in the terminal.

## Why so many precautions

A debug bar shown to a visitor hands them the map of the site: its routes, its files, what it keeps in the session. Other frameworks learned this the hard way, and that is why Wazi refused it for a long time.

It exists today with these safeguards:

- **It has no address.** It is written into the page you were receiving anyway. There is no profiler page, no request history to protect.
- **It only shows.** No button acts on your application, nothing runs on demand. It does not even have JavaScript.
- **It distrusts proxies.** Behind a proxy installed on the same machine, every request seems to come from `127.0.0.1`: that is the classic leak of "localhost only" tools. At the slightest proxy header, the bar steps aside.
- **It collects no sensitive value.** From a form or a session, it only reads the **names** of the fields and keys. Never a value, a cookie, a header, an environment variable or a setting. What is not collected cannot leak.
- **In production, its code is not loaded.** Production mode is the default, and it creates no object of the bar.

Despite all this, the basic rule does not change: **a site that is online never runs in development mode.**

## The limits

- A request sent by JavaScript, or followed by a redirect, has no bar: there is no history to find it in.
- If your application rewrites the content security policy to forbid styles written in the page, the bar is displayed without formatting.

Back to the [table of contents](../README.md).
