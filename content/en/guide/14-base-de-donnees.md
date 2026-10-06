# 14. The database

A database keeps what must last: accounts, articles, orders. You talk to it in **SQL**, a language made for that, which all databases understand.

Wazi does not hide SQL behind objects: you write it in plain sight, and what you write is what runs. Wazi takes care of the rest: connecting, passing values safely, turning errors into useful messages.

## Connecting

In `app.php`, you tell the container how to build the database:

```php
use Wazi\Database\Database;

$app->container->set(Database::class, static fn(): Database => Database::fromUrl(
    $config->string('DATABASE_URL', 'sqlite:var/app.sqlite'),
    __DIR__,
)->withTracer($app->tracer));
```

`withTracer($app->tracer)` is optional: in development mode, it makes the queries of each page appear in the [debug bar](15-barre-de-debogage.md). In production, `$app->tracer` is `null`, and nothing is reported.

Without a setting, it is an **SQLite** database: a simple file, `var/app.sqlite`. There is nothing to install, it is the ideal database to begin with.

For another database, a single setting in `.env`:

```ini
DATABASE_URL=mysql://user:password@localhost:3306/my_database
DATABASE_URL=postgres://user:password@localhost:5432/my_database
```

In the user name and the password, the characters `@ : / ? # %` are written encoded: `%40` for `@`. For a hosted PostgreSQL database that requires an encrypted connection, add `?sslmode=require`.

Each database needs its PHP extension: `pdo_sqlite`, `pdo_mysql` or `pdo_pgsql`. If one is missing, the error message says which one to enable.

A service that needs the database asks for it in its constructor, like any other service:

```php
final readonly class Carnet
{
    public function __construct(private Database $db) {}
}
```

**The connection only opens on the first query.** A page that reads nothing from the database costs nothing more.

## THE rule: a value is never written into the SQL

```php
// DANGER: never do this.
$db->select("SELECT * FROM notes WHERE auteur = '$auteur'");

// Always like this.
$db->select('SELECT * FROM notes WHERE auteur = ?', [$auteur]);
```

In the first form, the value is pasted into the query. If a visitor sends `x' OR '1'='1`, the query becomes `... WHERE auteur = 'x' OR '1'='1'`: it returns everybody's notes. This is an **SQL injection**, the best-known vulnerability of the web.

In the second, the query contains a **placeholder**, `?`, and the value is given separately. The database receives the two apart: the value, whatever it contains, remains a value. Injection is impossible.

Wazi has no method that pastes a value into a query. As long as you do not assemble SQL with variables yourself, you are safe.

Placeholders are written in two ways, not to be mixed in the same query:

```php
$db->select('SELECT * FROM notes WHERE auteur = ? AND couleur = ?', [$auteur, $couleur]);
$db->select('SELECT * FROM notes WHERE auteur = :auteur AND couleur = :couleur', ['auteur' => $auteur, 'couleur' => $couleur]);
```

## Reading

```php
// All the rows: a list of "column => value" arrays.
$notes = $db->select('SELECT id, texte FROM notes WHERE auteur = ? ORDER BY id DESC', [$auteur]);
// [['id' => 2, 'texte' => 'Lait'], ['id' => 1, 'texte' => 'Pain']]

// One row, or null if nothing matches.
$note = $db->selectOne('SELECT * FROM notes WHERE id = ?', [$id]);

// A single value.
$total = $db->selectValue('SELECT COUNT(*) FROM notes WHERE auteur = ?', [$auteur]);
```

A number stored in the database comes back as a number, not as a string. But for PHP, the content of a row still has an unknown type: check it (`is_string()`, `is_int()`) before using it, like what comes from a session.

## Writing

Three shortcuts for the most common writes:

```php
$id = $db->insert('notes', ['auteur' => $auteur, 'texte' => $texte]);     // returns the identifier created by the database

$db->update('notes', ['texte' => $texte], ['id' => $id, 'auteur' => $auteur]);
// UPDATE notes SET texte = ? WHERE id = ? AND auteur = ?

$db->delete('notes', ['id' => $id, 'auteur' => $auteur]);
```

For everything else, SQL is written in plain sight:

```php
$changees = $db->execute('UPDATE notes SET vues = vues + 1 WHERE id = ?', [$id]);     // returns the number of rows affected
```

Two protections:

- **An empty condition is refused.** `$db->delete('notes', [])` does not empty the table: it is an error. To affect all the rows, write it in SQL with `execute()`.
- **A single query per call.** `$db->execute('DELETE ...; DELETE ...')` is refused.

### Accepted values

String, whole or decimal number, boolean, `null`, date (`DateTimeInterface`, written `2026-10-04 15:30:00`) and backed enumeration. An array or another object is refused: to keep an array, pass it through `json_encode()`.

### Security: names never come from a visitor

A placeholder protects a **value**. It cannot replace the **name** of a table or a column.

```php
// DANGER: the visitor would choose the columns.
$db->insert('comptes', $request->getParsedBody());

// The checked values, one by one.
$db->insert('comptes', ['nom' => $nom, 'email' => $email]);
```

To sort according to a visitor's choice, compare that choice with a list, then write the name into the SQL:

```php
$v = new Validator($request->getQueryParams());
$tri = $v->choice('tri', ['creee_le', 'texte'], required: false) ?: 'creee_le';

$notes = $db->select('SELECT * FROM notes WHERE auteur = ? ORDER BY ' . $tri, [$auteur]);
```

This is the only case where a variable goes into SQL: it can only be what **you** wrote in the list.

