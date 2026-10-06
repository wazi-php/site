# 8. Sessions

The web has no memory: each request arrives on its own. To recognise a visitor from one page to the next, you give them a random **identifier**, stored in a cookie. They send it back with every request, and the server finds what it had noted for them: that is the **session**.

## Turning sessions on

Tell the kernel where to store them, **outside the public directory**:

```php
$app = new Kernel(sessions: __DIR__ . '/../var/sessions');
```

The directory is created if it does not exist. A directory placed in `public/` is refused: the sessions could be downloaded from there.

## Noting and reading back

Ask for the session in the constructor:

```php
use Wazi\Http\Session;

final class PanierController
{
    public function __construct(private readonly Session $session) {}

    #[Post('/panier/{id:int}')]
    public function ajouter(int $id): ResponseInterface
    {
        $panier = (array) $this->session->get('panier', []);
        $panier[] = $id;
        $this->session->set('panier', $panier);

        return new Response(303, ['Location' => '/panier']);
    }
}
```

| Method | Role |
| --- | --- |
| `get('key', $default)` | Read a value back |
| `has('key')` | Know whether it exists |
| `set('key', $value)` | Note a value |
| `remove('key')` | Forget it |
| `clear()` | Forget everything: that is signing out |

### What a session can keep

**Simple** values: strings, numbers, true/false, `null`, and arrays that contain nothing else. To remember an object, keep its identifier:

```php
$this->session->set('utilisateur_id', $utilisateur->id);
```

Keys starting with `_` are reserved for Wazi.

What you read back from a session has an unknown type for PHP: check it (`is_string()`, `is_int()`) before using it.

### Reading creates nothing

A visitor who only reads the site receives **no cookie** and creates **no file**. The session only starts to exist when your code notes something in it.

Keep this in mind: each write for an unknown visitor creates a file. Do not write to an anonymous visitor's session without a reason, above all on a page that a robot can call a thousand times.

## Signing a visitor in

Wazi does not provide ready-made authentication yet. A sign-in is built like this:

```php
#[Post('/connexion')]
public function connecter(ServerRequestInterface $request): ResponseInterface
{
    $v = new Validator($request->getParsedBody());
    $nom = $v->text('nom', max: 80);
    // min: 1: when signing in, you compare the password, you do not judge its strength.
    $motDePasse = $v->password('mot_de_passe', min: 1);

    if ($v->fails() || !$this->comptes->verifier($nom, $motDePasse)) {
        return $this->kioo->page('connexion', ['erreur' => 'Wrong name or password.'], 422);
    }

    $this->session->regenerate();
    $this->session->set('utilisateur', $nom);

    return new Response(303, ['Location' => '/']);
}
```

Three security points, all in these few lines:

- **`regenerate()` right after a successful sign-in.** It changes the session's identifier while keeping its content. If someone had managed to force their identifier onto the visitor's browser before they signed in, that identifier is now worthless.
- **The message does not say** whether it is the name or the password that is wrong: that would reveal which accounts exist.
- **Passwords are never kept as they are.** You keep their hash, computed by `password_hash()`, and compare with `password_verify()`.

To protect pages, a middleware checks the session: see [Middlewares](05-middlewares.md). The demo shows the whole thing in [`ConnexionController.php`](../../examples/demo/src/ConnexionController.php).

Signing out:

```php
#[Post('/deconnexion')]
public function deconnecter(): ResponseInterface
{
    $this->session->clear();

    return new Response(303, ['Location' => '/']);
}
```

Signing out is a `POST` form, not a link: it changes something.

## A message for the next page

After a successful form, you redirect the visitor. The message "Note added" must survive the redirect, then disappear:

```php
$this->session->flash('succes', 'Note added.');

return new Response(303, ['Location' => '/notes']);
```

On the next page:

```php
$message = $this->session->takeFlash('succes');   // "Note added.", then null on later reads
```

`takeFlash()` returns the message **and erases it**: it is displayed only once. As long as no page reads it, it waits.

## "Remember me"

By default, a session disappears when the browser closes, or after two hours without a visit. To make it last:

```php
if (($formulaire['se_souvenir'] ?? null) === '1') {
    $this->session->remember(30);   // 30 days after the last visit
}
```

The duration goes from 1 to 365 days. Each visit pushes the deadline back; `clear()` ends it.

Only call `remember()` if the visitor asked for it, with a checkbox: a long session stays open on the device where it was created, including a shared computer. Before a sensitive action (changing a password, paying), ask for the password again.

## Two requests at the same time

A visitor can send two requests at once: two tabs, a page that sends JavaScript requests. Wazi handles them **one after the other**: the second waits until the first has saved the session. Otherwise, the last one would erase what the other noted.

One consequence: a slow page makes the same visitor's other requests wait. If the wait exceeds ten seconds, an error explains it.

## What Wazi does for you

- The identifier is 256 bits of randomness: impossible to guess.
- The cookie is `HttpOnly` (the page's JavaScript cannot read it), `SameSite=Lax` (it does not travel with a form sent from another site) and `Secure` when the site uses HTTPS.
- An identifier that the server did not create is never adopted.
- The content is written as JSON: no object is ever rebuilt from a session file.
- The files can only be read by the account that runs PHP; expired sessions are deleted.

## The limits

- Sessions are stored in **files**, on a single server. A site spread over several servers will need another storage, in a database (not done yet).
- The number of sign-in attempts is not limited by Wazi: it is up to your application to slow down someone who tries thousands of passwords.

Next: [Forms](09-formulaires.md).
