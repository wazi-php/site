# 9. Forms

## The journey of a form

It is always the same:

```text
GET  /notes        display the form
POST /notes        receive  →  check  →  save  →  note a message  →  redirect (303)
GET  /notes        display the message, once
```

**Why redirect?** If the response to the `POST` were a page directly, reloading that page would send the form again: the note would be created twice. After a redirect, reloading only reads again.

## Displaying

```html
<form method="post" action="/notes">
    <label for="texte">New note</label>
    <textarea id="texte" name="texte" maxlength="280" required>{saisie}</textarea>
    <button>Add</button>
</form>
```

Look at the page's source code in your browser: Kioo added a hidden `_csrf` field. It is the protection token, explained further down.

## Receiving and checking

```php
use Wazi\Validation\Validator;

#[Post('/notes')]
public function ajouter(ServerRequestInterface $request): ResponseInterface
{
    $v = new Validator($request->getParsedBody());
    $texte = $v->longText('texte', max: 280);

    if ($v->fails()) {
        // Display the form again, with what was typed and what is wrong.
        return $this->kioo->page('notes/liste', ['saisie' => $v->input(), 'erreurs' => $v->errors()], 422);
    }

    $this->carnet->ajouter($texte);
    $this->session->flash('succes', 'Note added.');

    return new Response(303, ['Location' => '/notes']);
}
```

The `Validator` receives the form's fields. Each method does three things: it **reads** a field, **checks** it, and **returns its value in the right type**. If the field does not fit, it notes an error message for it and returns an empty value.

### Only the server's check counts

The `required` and `maxlength` attributes help the visitor, but protect nothing: a form can be sent without a browser, with any content. **Everything received is checked by your code**: presence, type, length, allowed value.

### One method per kind of field

| Method | For | Returns |
| --- | --- | --- |
| `text('nom', max: 80)` | a short text, on one line | `string` |
| `longText('message', max: 2000)` | a text of several lines | `string` |
| `integer('age', min: 18, max: 120)` | a whole number | `int`, or `null` |
| `decimal('prix', min: 0)` | a decimal number (`12,5` or `12.5`) | `float`, or `null` |
| `email('email')` | an email address | `string` |
| `choice('sujet', ['devis', 'question'])` | a value from a list (`<select>`, radio buttons) | `string` |
| `checkbox('conditions')` | a checkbox | `bool` |
| `date('naissance', max: new DateTimeImmutable())` | a date (`<input type="date">`) | `DateTimeImmutable`, or `null` |
| `password('mot_de_passe')` | a password (8 characters at least) | `string` |

A field that is refused or left empty returns `''` for a text, `null` for a number or a date.

### The defaults are strict

With nothing more to write:

- a field is **required**;
- a text is **255 characters at most** (5,000 for `longText()`);
- a short text fits on **a single line**;
- a text is stripped of the whitespace around it.

Whatever is more lenient is written down, and can therefore be seen when reading:

```php
$telephone = $v->text('telephone', required: false);
$article = $v->longText('article', max: 20000);
```

Forgetting a rule gives a form that is too strict, never a form that is too open.

### What is refused outright

- **A field that is not a string.** A field named `nom[]` arrives as an array: it is refused, instead of making your code fail.
- **Control characters and badly encoded text.** They damage pages, logs and files.
- **Malformed numbers.** PHP reads `12abc` as 12 and `1e3` as 1000. Here, only digits make a number.
- **Dates that do not exist.** PHP moves February 31st into March. Here, it is refused.
- **A value outside the list.** The list displayed by a `<select>` protects nothing: `choice()` compares the received value with the list you give it.

### Displaying the errors

`errors()` gives one message per refused field; `input()` gives what the visitor typed, so that they do not have to type it all again.

```html
<label for="nom">Your name</label>
<input id="nom" name="nom" value="{saisie.nom ?? ''}">
<p k:if="(erreurs.nom ?? null) != null" class="erreur" role="alert">{erreurs.nom}</p>
```

The messages are short and addressed to the visitor; they are in French today: « Ce champ est obligatoire. », « Écrivez un nombre entre 18 et 120. ». **They never repeat the received value.** To write your own:

```php
$age = $v->integer('age', min: 18, message: 'You must be an adult to sign up.');
```

### What is never sent back

When a form is displayed again, what was typed is put back, **except a password**. `input()` never contains a field read by `password()`: a password field always comes back empty.

### Your own rules

A rule specific to your application is written in ordinary PHP, with `check()`:

