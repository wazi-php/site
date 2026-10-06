# 6. Kioo templates

Kioo ("window pane" in Swahili) is Wazi's template engine. A Kioo template is **an ordinary HTML page**, stored in a `.kioo` file. You display a value between braces, and a few `k:` attributes decide what gets written.

```html
<h1>{titre}</h1>

<ul>
    <li k:for="note in notes" class="{note.importante ? 'importante' : ''}">
        <a href="/notes/{note.id}">{note.texte}</a>
    </li>
    <li k:else>No notes yet.</li>
</ul>
```

## Plugging Kioo in

Tell the kernel where your templates are:

```php
$app = new Kernel(views: __DIR__ . '/../views');
```

In a controller, ask for `Kioo` in the constructor, and return a page:

```php
use Wazi\View\Kioo;

final class NoteController
{
    public function __construct(private readonly Kioo $kioo) {}

    #[Get('/notes')]
    public function liste(): ResponseInterface
    {
        return $this->kioo->page('notes/liste', ['titre' => 'My notes', 'notes' => $notes]);
    }
}
```

`page('notes/liste', ...)` reads the file `views/notes/liste.kioo`. The second argument gives the template's **variables**. A third one chooses the status code: `page('introuvable', [], 404)`.

## Displaying a value

```html
<p>Hello {nom}!</p>
```

Everything displayed is **escaped**: if `nom` is `René <b>`, the page contains `René &lt;b&gt;`. The browser shows the text as it was typed, it does not interpret it. This is the protection against code injected into a page (the "XSS" vulnerability), and you have nothing to do to get it.

Kioo displays a **string** or a **number**. `null` displays nothing. For the rest, it asks you to be precise:

| Value | What to write |
| --- | --- |
| true / false | `{actif ? 'yes' : 'no'}` |
| a list | `k:for`, or `{notes \| join(', ')}` |
| an object | one of its properties: `{article.titre}` |
| a date | `{date \| date('d/m/Y')}` |

To write an actual brace, put a backslash before it: `\{`.

## Expressions

Between the braces, you write an expression. It is not PHP: it is a small language, limited on purpose.

| Syntax | Meaning |
| --- | --- |
| `note.texte` | the key of an array, or the public property of an object |
| `notes[0]`, `couleurs['fond']` | an element, by its position or its key |
| `article.resume(80)` | a public method of an object |
| `'text'`, `42`, `1.5`, `true`, `false`, `null` | literal values |
| `+ - * / %` | computing (with numbers only) |
| `== != < > <= >=` | comparing |
| `and`, `or`, `not` | combining conditions |
| `condition ? a : b` | one or the other |
| `value ?? 'default'` | the value, or the default if it is missing or `null` |

### Kioo is strict, on purpose

An **unknown** variable, key or property **is an error**, not a blank. Here is the message, as Wazi writes it (its messages are in French today):

```text
La variable « nomm » n'existe pas dans ce template. Vouliez-vous écrire « nom » ?
```

So a typo shows up right away, instead of giving a silently incomplete page. When the absence is expected, say so with `??`:

```html
<p k:if="(erreur ?? null) != null" class="erreur">{erreur}</p>
```

Likewise, Kioo never silently turns a string into a number: `{quantite + '3'}` is an error, and `3 == '3'` is false.

## Filters

A filter transforms a value before displaying it. It is written after a vertical bar:

| Filter | Example | Result |
| --- | --- | --- |
| `upper` | `{'bonjour' \| upper}` | `BONJOUR` |
| `lower` | `{'BONJOUR' \| lower}` | `bonjour` |
| `capitalize` | `{'bonjour le monde' \| capitalize}` | `Bonjour le monde` |
| `trim` | `{'  bonjour  ' \| trim}` | `bonjour` |
| `length` | `{notes \| length}` | the number of elements in a list, or of characters in a string |
| `number` | `{1234.5 \| number(2)}` | `1 234,50` |
| `date` | `{date \| date('d/m/Y à H:i')}` | `03/10/2026 à 17:30` |
| `join` | `{notes \| join(' et ')}` | `Pain et Lait` |
| `first`, `last` | `{notes \| first}` | the first, the last element |
| `json` | `data-points="{points \| json}"` | the value written as JSON, for an attribute |
| `url` | `href="/recherche?q={mots \| url}"` | the text prepared to go into an address |

