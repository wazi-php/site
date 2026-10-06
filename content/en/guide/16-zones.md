# 16. Updated zones

Adding a note, filtering a list, deleting a row: each time, the browser reloads the whole page. **Zones** let you replace only pieces of it, without changing anything in your controllers.

```html
<section k:zone="ajout">
    <form method="post" action="/notes" k:update="ajout, liste, compteur">
        <input name="texte">
        <button>Add</button>
    </form>
</section>

<p k:zone="compteur">{notes | length} note(s)</p>

<ul k:zone="liste">
    <li k:for="note in notes">{note.texte}</li>
</ul>
```

- `k:zone="liste"` marks a piece of the page and gives it a name;
- `k:update="ajout, liste, compteur"`, on a form or a link, says which zones it updates. Here, the form is itself inside a zone: after the note is added, it comes back empty.

## How it works

Nothing hidden, three steps:

1. the `wazi.js` script makes **the same request** the browser would have made (same address, same fields, same protection token);
2. your controller answers **the same page**, whole, as usual;
3. the script keeps only the named zones from that page, and puts them in place of the old ones.

So your controller does not know that zones exist. And if JavaScript is turned off, or if the script is not installed, the form simply reloads the page: everything works anyway.

> Write the ordinary page first, check that it works, then add `k:zone` and `k:update`. You will have nothing to rewrite.

In the page's code, the two attributes become ordinary HTML attributes, which you can see with the browser's inspector: `data-k-zone="liste"` and `data-k-update="liste compteur"`.

## Installing the script

Once per project:

```bash
wazi zones:install
```

The command creates `public/wazi.js`. It is a file of your project, short and commented: open it. Then add a line to your layout, before `</head>`:

```html
<script src="/wazi.js" defer></script>
```

After an update of Wazi, run `wazi zones:install` again: the command replaces the file with its new version. So do not modify it. If you had written a `wazi.js` file yourself, the command recognises it and leaves it alone.

In your project's `wazi` file, the command is declared like the others:

```php
$console->add(new ZonesInstallCommand(__DIR__));
```

## A form

```html
<section k:zone="redaction">
    <p k:if="erreur != null" role="alert">{erreur}</p>

    <form method="post" action="/notes" k:update="redaction, liste">
        <textarea name="texte">{saisie}</textarea>
        <button>Add</button>
    </form>
</section>
```

The controller is the one from the [Forms](09-formulaires.md) page, without one more line:

- the note is accepted: it redirects to `/notes` (303). The script follows the redirect, receives the notes page, and replaces the zones: the list has one more note, the form has come back empty;
- the note is refused: it answers the page with the error message (422). The script replaces the zones in the same way: the message appears, what was typed is still there.

The protection token is in the form, as always: a submission made by the script is checked like an ordinary one.

## A link, a search

```html
<form method="get" action="/notes" k:update="liste">
    <input name="q" type="search" value="{recherche}">
</form>

<nav k:zone="filtres">
    <a href="/notes" k:update="filtres, liste">All</a>
    <a href="/notes?filtre=importantes" k:update="filtres, liste">Pinned</a>
</nav>
```

After a link or a `GET` form, **the address changes** in the browser's bar (`/notes?q=pain`): it can be kept or shared, and it displays the same thing if you reload it. The "Back" button works.

## When the script lets the browser do it

It only replaces zones if it can do so cleanly. Otherwise:

| Situation | What happens |
| --- | --- |
| Click with `Ctrl`, middle click, link with `target` or `download` | The browser opens the link as usual |
| The address belongs to another site | The browser goes there as usual |
| A requested zone is not in the received page (the visitor was redirected to the sign-in page, an error occurred) | The received page is displayed in full |
| The response is not an HTML page, or the network is down | The browser does the submission itself |

## While waiting, and after

While the page is being requested, each zone concerned carries the `aria-busy="true"` attribute. One style rule is enough to show it:

```css
[data-k-zone][aria-busy="true"] {
    opacity: .55;
}
```

After the update, the document receives the `wazi:updated` event. That is where your own script hooks itself back onto what just arrived:

```js
document.addEventListener('wazi:updated', (evenement) => {
    console.log(evenement.detail.zones);   // ['liste', 'compteur']
});
```

The simplest thing is often to have nothing to hook back: listen for clicks on `document` rather than on each button. A button that arrived afterwards is then heard like the others.

```js
document.addEventListener('click', (evenement) => {
    const bouton = evenement.target.closest('.epingle');

    if (bouton) {
        // ...
    }
});
```

## The rules

Kioo checks them when it reads the template, and explains what is wrong.

- A zone name is written **literally**: lowercase letters, digits, hyphens (`liste`, `panier-total`), forty characters at most. Never braces: a value does not choose which piece of the page is replaced.
- `k:zone` goes on a tag that has a beginning and an end: `<div>`, `<section>`, `<ul>`, `<p>`... Not on `<body>`, nor on a tag that carries `k:for`: put it on the tag that wraps the loop.
- A zone name is used only once per page.
- `k:update` goes on a `<form>` or an `<a>`.

## Good to know

- **The whole content of a zone is replaced.** A field being typed in loses what was typed, unless the server writes it back (that is what a refused form does). A search field you want to keep intact goes outside the zone, or carries an `id`: the script puts the cursor back in it.
- **A `<script>` written in a zone does not run** when the zone is replaced: it is a protection of the browser. Use `wazi:updated`.
- **The server computes the whole page** even for a small zone. What you gain: the page is not redrawn, neither its styles nor its scripts are reloaded, and the position in the page is kept.
- **The debug bar follows**: after an update, it describes the last request.
- Zones keep no state in the browser and add no address to your site: there is nothing more to protect. The full decision is in [ADR-036](../decisions/0036-zones-mises-a-jour.md).

## Seeing a complete example

The notes page of [the demo](../../examples/demo/README.md) uses six zones: adding, refusal, search, filters, deletion with confirmation. Open `examples/demo/views/notes/liste.kioo`, then your browser's "Network" tab while you add a note.