```php
$email = $v->email('email');
$v->check('email', !$this->comptes->existe($email), 'This address is already in use.');

$debut = $v->date('debut');
$fin = $v->date('fin');
$v->check('fin', $debut === null || $fin === null || $fin >= $debut, 'The end comes after the start.');
```

If the condition is false, the message becomes the field's error. The first error of a field is the one displayed.

No rule takes a regular expression: a badly written expression can block a server with a single well-chosen text.

### Only save what has been checked

`values()` gives all the checked values, in their type:

```php
$v->text('nom', max: 80);
$v->email('email');

if (!$v->fails()) {
    $this->comptes->creer($v->values());   // ['nom' => '...', 'email' => '...']
}
```

Only the fields that **you** asked for are in it. A visitor who adds a `role=admin` field to their form by hand will never find it in `values()`. Never save `getParsedBody()` directly.

### Checkboxes

An unchecked box is not sent at all: it is never an error, `checkbox()` simply returns `false`.

```php
$importante = $v->checkbox('importante');
```

```html
<input type="checkbox" name="importante" value="1" checked="{note.importante}">
```

### The parameters of an address

The validator does not know the request: you give it fields. So it checks what follows the "?" of an address just as well:

```php
$v = new Validator($request->getQueryParams());
$page = $v->integer('page', required: false, min: 1) ?? 1;
```

### What it does not do yet

Uploaded files, nested fields (`adresse[ville]`) and lists (`tags[]`) are not checked by the validator.

## Protection against request forgery (CSRF)

### The attack

A visitor is signed in to your site. In another tab, they open a booby-trapped page. That page contains a hidden form that submits itself **to your site**: "delete my account", "change my address". The browser attaches the visitor's cookies to it. For your site, it is the visitor acting.

### The defence

1. Your site gives the browser a random **token**, in a cookie.
2. Each form on **your** pages repeats this token in a hidden field.
3. On reception, the field must equal the cookie.

The booby-trapped page can make the cookie travel, but it cannot read it: it does not know what to write in the field.

### What you have to do: nothing

- Kioo adds the field to every `<form method="post">` sent to your site.
- Wazi checks the token for **every request that changes something** (everything except `GET`, `HEAD` and `OPTIONS`), on every route, with or without sessions.
- A request without the right token receives a 403 response, and your code is not called.

Kioo never adds the token to a form sent to another site, nor to a `GET` form: the token would end up in an address.

### A request sent by JavaScript

There is no form, so no field. The token travels in the `X-CSRF-Token` header. The controller gives it to the page:

```php
use Wazi\Http\CsrfToken;

public function __construct(private readonly Kioo $kioo, private readonly CsrfToken $jeton) {}

// ...
return $this->kioo->page('notes/liste', ['jeton' => $this->jeton->value()]);
```

```html
<meta name="jeton" content="{jeton}">
```

```js
const jeton = document.querySelector('meta[name="jeton"]').content;

await fetch('/notes/3/importante', {
    method: 'PATCH',
    headers: { 'X-CSRF-Token': jeton },
});
```

### A route called by another program

A payment provider notifying your site has neither cookie nor token. You exempt **that route** from the check:

```php
use Wazi\Middleware\WithoutCsrf;

#[Post('/webhooks/paiement', [WithoutCsrf::class])]
public function paiementRecu(ServerRequestInterface $request): ResponseInterface
```

There is no global switch: the exemption is set route by route, where it can be seen.

Exempting a route does not mean opening it to everyone. Check where the request comes from in another way, usually with a **signature**: the sender signs its message with a secret you share.

```php
$attendue = hash_hmac('sha256', (string) $request->getBody(), $this->secret);

if (!hash_equals($attendue, $request->getHeaderLine('X-Signature'))) {
    return new Response(403, ['Content-Type' => 'text/plain; charset=utf-8'], 'Wrong signature.');
}
```

`hash_equals()` compares in constant time: how long the comparison takes reveals nothing about the expected signature. Use it to compare any secret.

### If a form is refused

A 403 response on a form that looks correct to you almost always has one of these causes:

- the form is written in a PHP string, not in a Kioo template: add the field yourself, `<input type="hidden" name="_csrf" value="...">` with the value of `CsrfToken::value()`;
- cookies are blocked in the browser;
- the form's page was cached and served to another visitor: a page that contains a `POST` form does not go into a shared cache.

In development mode, the error page says which of the protections refused the request.

Next: [Errors](10-erreurs.md).
