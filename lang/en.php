<?php

/**
 * Les textes de l'interface, en anglais.
 *
 * Les clés sont celles de lang/fr.php : une clé ajoutée là-bas doit l'être ici.
 */

declare(strict_types=1);

return [
    'site' => [
        'nom' => 'Wazi',
        'slogan' => 'The PHP framework that explains itself',
        'description' => 'Wazi is a PHP framework for understanding what you build: no hidden magic, errors that explain, security by default.',
        'evitement' => 'Skip to content',
    ],

    'menu' => [
        'principal' => 'Main navigation',
        'guide' => 'Documentation',
        'journal' => 'Changelog',
        'github' => 'GitHub',
        'theme' => 'Switch between light and dark theme',
        'langue' => 'Lire cette page en français',
        'langue_court' => 'FR',
    ],

    'accueil' => [
        'titre_page' => 'The PHP framework that explains itself',
        'pastille' => 'PHP framework · open source · in the making',
        'titre' => 'The PHP framework that explains itself.',
        'chapo' => 'Wazi means "clear, open, obvious" in Swahili. Start in five minutes, understand every step, and never hit a ceiling when the project grows.',
        'action_guide' => 'Read the guide',
        'action_github' => 'Browse the code',
        'vitrine_legende' => 'src/NoteController.php',
        'vitrine_commentaire' => '// A route, written next to its code.',

        'piliers_titre' => 'Code with AI, understand with Wazi.',
        'piliers_chapo' => 'The big frameworks save time for people who already know. Wazi builds understanding for people who are learning.',
        'piliers' => [
            ['libelle' => 'Understand', 'titre' => 'No hidden magic', 'texte' => 'Every behaviour can be followed in your editor with a click. The framework\'s code is written to be read, with comments that say why.'],
            ['libelle' => 'Build', 'titre' => 'From one file to a real project', 'texte' => 'An application fits in a single file. It then grows into controllers, services and templates, with nothing to rewrite.'],
            ['libelle' => 'Protect', 'titre' => 'Security without thinking about it', 'texte' => 'Protected forms, escaped pages, prepared queries: all on, with no configuration. Turning a protection off takes an explicit gesture.'],
        ],

        'pieces_titre' => 'What Wazi does',
        'pieces_chapo' => 'Each piece has its page in the guide.',
        'pieces' => [
            ['page' => '02-routes', 'titre' => 'Routes and controllers', 'texte' => 'Addresses with parameters, routes written next to their code, dependencies provided by the container.'],
            ['page' => '06-kioo', 'titre' => 'Kioo templates', 'texte' => 'Ordinary HTML pages with a few extra attributes. Everything displayed is escaped.'],
            ['page' => '09-formulaires', 'titre' => 'Forms', 'texte' => 'Request forgery protection with nothing to write, field-by-field validation.'],
            ['page' => '14-base-de-donnees', 'titre' => 'Database', 'texte' => 'SQLite, MySQL, PostgreSQL. Plain SQL, values always kept apart, migrations.'],
            ['page' => '16-zones', 'titre' => 'Updated zones', 'texte' => 'A form or a link replaces only a piece of the page. The controller does not change.'],
            ['page' => '13-console', 'titre' => 'Console', 'texte' => 'Serve the site, list routes, explain what an address goes through, generate a controller.'],
            ['page' => '15-barre-de-debogage', 'titre' => 'Debug bar', 'texte' => 'At the bottom of the page: the route, templates, SQL queries, timing. Never a secret.'],
            ['page' => '10-erreurs', 'titre' => 'Errors that teach', 'texte' => 'Every error says what happened, why, and how to fix it.'],
        ],

        'erreur_titre' => 'An error that explains',
        'erreur_chapo' => 'A typo in a template. Wazi says where, suggests the right spelling, and lists what exists.',
        'erreur_legende' => 'What Wazi answers',
        'erreur_note' => 'Wazi\'s messages, comments and source documentation are written in French today.',

        'console_titre' => 'A console that introduces itself',
        'console_chapo' => 'Every command explains itself with --help. "wazi explain" shows what an address goes through, without running anything.',
        'console_alt' => 'The Wazi console: its welcome screen lists commands by family.',
        'demo_titre' => 'A demo you can read',
        'demo_chapo' => 'A complete notebook application ships with the framework: sign-in, forms, database, debug bar.',
        'demo_alt' => 'The demo application: a notebook, with the debug bar at the bottom of the page.',

        'essayer_titre' => 'Try it',
        'essayer_chapo' => 'You need PHP 8.5 and Composer. Wazi will be published on Packagist with version 0.6; until then, try it from its repository.',
        'essayer_suite' => 'Then open the demo: three commands, and a real site runs on your computer.',

        'principes_titre' => 'Eight principles',
        'principes_chapo' => 'They settle every decision. A feature that breaks one is refused or rethought.',
        'principes' => [
            ['titre' => 'Transparency over magic', 'texte' => 'Everything can be followed in your editor with a click.'],
            ['titre' => 'No required configuration', 'texte' => 'Sensible defaults everywhere.'],
            ['titre' => 'Progressive complexity', 'texte' => 'One file to begin, a structured application later.'],
            ['titre' => 'Errors that teach', 'texte' => 'What, why, how to fix.'],
            ['titre' => 'Readable source code', 'texte' => 'The core stays small enough to be read.'],
            ['titre' => 'Modern PHP only', 'texte' => 'PHP 8.5 at least.'],
            ['titre' => 'Standards respected', 'texte' => 'PSR-7, PSR-11, PSR-15, PSR-17.'],
            ['titre' => 'Strict security, never painful', 'texte' => 'Safe by default; every refusal explains why.'],
        ],
    ],

    'guide' => [
        'titre' => 'Documentation',
        'chapo' => 'The guide, from the first route to going live. Read it in order the first time: each page builds on the previous ones.',
        'sommaire' => 'Pages of the guide',
        'dans_cette_page' => 'On this page',
        'precedente' => 'Previous page',
        'suivante' => 'Next page',
        'modifier' => 'Suggest a correction on GitHub',
        'non_traduite' => 'This page is not translated yet: here it is in French. The translation is on its way.',
        'pas_prete_titre' => 'The documentation is not prepared yet',
        'pas_prete_texte' => 'Run "wazi docs:build" in the site\'s directory, then reload this page.',
    ],

    'journal' => [
        'titre' => 'Changelog',
        'chapo' => 'What changes from one version of Wazi to the next.',
        'adresse' => 'changelog',
    ],

    'introuvable' => [
        'titre' => 'Page not found',
        'texte' => 'This address leads nowhere. The page may have been renamed.',
        'accueil' => 'Back to the home page',
        'guide' => 'Open the guide',
    ],

    'pied' => [
        'licence' => 'Wazi is free software, under the MIT licence.',
        'construit' => 'This site is written with Wazi.',
        'source' => 'Its code',
        'securite' => 'Report a vulnerability',
        'contribuer' => 'Contribute',
    ],
];
