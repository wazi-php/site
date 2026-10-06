# 13. The console

The console is what you type in a terminal to act on your project: start the site, display data, and soon generate code.

You type it from your project's directory:

```bash
wazi
```

## Installing the `wazi` command

For your terminal to know the word `wazi`, the command is installed once on your computer, with Composer:

```bash
composer global require wazi/framework
```

> Wazi is not published on Packagist yet: this line will work when it is. Until then, use the form below.

Composer keeps its global commands in a directory of its own. If your terminal answers that `wazi` cannot be found, that directory is not in your `PATH`: `composer global config bin-dir --absolute` gives you its path, to be added to your system's `PATH`.

**Without installing anything**, everything works anyway. Every project contains a `wazi` file, at its root, and you can run it with PHP:

```bash
php wazi
```

Both forms do exactly the same thing: the installed `wazi` command only hands over to the `wazi` file of the project you are in. It is that file which declares the commands, Wazi's and your own.

## The list of commands

With nothing else, the console displays its welcome screen: Wazi's version, then the commands, grouped by family. The console speaks French today.

```text
██╗    ██╗ █████╗ ███████╗██╗
██║    ██║██╔══██╗╚══███╔╝██║
██║ █╗ ██║███████║  ███╔╝ ██║
██║███╗██║██╔══██║ ███╔╝  ██║
╚███╔███╔╝██║  ██║███████╗██║
 ╚══╝╚══╝ ╚═╝  ╚═╝╚══════╝╚═╝

Wazi 0.5.0 · le framework PHP où tout est clair

Utilisation :
  wazi <commande> [arguments] [--options]

Options :
  --help  Explique une commande, sans l'exécuter : wazi serve --help

Commandes :
  explain          Explique ce qu'une adresse traverse : route, middlewares, contrôleur.
  messages         Affiche les messages reçus par le formulaire de contact.
  routes           Liste les routes de l'application : adresse, code exécuté, middlewares.
  serve            Lance le site sur votre ordinateur, pour développer.
 db
  db:migrate       Applique les migrations en attente : crée ou modifie les tables de la base.
  db:status        Montre les migrations faites et celles qui restent à appliquer.
 make
  make:controller  Crée un contrôleur et sa page, commentés et prêts à modifier.
  make:migration   Crée un fichier de migration : un changement de la structure de la base, en SQL.
 views
  views:compile    Prépare les templates à l'avance, pour la mise en ligne.
 zones
  zones:install    Installe public/wazi.js : le script qui met à jour des morceaux de page sans la recharger.

Pour le détail d'une commande : wazi <commande> --help
```

A **family** is what comes before the colon in a command's name: `db:migrate` and `db:status` belong to the `db` family. Name your commands the same way (`facture:envoyer`, `facture:relancer`) and they will be grouped together.

## The detail of a command

Add `--help` to any command. It is not executed: the console says what it does and how to write it.

```bash
wazi explain --help
```

```text
Description :
  Explique ce qu'une adresse traverse : route, middlewares, contrôleur.

Utilisation :
  wazi explain <adresse> [--options]

Arguments :
  adresse  Le chemin à expliquer : /notes/42, ou notes/42

Options :
  --method=…  La méthode de la requête : GET, POST, PUT, PATCH, DELETE (par défaut : GET)
  --help      Affiche cette aide, sans exécuter la commande

Exemples :
  wazi explain notes/3                Ce que traverse GET /notes/3
  wazi explain contact --method=POST  Ce que traverse l'envoi du formulaire de contact

Aide :
  Montre la route choisie, les étapes traversées dans l'ordre, le code exécuté
  et d'où vient chacun de ses arguments.
  ...
```

An argument between `<` and `>` is required; between square brackets, it is optional.

## Starting the site

```bash
wazi serve
```

The site is served at http://localhost:8000. To stop it: `Ctrl+C`.

```bash
wazi serve --port=8080
```

This is the development server that ships with PHP. It is for developing, not for receiving visitors: see [Going live](12-deploiement.md).

It only listens on `localhost`: only your computer can reach it. To show it to another device on the network (a phone, for testing), you have to ask for it, and the console warns you:

```bash
wazi serve --host=192.168.1.20
```

## Seeing your routes

```bash
wazi routes
```

```text
4 route(s), dans l'ordre où le routeur les essaie

GET   /          App\PageController::accueil
GET   /a-propos  App\PageController::aPropos
GET   /contact   App\ContactController::formulaire
POST  /contact   App\ContactController::envoyer
```