The `date` filter expects a `DateTime` or `DateTimeImmutable` object. Its format is PHP's: `d` (day), `m` (month), `Y` (year), `H` (hours), `i` (minutes).

Filters can be chained: `{titre | trim | upper}`.

### A filter applies to everything on its left

`{prix * 2 | number}` formats the product, not just `2`. In return, **nothing follows a filter** without parentheses:

```html
{(notes | length) > 1 ? 'several notes' : 'one note'}
```

### Your own filters

A filter is a function. Add it in `app.php`, after creating the kernel:

```php
use Wazi\View\Kioo;

$kioo = $app->container->get(Kioo::class);

$kioo->addFilter('euros', fn (mixed $prix): string => number_format((float) $prix, 2, ',', ' ') . ' €');
```

```html
{article.prix | euros}
```

The function receives the value written on the left of the bar, then the arguments written between parentheses. What it returns is escaped like any displayed value.

A filter cannot replace another one: its name must be free.

A template can **only** call the filters of this list and your own. No PHP function is reachable from a template.

## Structures

### `k:if` and `k:else`

```html
<p k:if="(notes | length) > 5">You have many notes.</p>
<p k:else>You have few notes.</p>
```

The tag is written if the condition is true. These are "false": `false`, `null`, the empty string, the number zero and the empty list. So `<p k:if="notes">` is enough for "if there are notes".

`k:else` goes on the tag **that follows** the one carrying `k:if`. Between the two, only whitespace and comments are allowed.

### `k:for`

```html
<li k:for="note in notes">{note.texte}</li>
<li k:else>No notes.</li>
```

The tag is written once per element. With a `k:else` right after it, that one is written when the list is empty.

To also get the key, or the position:

```html
<li k:for="position, note in notes">{position + 1}. {note.texte}</li>
<li k:for="nom, valeur in reglages">{nom}: {valeur}</li>
```

### The rules

- The value of `k:if` and `k:for` is written **without braces**.
- A tag carries **only one** structure attribute. To combine them, put one on a tag that wraps the other.
- A tag that carries a structure must be closed (`<li k:for="...">...</li>`).

## Attributes

A value can also be displayed in an attribute:

```html
<a href="/notes/{note.id}" class="note {note.importante ? 'importante' : ''}">
```

**True / false attributes.** When an attribute is made of a single displayed value, it is written if the value is true, removed if it is false or `null`:

```html
<input type="checkbox" checked="{note.importante}">
<!-- gives <input type="checkbox" checked>  or  <input type="checkbox"> -->
```

**Addresses.** In `href`, `src` and attributes of the same kind, Kioo checks the protocol. A dangerous address (`javascript:...`) becomes `#`.

Kioo escapes a value for HTML, not for an address. Free text placed in an address goes through the `url` filter, which replaces spaces, `&` and `#`:

```html
<a href="/recherche?q={mots | url}">Search for "{mots}"</a>
```

**Event attributes.** Displaying a value in `onclick`, `onload`... is refused: in that place, no escaping protects the page. Put the script in a `.js` file and pass the value through a `data-` attribute: `data-id="{note.id}"`.

## The layout

All the pages of a site share the same skeleton. You write it once, in `views/base.kioo`:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><k:block name="titre">My site</k:block></title>
</head>
<body>
    <main>
        <k:block name="content"></k:block>
    </main>
    <k:include file="partiels/pied" annee="{annee}">
