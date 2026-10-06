# 10. Errors

An application fails in two ways, which Wazi handles differently.

- **A normal response to a request that cannot succeed**: page not found (404), access refused (403), incorrect request (400). This is not a failure.
- **A failure**: an exception nobody expected. The visitor receives a 500 response.

In both cases, your code has nothing to write: any exception that bubbles up becomes an error page.

## What the visitor sees

### In production (the default mode)

A neutral page: the code, a title, a sentence. For a failure, a **reference** as well. The page is in French today:

```text
500 — Erreur interne
Une erreur s'est produite de notre côté. Elle a été enregistrée.
Référence de l'erreur : d57153affdcd7daf
```

Nothing else. Neither the exception's message, nor a file name, nor the framework's name: all of that would inform someone looking for a vulnerability. The visitor can pass the reference on to you, and you find the error in the log.

### In development

```php
$app = new Kernel(development: true);
```

The same page, with what you need to fix things:

| | |
| --- | --- |
| **What happened** | The error's message |
| **Type of the error** | The exception's class |
| **Where, in your code** | The file and line of **your** code, not Wazi's |
| **Reference in the log** | To find the full report |

Even in development, the browser **never** shows the full trace, the value of a variable or a setting. The same is true of the [debug bar](15-barre-de-debogage.md). The detail is in the log. Wazi provides no debugging tool reachable from the browser: that kind of tool, forgotten online, is an open door.

This mode is never guessed. Tie it to a setting, false by default:

```php
$app = new Kernel(development: $config->bool('APP_DEBUG', false));
```

## The log

The full report of an error is written there: the message, then the chain of calls that led to it.

```text
[wazi] Erreur d57153affdcd7daf — 500 — RuntimeException : Le fichier des notes n'a pas pu être ouvert. (dans .../src/Carnet.php, ligne 152)
  #0 .../src/Carnet.php:48 Demo\Carnet->modifierLeFichier()
  #1 .../src/NoteController.php:60 Demo\Carnet->ajouter()
```

Where to read it:

- with the development server (`wazi serve`), **in the terminal** where it runs;
- online, in the file named by the `error_log` setting of `php.ini`, or in the web server's log.

What is written there:

- in production, only **failures** (codes 500 and above). A page not found is not an event worth recording;
- in development, everything.

The trace never contains the functions' **arguments**: a password passed to a function has no business being in a log.

To write elsewhere (a file of your application, a monitoring service), implement `ErrorLog`:

```php
use Wazi\Errors\ErrorHandler;
use Wazi\Errors\ErrorLog;

final class JournalFichier implements ErrorLog
{
    public function write(string $entry): void
    {
        file_put_contents(__DIR__ . '/../var/erreurs.log', date('c') . ' ' . $entry . "\n", FILE_APPEND | LOCK_EX);
    }
}

$app = new Kernel(errorHandler: new ErrorHandler($config->bool('APP_DEBUG', false), new JournalFichier()));
```

## What Wazi turns into an error

Wazi does not let PHP carry on after a problem:

- a PHP **warning** (a missing array key, a division by zero) becomes an exception, and therefore an error page. An ignored warning is often a bug that will come out further on, where it will be harder to understand;
- a **fatal error** (memory exhausted, time exceeded) gives a 500 page, not a blank page;
- an `echo` forgotten before the response is reported;
- a **deprecation** (a warning about a future version of PHP) goes to the log without interrupting the page.

PHP never displays an error in the page by itself: everything goes through Wazi, which decides what the visitor sees.

## Answering with an error yourself

### With a page of your own

The simplest, and the most common:

```php
$article = $this->catalogue->trouver($id);

if ($article === null) {
    return $this->kioo->page('articles/introuvable', ['id' => $id], 404);
}
```

### With an exception

To interrupt processing from a service or a middleware, throw an exception that implements `HttpError`: it knows its status code.

```php
use Wazi\Contracts\HttpError;

final class AccesRefuse extends \RuntimeException implements HttpError
{
    public function getStatusCode(): int
    {
        return 403;
    }

    public function getResponseHeaders(): array
    {
        return [];
    }
}
```

```php
throw new AccesRefuse('Only the author can change this note.');
```

The visitor receives Wazi's 403 page. The exception's message is only shown in development.

## Reading a Wazi error

Wazi's errors are written to be read. Each one says **what happened, why, and how to fix it**:

```text
La variable « nomm » n'existe pas dans ce template. Vouliez-vous écrire « nom » ?
Vérifiez son nom, et qu'elle est bien donnée au template. Pour prévoir son absence,
écrivez {nomm ?? 'valeur par défaut'}.
```

Read the message to the end: the correction is almost always in the last sentence.

## A page does not answer as expected

Before searching through the code, ask Wazi what the address goes through:

```bash
wazi explain notes/3
```

The command shows the chosen route, each middleware it goes through, and the code that runs. It also says why an address gives 404 or 405. See [The console](13-console.md).

## The limits

The look of the error pages cannot be changed yet: they use Wazi's colours, with no logo and no name, and follow the visitor's theme, light or dark.

Next: [Security](11-securite.md).