For each route: the method, the address, the code that runs, and the middlewares placed on it. The order is that of your declarations, and therefore the order in which the router tries them: if an address with a parameter hides another one, it shows here.

The command reads the application built by `app.php`, the same one the site serves.

## Understanding an address

When a page does not answer as expected, ask Wazi what its address goes through:

```bash
wazi explain notes/3
```

```text
GET /notes/3

1. La route choisie

   GET /notes/{id:int}   (la 8e des 12 route(s) déclarée(s))
   {id} = 3   (un nombre entier)

2. Ce que la requête traverse, dans l'ordre

    1. SecurityHeaders               ajoute les en-têtes de sécurité à la réponse
    2. CsrfCookie                    lit le cookie du jeton des formulaires, et l'envoie si une page en a besoin
    3. SessionMiddleware             retrouve la session du visiteur, et l'enregistre au retour
    4. le routeur                    choisit la route ci-dessus
    5. ConnexionRequise              Le middleware : un garde placé devant les routes réservées aux visiteurs connectés.
    6. CsrfProtection                ne demande rien : GET ne fait que lire
    7. Demo\NoteController::voir()   votre code : il retourne la réponse

   La réponse repasse ensuite par les mêmes étapes, en sens inverse.

3. Le code exécuté

   Demo\NoteController::voir()
   …/src/NoteController.php, ligne 70

   Le conteneur fabrique le contrôleur, et lui fournit ce que son constructeur demande :
     Demo\Carnet $carnet
     Wazi\View\Kioo $kioo

   Les arguments de la méthode :
     int $id   ← le paramètre {id} de la route : 3
```

For a method other than GET:

```bash
wazi explain notes --method=POST
```

What the command teaches you:

- **which route is chosen**, and the value of its parameters. If the address also fits a route declared further down, it warns you: the first declared wins, the other is never reached;
- **each step it goes through**, in order. For your own middlewares, it quotes the first sentence of their class comment: comment them, and the explanation speaks with your words;
- **which code runs**, where it is written, and where each of its arguments comes from;
- **why an address does not answer**: 404 (no route), 405 (not for this method), or address refused as a protection.

It **runs nothing**: neither middleware nor controller. You can explain `--method=DELETE` without deleting anything. In return, it does not say what one of your middlewares will decide (let through or refuse): that depends on the real request.

The address is written with or without the leading slash. On Windows, the Git Bash terminal turns anything starting with `/` into a file path: write `notes/3`.

## Preparing the templates for going live

```bash
wazi views:compile
```

This command parses all your templates once and for all, so that they are no longer parsed on every request. It is run at deployment, not while you develop. It is explained in [Going live](12-deploiement.md).

## Building the database

```bash
wazi make:migration creer_notes    # creates an SQL file, to be filled in
wazi db:migrate                    # applies the pending migrations
wazi db:status                     # shows where each migration stands
```

These three commands are explained in [The database](14-base-de-donnees.md).

## Creating a controller

```bash
wazi make:controller Article
```

```text
OK  Créé : src/ArticleController.php
OK  Créé : views/article.kioo

Il reste une ligne à ajouter dans app.php, avec les autres contrôleurs :

    $app->router->addController(\App\ArticleController::class);

Puis ouvrez /article dans votre navigateur.
```

The command creates two files: the controller, with a route, and the template of its page. If your project has a `views/base.kioo` layout, the page goes into it.

The created code is **commented**: each line says what it does. Once you know these lines, ask for it without the explanations:

```bash
wazi make:controller Article --no-comments
```

Three rules, so that it cannot damage anything:

- **it never replaces an existing file.** If one of the two already exists, it creates nothing and tells you;
- **it does not modify `app.php`.** It gives you the line to add there: no route appears without you having declared it;
- **the name is a class name**: a capital letter, then unaccented letters and digits. `BlogPost` gives the address `/blog-post`.

## How a command is written

A single form, for all commands:

```text
wazi <command> <argument> --option=value --flag
```

| Form | Meaning |
| --- | --- |
| `value` | an argument, in the order the command expects |
| `--port=8080` | an option, and its value joined by an equals sign |
| `--force` | a flag: present or absent |
| `--help` | the command's help, valid everywhere |

There is no short form (`-p`), and no value separated by a space. A command refuses everything it does not expect, and says what to write instead:

```text
Erreur  La commande « serv » n'existe pas. Vouliez-vous écrire « serve » ?
```

## Writing your own command

