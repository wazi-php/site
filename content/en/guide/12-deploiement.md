# 12. Going live

The development server (`wazi serve`) is for developing, not for receiving visitors. Online, you need a real web server, and a few settings.

## The checklist

Before opening the site:

- [ ] The web server serves **only** the `public/` directory.
- [ ] `APP_DEBUG` is `false`, or not defined.
- [ ] The site uses **HTTPS**.
- [ ] `APP_HOSTS` holds the site's names.
- [ ] Behind a proxy: `APP_TRUSTED_PROXIES` holds its addresses.
- [ ] The `var/` directory can be written to by PHP, and by PHP alone.
- [ ] `composer install --no-dev --optimize-autoloader` has been run.
- [ ] `wazi db:migrate` has been run: the database has all its tables.
- [ ] `wazi views:compile` has been run, and the `build/` directory cannot be written to by the web server.
- [ ] `composer audit` reports nothing.
- [ ] You know where to read the error log.

Each point is explained below.

## The public directory

The web server's root must be the project's `public/` directory, not the project itself. Otherwise, `.env`, the code and the sessions can be downloaded. Wazi does in fact refuse to read a `.env` file or to write sessions in the site's root.

Any address that does not match an existing file must be handed to `index.php`.

**nginx**, with PHP-FPM:

```nginx
server {
    listen 443 ssl;
    server_name example.com;
    root /var/www/my-project/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location = /index.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
    }

    # No other PHP file is executed.
    location ~ \.php$ {
        return 404;
    }
}
```