The `insert()`, `update()` and `delete()` shortcuts refuse by themselves a name that does not have the shape of a name (unaccented letters, digits, `_`).

## Searching for a text

In a `LIKE`, `%` means "anything" and `_` "any character". A visitor searching for `100%` does not mean that:

```php
$notes = $db->select(
    "SELECT * FROM notes WHERE texte LIKE ? ESCAPE '!'",
    ['%' . Database::likeEscape($recherche) . '%'],
);
```

`likeEscape()` neutralises these characters with a `!`. The `ESCAPE '!'` in the query is essential: it tells the database what this `!` means.

## All or nothing: the transaction

When several writes only make sense together:

```php
$db->transaction(function (Database $db) use ($de, $vers, $somme): void {
    $db->execute('UPDATE comptes SET solde = solde - ? WHERE id = ?', [$somme, $de]);
    $db->execute('UPDATE comptes SET solde = solde + ? WHERE id = ?', [$somme, $vers]);
});
```

If the function throws an exception, everything it wrote is undone, and the exception carries on its way. Otherwise, everything is kept. `transaction()` returns what the function returns.

## Migrations: building the database step by step

A **migration** is an SQL file that changes the structure of the database: creating a table, adding a column.

```bash
wazi make:migration creer_notes
```

```text
OK  Créé : migrations/20261004_153000_creer_notes.sql
```

The file contains an example as a comment. Write your SQL in it:

```sql
CREATE TABLE notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    auteur VARCHAR(80) NOT NULL,
    texte TEXT NOT NULL,
    creee_le VARCHAR(19) NOT NULL
);

CREATE INDEX notes_auteur ON notes (auteur);
```

Then apply it:

```bash
wazi db:migrate
```

```text
OK  20261004_153000_creer_notes.sql

1 migration(s) appliquée(s).
```

The idea to remember: **the database is the result of these files, in order.** The name of each file starts with its date, which sets that order. Each file is applied only once; Wazi notes the ones that are done in the `wazi_migrations` table. On your computer, on a colleague's, on the server: the same command leads to the same database.

To see where you stand:

```bash
wazi db:status
```

```text
20261004_153000_creer_notes.sql                  faite      2026-10-04 15:31:02
20261012_091500_ajouter_couleur_aux_notes.sql    à faire

1 migration(s) à faire : wazi db:migrate
```

Two rules:

- **You only go forward.** To undo a change, you write a new migration that undoes it.
- **You do not modify a migration that has already been applied.** It would not be replayed: your change would not be in the database. `wazi db:migrate` warns you when it sees a file modified after the fact.

A migration is only run from the terminal. No address of the site changes the structure of the database.

### The `id` column changes from one database to another

| Database | A number given by the database to each row |
| --- | --- |
| SQLite | `id INTEGER PRIMARY KEY AUTOINCREMENT` |
| MySQL | `id INT AUTO_INCREMENT PRIMARY KEY` |
| PostgreSQL | `id SERIAL PRIMARY KEY` |

A project chooses its database, and its migrations are written for it. With PostgreSQL, `insert()` returns the value of the column named `id`.

## When the database refuses

A database error becomes a `DatabaseException`, which quotes your query and the database's answer, and says what to check:

```text
La base de données a refusé cette requête : SELECT * FROM articles — Réponse de la base :
« SQLSTATE[HY000]: General error: 1 no such table: articles ». Cette table n'existe pas (encore).
Avez-vous lancé « wazi db:migrate » ? Sinon, vérifiez l'orthographe du nom de la table.
```

Like any error, the visitor sees nothing of it in production: they receive the generic error page.

### A value already taken

A column declared `UNIQUE` refuses a value that already exists. It is the right way to guarantee that an email address is used by only one account: the database checks it at write time, even if two sign-ups arrive at the same moment.

```php
use Wazi\Database\Exception\DatabaseException;

try {
    $db->insert('comptes', ['email' => $email, 'nom' => $nom]);
} catch (DatabaseException $erreur) {
    if (!$erreur->isDuplicate()) {
        throw $erreur;
    }

    $v->check('email', false, 'This address is already in use.');
}
```

## What Wazi sets up for you

- Queries are **really prepared by the database**, which receives the values separately. PHP can also "emulate" this preparation by pasting the values into the SQL itself: Wazi does not let it.
- A database error always becomes an exception: it never goes unnoticed.
- SQLite checks the links between tables (foreign keys), which it does not do by itself.
- MySQL speaks `utf8mb4`: all characters, emoji included.
- The database password appears in no error message, no trace, no `var_dump()`.
- An SQLite file placed in the site's public directory is refused: it could be downloaded.

## The differences between databases

- **Booleans** come back as the database keeps them: `0` and `1` for SQLite and MySQL, `true` and `false` for PostgreSQL.
- **Failed migrations**: SQLite and PostgreSQL entirely undo a migration that fails. MySQL commits each structure change at once: a migration that fails in the middle stays half applied there, and the message says so.
- **SQLite** writes to a file: it suits a modest site on a single server very well. Beyond that, move to MySQL or PostgreSQL.

## Current limits

- No entity classes and no query builder: SQL is written by hand. It is a choice.
- A migration file is split into queries on the `;`. A trigger or a procedure, whose body contains `;`, cannot go through a migration file.
- No row-by-row reading of very large results: `select()` returns everything at once. Limit your queries with `LIMIT`.
- A single database per application.

Next: [The debug bar](15-barre-de-debogage.md).