</body>
</html>
```

Each `<k:block>` is a **slot**, with default content. A page says which layout it goes into, and fills the slots:

```html
<k:layout name="base">
<k:block name="titre">My notes</k:block>

<h1>My notes</h1>
<p>Everything that is not in a block goes into the "content" slot.</p>
```

The rules:

- `<k:layout>` is the first thing in the file; a layout does not use another one;
- a slot that the page does not fill keeps its default content;
- the layout receives the same variables as the page.

### The variables every page displays

The layout often displays common values: the visitor's name in the header, the year in the footer. Rather than passing them from every controller, share them once, in `app.php`:

```php
$kioo = $app->container->get(Kioo::class);

$kioo->share('annee', (int) date('Y'));
```

All templates then see `{annee}`: pages, layouts and included pieces.

When the value is only known at the moment the page is displayed (it depends on the session, for instance), give a function. It is called once per displayed page:

```php
$session = $app->container->get(Session::class);

$kioo->share('utilisateur', fn () => $session->get('utilisateur'));
```

A variable of the same name given to a page wins over the shared variable. The demo uses this in [`app.php`](../../examples/demo/app.php).

### Including a piece

```html
<k:include file="partiels/pied" annee="{annee}">
```

The included template **only sees what it is passed** through the attributes (here, `annee`), plus the variables shared with `share()`. That way, reading the line tells you what the piece depends on.

The names in `file` and `name` are written literally: they cannot come from a value. A template cannot leave the views directory.

## Kioo and JavaScript

### The scripts of your templates

```html
<script src="/app.js" defer></script>

<script>
    document.getElementById('compteur').textContent = 'Loaded.';
</script>
```

Both work. On each `<script>` tag of a template, Kioo sets a token, renewed on every request, that the security policy allows. A script injected by a visitor does not know this token: the browser refuses to run it.

The content of a `<script>` or `<style>` tag is **never interpreted** by Kioo: braces there are JavaScript or CSS, not displayed values.

### Passing a value to a script

So you do not write a value into JavaScript code. You leave it next to it:

```html
<k:json id="donnees" value="{notes}">

<script>
    const notes = JSON.parse(document.getElementById('donnees').textContent);
</script>
```

`<k:json>` produces a tag that the browser keeps as data, without running it. For a small value, an attribute is enough: `<div data-id="{note.id}">`, or `data-points="{points | json}"` for a list.

## Forms

Kioo adds by itself, to every `<form method="post">` sent to your site, the hidden field of the protection token. You write nothing. See [Forms](09-formulaires.md).

## Updating a piece of the page

`k:zone` marks a piece of the page, and `k:update`, on a form or a link, updates it without reloading the page. See [Updated zones](16-zones.md).

## Writing HTML as it is

To display HTML that **you** produced and that you know to be safe:

```html
{article.contenuHtml | unsafe_raw}
```

The name is long and worrying on purpose: everything that goes through it is run by the browser as it is. Never put there a text typed by a visitor without cleaning it first.

## Comments

```html
<!-- To revisit: the list should be sorted by date. -->
<ul>
```

A comment in a template is for you, not for the visitor: **it is never written to the page**. So you can explain a template as much as you like, without making the page heavier or informing someone who reads its source code.

## When a template is wrong

The error gives the name of the template, the line, what is wrong, and often the correction:

```text
Dans le template « notes/liste », ligne 12 : Le filtre « uper » n'existe pas.
Vouliez-vous écrire « upper » ? Filtres disponibles : capitalize, date, first, ...
```

## What Kioo does not do yet

Kioo is simpler and safer than the best-known engines, not more powerful. It does not yet have: reusable components with slots, translation, or an operator to join two strings.

About speed: while you develop, a template is read and parsed again on every request, which takes a few milliseconds per page. Online, the `wazi views:compile` command does this parsing once and for all, at deployment: see [Going live](12-deploiement.md).

Next: [Configuration](07-configuration.md).
