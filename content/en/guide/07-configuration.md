# 7. Configuration

Some values change from one machine to another (the site's address, development mode), and others must stay secret (a password, a key). They do not belong in the code: **code is shared, settings are not**.

## The `.env` file

At the root of the project, next to `composer.json` and **never in `public/`**:

```text
# A comment
APP_DEBUG=true
APP_NAME="My notebook"
PAGINATION=20
APP_HOSTS=example.com, www.example.com
MAIL_PASSWORD='p@ss # this hash sign is part of the password'
```

The rules are few:

- `NAME=value`, the name in capital letters;
- without quotes, the value stops at the end of the line, or at a `#` preceded by a space;
- between single quotes, everything is taken as it is;
- between double quotes, `\n`, `\t`, `\"` and `\\` are interpreted;
- a value fits on one line.

A value is a **string**. Nothing in it is ever executed or substituted: `${OTHER}` stays `${OTHER}`.

### This file is not shared

The `.env` file holds your secrets: it must not go into Git. Add it to `.gitignore`, and share an `.env.example` file instead, with the same keys and harmless values. Each person copies it under the name `.env`.

```text
# .gitignore
/.env
/var/
```

## Reading a setting

```php
use Wazi\Config\Config;

$config = Config::fromEnvFile(__DIR__ . '/../.env');

$config->string('APP_NAME', 'My site');    // a string
$config->int('PAGINATION', 20);            // a whole number
$config->bool('APP_DEBUG', false);         // true or false
$config->list('APP_HOSTS', []);            // "a.com, b.com" becomes ['a.com', 'b.com']
$config->has('MAIL_PASSWORD');             // does the key exist?
```

In a `.env` file, everything is text. These methods say which **type** you expect, and check it: `PAGINATION=twenty` gives a clear error rather than a silent zero. For a true/false value, only `true` and `false` are accepted.

The second argument is the **default value**. Without it, the key is required:

```php
$config->string('DATABASE_PASSWORD');   // error if the key is missing
```

A missing `.env` file is not an error: the application starts with its default values.

## Where a value comes from

For each key, in this order:

1. an **environment variable** of the server that bears this name;
2. otherwise, the line of the `.env` file;
3. otherwise, the default value written in the code.

This is what lets you go live without a `.env` file: on a hosting platform or in a container, you declare the settings in the host's interface, and the application reads them.

### Name your keys with a prefix

The system defines variables of its own: `PATH`, `USER`, `HOME`, `LANG`... A key bearing one of these names would take the system's value. Prefix yours: `APP_`, `DATABASE_`, `MAIL_`.

A name cannot start with `HTTP_`: on some servers, these variables are built from the request, and therefore chosen by the visitor.

## Giving a setting to a service

`Config` is read in `app.php`, and the values are **given** to the services that need them:

```php
$app->container->set(Messagerie::class, fn () => new Messagerie(
    $config->string('MAIL_HOST'),
    $config->string('MAIL_PASSWORD'),
));
```

A service that receives its settings through its constructor states clearly what it depends on, and can be tested without a `.env` file.

## What Wazi does for your secrets

- The file's values stay **inside the `Config` object**. They are never copied into `$_ENV`, `$_SERVER` or `putenv()`, where another program could read them.
- A `.env` file placed in the public directory is **refused**: it could be downloaded.
- `var_dump($config)` shows the names of the keys, not their values.
- No error message contains a value; a badly written line is pointed out by its number.

In your own code, mark an argument that receives a secret so that PHP hides it in error traces:

```php
public function __construct(#[\SensitiveParameter] private string $motDePasse) {}
```

Next: [Sessions](08-sessions.md).
