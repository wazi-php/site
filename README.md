# Le site de Wazi

Le site qui présente [Wazi](https://github.com/wazi-php/wazi), le framework PHP qui s'explique, et sa documentation. En français et en anglais.

Il est écrit **avec Wazi** : c'est aussi un exemple de vrai site, à lire comme les autres.

## Le lancer sur votre ordinateur

Il faut PHP 8.5 et Composer.

```bash
git clone https://github.com/wazi-php/site.git
cd site
composer install
php wazi docs:build     # prépare les pages de la documentation
php wazi serve
```

Puis ouvrez http://localhost:8000.

## Ce que contient le dossier

```text
site/
├── app.php            L'application : réglages, services, routes
├── wazi               La console : charge app.php et exécute une commande
├── content/           Les textes : la documentation, en Markdown, par langue
├── lang/              Les textes de l'interface, par langue
├── public/            Le seul dossier visible depuis un navigateur
├── src/               Le code du site
├── views/             Les templates Kioo
└── build/             Ce que les commandes préparent (non suivi par Git)
```

## La documentation

Les pages du guide sont écrites en Markdown :

- `content/fr/guide/` est une copie du guide du framework (`docs/guide/` dans son dépôt). On ne la modifie pas ici : on corrige le guide dans le dépôt du framework, puis on la recopie avec `php wazi docs:import ../wazi`.
- `content/en/guide/` contient les traductions, sous le même nom de fichier ; `content/en/journal.md` est le journal en anglais. Une page pas encore traduite s'affiche en français, avec un mot d'explication.

Quand une page change dans le guide du framework, sa traduction est à reprendre ici, dans la même demande de fusion que le `docs:import`. Une traduction garde les extraits de code et les liens de l'original : seuls le texte et les commentaires changent.

`php wazi docs:build` transforme ces fichiers en pages prêtes à afficher, dans `build/docs/`. À relancer après chaque modification d'un texte.

## Contribuer

Le site suit les règles du framework : une branche par sujet, une demande de fusion, jamais de commit direct sur `main`. Voir le [guide de contribution](https://github.com/wazi-php/wazi/blob/main/CONTRIBUTING.md).

## Licence

[MIT](LICENSE)