**Apache**, in a `public/.htaccess` file (the `mod_rewrite` module must be enabled, and the site's root set to `public/`):

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
```

Adapt the paths and the PHP version to your server.

## The settings

Online, settings are given in one of two ways, as you prefer:

- a `.env` file placed on the server, at the project's root (outside `public/`), readable by PHP only;
- **environment variables**, declared in the interface of the host or of the container. They take precedence over the file, and there is then no `.env` online at all.

See [Configuration](07-configuration.md).

### Development mode

`APP_DEBUG=false`, or nothing. In development mode, every visitor would see the message of your errors and the path of your files.

## Trusted hosts

A request announces the name of the site it wants to reach. Nothing stops an attacker from announcing another one, so that your application builds links to their site (in a password reset email, for instance).

Declare the names your site answers to:

```text
APP_HOSTS=example.com, www.example.com
```

```php
new ServerRequestCreator(trustedHosts: $config->list('APP_HOSTS', []))
```

A request for another name receives a 400 response. Without this list, any well-formed name is accepted: handy on your computer, not to be left that way online.

## HTTPS

Get a certificate (free with Let's Encrypt, often provided by the host) and redirect all HTTP traffic to HTTPS, in the web server's configuration.

When the site uses HTTPS, Wazi sees it and strengthens the cookies by itself:

- the session cookie receives the `Secure` attribute: it never travels in clear text;
- the form token's cookie takes the name `__Host-csrf`, which nobody but your site can set.

## Behind a proxy

On most modern hosting, a **proxy** sits in front of PHP: a load balancer, Cloudflare, nginx or Traefik in front of a container. It is the one that receives the visitor's HTTPS connection; it then talks to PHP over HTTP, and passes the original information on in `X-Forwarded-*` headers.

Without a setting, Wazi **ignores these headers**: any visitor can write them. It then believes the site uses HTTP, and the IP address it sees is the proxy's. Consequences: cookies without `Secure`, a less protected form token.

Declare the addresses of **your** proxies:

```text
APP_TRUSTED_PROXIES=10.0.0.5
APP_TRUSTED_PROXIES=10.0.0.0/8, 172.16.0.0/12
```

```php
new ServerRequestCreator(trustedProxies: $config->list('APP_TRUSTED_PROXIES', []))
```

The headers are then only believed if the request arrives from one of these addresses. Your code reads the visitor's address like this:

```php
$request->getAttribute('client_ip');
```

**How do you know whether you are behind a proxy?** If, online and over HTTPS, the session cookie does not have the `Secure` attribute (visible in the browser's tools, F12 key, Application or Storage tab), Wazi believes the site uses HTTP: there is a proxy to declare. Its address is in your host's documentation.

Never declare a range wider than necessary, and never "all addresses": that would mean believing everyone.

## The `var/` directory

PHP must be able to write there (sessions, the application's files), and nobody else must be able to read there. On a Linux server:

```bash
chown -R www-data:www-data var
chmod -R 700 var
```

Replace `www-data` with the account that runs PHP on your server.

This directory holds data, not code: it does not go into Git, and it must not be overwritten on every update of the site.

## Installing the dependencies

```bash
composer install --no-dev --optimize-autoloader
```

`--no-dev` does not install the development tools (tests, analysis): less code online, less attack surface.

## Updating the database

```bash
wazi db:migrate
```

To be run on every deployment, after putting the code in place: the database receives the tables and columns added since last time. Running it again does nothing more. See [The database](14-base-de-donnees.md).

The database password is given through the server's `DATABASE_URL` environment variable, or through the `.env` file: never in the code.

With SQLite, the database is a file in the `var/` directory. PHP must be able to write to this file **and** to its directory. If you run `wazi db:migrate` under an account other than PHP's, give the directory back to PHP afterwards (`chown`, as above). And remember to back this file up: it is your whole database.

## Preparing the templates

While you develop, each template is parsed on every request: you change it, you reload, it is up to date. Online, this repeated work costs a few milliseconds per page. The console does it once and for all:

```bash
wazi views:compile
```

```text
  accueil
  base
  contact
  partiels/pied

OK  4 template(s) préparé(s).
```

Each template is parsed, and the result is written to a file in the `build/views` directory. PHP keeps these files in memory: pages are displayed faster. On the demo application, the home page goes from 4.8 to 0.9 ms.

The directory is set in `app.php`:

```php
$app = new Kernel(
    views: __DIR__ . '/views',
    compiledViews: __DIR__ . '/build/views',
);
```

What you need to know:

- **the command checks all your templates.** A mistake in one of them stops it, with the template's name and the line. Run it in your deployment script: a broken template will not reach production;
- **a page is never stale.** If you change a template without running the command again, Wazi sees it (its date and size have changed) and parses it as usual. You lose the speed, not the correctness;
- **run the command again on every update of the site.**

### The `build/` directory must not be writable by the web server

The prepared files are PHP: what they contain is executed. If the web server could write to this directory, a flaw in your site that let a visitor drop a file there would let them run code.

Wazi does not just ask you: **it checks on every request**. If PHP is allowed to write to the directory, it refuses to use it and parses the templates as in development. The worst that happens to someone who forgets is a slower site.

On a Linux server, assuming you deploy with the `deploy` account and PHP runs as `www-data`:

```bash
wazi views:compile
chown -R deploy:deploy build
chmod -R a-w,a+rX build
```

This is the opposite of the `var/` directory, which must be writable by PHP. That is why they are two separate directories.

If your hosting runs PHP under the account that uploads the files (common on shared hosting), the directory will always be writable, and the prepared templates will not be used. You can accept that knowingly, with an explicit gesture: `unsafeAllowWritableCompiledViews: true` in `app.php`.

If your server is set never to re-check PHP files (`opcache.validate_timestamps=0`), clear OPcache after the command, as after any update of the code.

## The error log

In production, the visitor only sees a reference. The detail is in PHP's log: find out where it is on your server (the `error_log` setting of `php.ini`, or the web server's log) **before** you need it. See [Errors](10-erreurs.md).

## Current limits

- **A single server.** Sessions are stored in local files: an application spread over several servers would not share its sessions.
- **Pages are still rendered on every request**, even with prepared templates. For a high-traffic site, put an HTTP cache in front of the public pages, leaving out those that contain a form or a visitor's data.

Next: [The console](13-console.md).