A command is a class that implements `Command`. It declares its name, its description, what it accepts, and what it does:

```php
namespace App;

use Wazi\Console\Argument;
use Wazi\Console\Command;
use Wazi\Console\Input;
use Wazi\Console\Option;
use Wazi\Console\Output;

final readonly class BonjourCommand implements Command
{
    public function name(): string
    {
        return 'bonjour';
    }

    public function description(): string
    {
        return 'Greets someone.';
    }

    public function arguments(): array
    {
        return [new Argument('nom', 'Who to greet')];
    }

    public function options(): array
    {
        return [
            new Option('fort', 'Write in capital letters'),      // a flag
            new Option('fois', 'How many times', '1'),           // an option, with its default value
        ];
    }

    public function run(Input $input, Output $output): int
    {
        $texte = 'Hello ' . $input->argument('nom') . '!';

        for ($tour = 0; $tour < (int) $input->option('fois'); $tour++) {
            $output->line($input->flag('fort') ? mb_strtoupper($texte) : $texte);
        }

        return 0;
    }
}
```

Then declare it in your project's `wazi` file, next to the others:

```php
$console->add(new BonjourCommand());
```

If your command needs a service, ask the application's container for it, as for a controller:

```php
$console->add(new MessagesCommand($app->container->get(Messagerie::class)));
```

```bash
wazi bonjour Alice --fort --fois=2
```

The starter project contains a complete example, `src/MessagesCommand.php`.

### Arguments and options

| Declaration | To type | To read in `run()` |
| --- | --- | --- |
| `new Argument('nom', '…')` | `Alice` (required) | `$input->argument('nom')` |
| `new Argument('formule', '…', 'Bonjour')` | `Salut` (optional) | `$input->argument('formule')` |
| `new Option('fois', '…', '1')` | `--fois=3` | `$input->option('fois')` |
| `new Option('fort', '…')` | `--fort` | `$input->flag('fort')` |

Everything typed arrives as a **string**. As for a form, check it before using it:

```php
if (!ctype_digit($input->option('fois'))) {
    $output->error('The --fois option expects a number, for instance --fois=3.');

    return 2;
}
```

### Examples and a help text

For `wazi bonjour --help` to also show examples and a help text, the command implements `DetailedCommand` instead of `Command`, and adds two methods:

```php
use Wazi\Console\DetailedCommand;

final class BonjourCommand implements DetailedCommand
{
    // name(), description(), arguments(), options() and run(): as before.

    public function help(): string
    {
        return "Greets the named person.\nWith --fort, the text is written in capital letters.";
    }

    public function examples(): array
    {
        // What is typed after the command's name => what it does.
        return [
            'Alice' => 'Greets Alice',
            'Alice --fort' => 'The same thing, in capital letters',
        ];
    }
}
```

This is optional: a command that only implements `Command` works exactly the same, with a shorter help.

### The exit code

`run()` returns a number, which the terminal and automation tools read to know whether the command succeeded:

| Code | Meaning |
| --- | --- |
| `0` | everything went well |
| `1` | the command failed |
| `2` | the command was badly written |

### Writing to the terminal

| Method | Use |
| --- | --- |
| `$output->line('…')` | an ordinary line |
| `$output->title('…')` | a title |
| `$output->success('…')` | what succeeded |
| `$output->warning('…')` | what deserves attention |
| `$output->error('…')` | what failed (written to the error output) |
| `$output->definitions([...])` | a two-column list, aligned |
| `$output->section('…')` | the name of a section: "Options:" |
| `$output->accent('…')` | a highlighted line, in Wazi's colour |
| `$output->note('…')` | a discreet line: a detail, a reminder |

Colour comes from these methods. It is only used in a terminal: redirecting the output to a file gives plain text.

## What the console does for you

- **It only runs in a terminal.** Called by a web server, it refuses: it would give a visitor the developer's powers. The `wazi` file is at the project's root, outside the `public/` directory.
- **What it displays is cleaned.** A value that came from a visitor (a message, an address) can contain sequences that clear the screen or trap the terminal. `Output` removes them from everything it writes: you do not have to think about it.
- **An error never displays a trace**: it would contain the values passed to functions, sometimes secrets. You see the message, the kind of error, the file and the line.

## The limits

The console is in its early days. It has no interactive input (asking a question) yet. Other generators (middleware, command) are planned.

An error in `app.php` (a missing setting, a badly written route) prevents the console from starting, whatever the command. It tells you which one, and where.

Next: [The database](14-base-de-donnees.md).
