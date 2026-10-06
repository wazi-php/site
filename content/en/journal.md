# Changelog

What changes from one version of Wazi to the next. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), the numbers follow [semantic versioning](https://semver.org/):

- **patch** (`0.4.0` → `0.4.1`): fixes, nothing else. Always safe;
- **minor** (`0.4` → `0.5`): new features. Before 1.0, a minor version can still change the API: it is then written under "To change in your project";
- **major** (`1` → `2`): a change that forces you to modify your code.

The full rules are in [decision 033](docs/decisions/0033-versions-et-publications.md).

## Upcoming

### Added
- **Updated zones** (decision 036): in a template, `k:zone="liste"` marks a piece of the page, and `k:update="liste"`, on a form or a link, updates it without reloading the page. The controller does not change: it answers the same page, from which the `wazi.js` script keeps only the named zones. Without JavaScript, everything works as before.
- `wazi zones:install` command: installs `public/wazi.js`, or updates it. To be declared in the project's `wazi` file: `$console->add(new ZonesInstallCommand(__DIR__));`.
- Guide: "Updated zones" page.
- Demo: the notes page adds, searches, filters and deletes without reloading.

## 0.5.0 "Ça se voit" (it shows) — 2026-10-05

Nothing to change in a project created with 0.4.

### Added
- **Debug bar** (decision 035): in development mode, at the bottom of HTML pages, it shows the request, the route and the code that ran, the middlewares, the templates and their duration, the names of the session's keys. Read-only, with no address of its own and no history; written only for a request that came from the machine itself, with no proxy, under a local name; no sensitive value is collected. `new Kernel(debugBar: false)` to do without it.
- `Contracts\Tracer`: a component reports what it does without knowing the bar; Kioo reports each page written.
- Debug bar, **Base** section: the page's SQL queries (their text and placeholders, never their values) and their duration, with a warning when the same query is run in a loop. `Database::withTracer($app->tracer)` in `app.php`.
- **Console:** welcome screen with the name in large letters, the version and the commands grouped by family; detailed help for each command (`--help`): description, usage, arguments, options, examples, help text. `DetailedCommand` interface to give examples and help to your own commands; `Output::section()`, `accent()`, `note()`.
- **Redesigned error pages:** a centred card, the error code in a badge, details in sections, dark theme according to the visitor's setting. Still with no logo, no name and no loaded resource.
- Demo: notes are kept in an SQLite database (two migrations, `wazi db:migrate`) instead of a file; the debug bar shows each page's queries there.
- Demo: "wazi" next to the mark, halos of light behind the page, the displayed page's link marked in the header, and the six steps of a request's path open on click to show their code. Dark theme colours aligned with the identity.
- Visual identity: dark theme, spacing scale, rules for depth and accessibility (`docs/brand/`).
- The project now lives in the `wazi-php` GitHub organisation; the old addresses redirect.
- Repository: contribution guide (`CONTRIBUTING.md`), code of conduct, merge request and issue templates, dependency updates proposed by Dependabot; a GitHub release page is created for each tag, with the text of this changelog.

## 0.4.0 "Ça se construit" (it gets built) — 2026-10-04

### Added
- **The `wazi` console**: `serve`, `routes`, `explain`, `make:controller`, `views:compile`, and your own commands.
- **`app.php`**: the application is built once, for the site and for the console (`Kernel::load()`).
- **Templates prepared for going live** (`wazi views:compile`): pages are displayed faster.
- **Form validation**: `Validator`, one method per kind of field, which checks the value and returns it in its type.
- **Database**: `Database` (SQLite, MySQL, PostgreSQL), plain SQL and values always kept apart; migrations with `wazi make:migration`, `wazi db:migrate`, `wazi db:status`.
- Guide: "The console" and "The database" pages; "Forms" page rewritten.

### To change in your project
- A project created with 0.3 must receive an `app.php` file that builds the application and ends with `return $app;`, and a `public/index.php` reduced to `Kernel::load(__DIR__ . '/../app.php')->run();`. The starter project gives the model.

### Fixed
- Sessions: on Windows, writing a session sometimes failed ("Access denied") when another program held the file open for a moment.

### Security
- SQLite database: a path containing ".." can no longer get around the refusal of a file located in the public directory.

## 0.3.0 "Ça s'affiche" (it displays) — 2026-10-04

### Added
- **Kioo**, the templates: display escaped by default, `k:if`, `k:for`, layouts, included pieces, filters.
- **Configuration** through a `.env` file and the server's environment variables.
- **Sessions**: flash messages, lock, "remember me".
- **CSRF protection** by cookie, active on every route, even without a session.
- **Trusted proxies**: `X-Forwarded-*` headers are only believed when they come from a declared proxy.
- CSP token set automatically on the scripts written in a template.
- Demo application, twelve-page guide, `wazi/skeleton` starter project.

## 0.2.0 "Ça s'organise" (it gets organised) — 2026-10-03

### Added
- **Service container**, which builds classes and their dependencies by itself.
- **Controllers as classes**, routes declared with attributes (`#[Get]`, `#[Post]`...).
- **Middlewares**, global or per route; security headers on by default.

## 0.1.0 "Ça répond" (it answers) — 2026-10-03

### Added
- **HTTP**: requests, responses, streams, addresses and uploaded files, following the PSR-7 and PSR-17 standards.
- **Router**, with parameters and constraints.
- **Errors that teach**: every error says what happened, why, and how to fix it.
- **Kernel**, which assembles everything: an application fits in a single file.
