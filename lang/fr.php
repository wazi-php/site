<?php

/**
 * Les textes de l'interface, en français.
 *
 * lang/en.php contient les mêmes clés, en anglais. Dans un template, un texte
 * s'écrit {t.menu.guide} ; Kioo signale une clé qui n'existe pas.
 *
 * Les pages de la documentation ne sont pas ici : elles sont dans content/.
 */

declare(strict_types=1);

return [
    'site' => [
        'nom' => 'Wazi',
        'slogan' => 'Le framework PHP qui s\'explique',
        'description' => 'Wazi est un framework PHP pour comprendre ce que l\'on construit : pas de magie cachée, des erreurs qui expliquent, la sécurité par défaut.',
        'evitement' => 'Aller au contenu',
    ],

    'menu' => [
        'principal' => 'Navigation principale',
        'guide' => 'Documentation',
        'journal' => 'Journal',
        'github' => 'GitHub',
        'theme' => 'Changer de thème, clair ou sombre',
        'langue' => 'Read this page in English',
        'langue_court' => 'EN',
    ],

    'accueil' => [
        'titre_page' => 'Le framework PHP qui s\'explique',
        'pastille' => 'Framework PHP · libre · en construction',
        'titre' => 'Le framework PHP qui s\'explique.',
        'chapo' => 'Wazi veut dire « clair, ouvert, évident » en swahili. On démarre en cinq minutes, on comprend chaque étape, et on ne bute pas sur un plafond quand le projet grandit.',
        'action_guide' => 'Lire le guide',
        'action_github' => 'Voir le code',
        'vitrine_legende' => 'src/NoteController.php',
        'vitrine_commentaire' => '// Une route, écrite à côté de son code.',

        'piliers_titre' => 'Codez avec l\'IA, comprenez avec Wazi.',
        'piliers_chapo' => 'Les grands frameworks font gagner du temps à ceux qui savent déjà. Wazi fait gagner de la compréhension à ceux qui apprennent.',
        'piliers' => [
            ['libelle' => 'Comprendre', 'titre' => 'Pas de magie cachée', 'texte' => 'Chaque comportement se suit dans l\'éditeur par un clic. Le code du framework est écrit pour être lu, avec des commentaires qui disent pourquoi.'],
            ['libelle' => 'Construire', 'titre' => 'Du premier fichier au vrai projet', 'texte' => 'Une application tient dans un fichier. Elle se range ensuite en contrôleurs, services et templates, sans rien réécrire.'],
            ['libelle' => 'Protéger', 'titre' => 'La sécurité sans y penser', 'texte' => 'Formulaires protégés, pages échappées, requêtes préparées : tout est actif sans configuration. Désactiver une protection demande un geste explicite.'],
        ],

        'pieces_titre' => 'Ce que fait Wazi',
        'pieces_chapo' => 'Chaque pièce a sa page dans le guide.',
        'pieces' => [
            ['page' => '02-routes', 'titre' => 'Routes et contrôleurs', 'texte' => 'Des adresses avec paramètres, des routes écrites à côté de leur code, des dépendances fournies par le conteneur.'],
            ['page' => '06-kioo', 'titre' => 'Templates Kioo', 'texte' => 'Des pages HTML ordinaires, quelques attributs en plus. Tout ce qui s\'affiche est échappé.'],
            ['page' => '09-formulaires', 'titre' => 'Formulaires', 'texte' => 'Protection contre la falsification de requête sans rien écrire, validation champ par champ.'],
            ['page' => '14-base-de-donnees', 'titre' => 'Base de données', 'texte' => 'SQLite, MySQL, PostgreSQL. Du SQL en clair, des valeurs toujours à part, des migrations.'],
            ['page' => '16-zones', 'titre' => 'Zones mises à jour', 'texte' => 'Un formulaire ou un lien ne remplace qu\'un morceau de la page. Le contrôleur ne change pas.'],
            ['page' => '13-console', 'titre' => 'Console', 'texte' => 'Lancer le site, lister les routes, expliquer ce qu\'une adresse traverse, créer un contrôleur.'],
            ['page' => '15-barre-de-debogage', 'titre' => 'Barre de débogage', 'texte' => 'En bas de la page : la route, les templates, les requêtes SQL, la durée. Jamais un secret.'],
            ['page' => '10-erreurs', 'titre' => 'Erreurs pédagogiques', 'texte' => 'Chaque erreur dit ce qui s\'est passé, pourquoi, et comment corriger.'],
        ],

        'erreur_titre' => 'Une erreur qui explique',
        'erreur_chapo' => 'Une faute de frappe dans un template. Wazi dit où, propose la bonne écriture, et rappelle ce qui existe.',
        'erreur_legende' => 'Ce que Wazi répond',
        'erreur_note' => '',

        'console_titre' => 'Une console qui se présente',
        'console_chapo' => 'Chaque commande s\'explique avec --help. « wazi explain » montre ce qu\'une adresse traverse, sans rien exécuter.',
        'console_alt' => 'La console de Wazi : son écran d\'accueil liste les commandes par famille.',
        'demo_titre' => 'Une démonstration à lire',
        'demo_chapo' => 'Un carnet de notes complet, livré avec le framework : connexion, formulaires, base de données, barre de débogage.',
        'demo_alt' => 'L\'application de démonstration : un carnet de notes, avec la barre de débogage en bas de page.',

        'essayer_titre' => 'Essayer',
        'essayer_chapo' => 'Il faut PHP 8.5 et Composer. Wazi sera publié sur Packagist avec la version 0.6 ; d\'ici là, on l\'essaie depuis son dépôt.',
        'essayer_suite' => 'Puis ouvrez la démonstration : trois commandes, et un vrai site tourne sur votre ordinateur.',

        'principes_titre' => 'Huit principes',
        'principes_chapo' => 'Ils tranchent toutes les décisions. Une fonctionnalité qui en viole un est refusée ou repensée.',
        'principes' => [
            ['titre' => 'Transparence avant magie', 'texte' => 'Tout se suit dans l\'éditeur par un clic.'],
            ['titre' => 'Zéro configuration obligatoire', 'texte' => 'Des valeurs par défaut sensées partout.'],
            ['titre' => 'Complexité progressive', 'texte' => 'Un fichier au départ, une application structurée ensuite.'],
            ['titre' => 'Erreurs pédagogiques', 'texte' => 'Quoi, pourquoi, comment corriger.'],
            ['titre' => 'Code source lisible', 'texte' => 'Le cœur reste assez petit pour être lu.'],
            ['titre' => 'PHP moderne uniquement', 'texte' => 'PHP 8.5 au minimum.'],
            ['titre' => 'Standards respectés', 'texte' => 'PSR-7, PSR-11, PSR-15, PSR-17.'],
            ['titre' => 'Sécurité stricte, jamais pénible', 'texte' => 'Le défaut est sûr ; chaque refus explique pourquoi.'],
        ],
    ],

    'guide' => [
        'titre' => 'Documentation',
        'chapo' => 'Le guide, de la première route à la mise en ligne. À lire dans l\'ordre la première fois : chaque page s\'appuie sur les précédentes.',
        'sommaire' => 'Les pages du guide',
        'dans_cette_page' => 'Dans cette page',
        'precedente' => 'Page précédente',
        'suivante' => 'Page suivante',
        'modifier' => 'Proposer une correction sur GitHub',
        'non_traduite' => '',
        'pas_prete_titre' => 'La documentation n\'est pas encore préparée',
        'pas_prete_texte' => 'Lancez la commande « wazi docs:build » dans le dossier du site, puis rechargez cette page.',
    ],

    'journal' => [
        'titre' => 'Journal des modifications',
        'chapo' => 'Ce qui change d\'une version de Wazi à l\'autre.',
        'adresse' => 'journal',
    ],

    'introuvable' => [
        'titre' => 'Page introuvable',
        'texte' => 'Cette adresse ne mène nulle part. La page a peut-être changé de nom.',
        'accueil' => 'Revenir à l\'accueil',
        'guide' => 'Ouvrir le guide',
    ],

    'pied' => [
        'licence' => 'Wazi est un logiciel libre, sous licence MIT.',
        'construit' => 'Ce site est écrit avec Wazi.',
        'source' => 'Son code',
        'securite' => 'Signaler une faille',
        'contribuer' => 'Contribuer',
    ],
];
