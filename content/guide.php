<?php

/**
 * Le sommaire du guide : ses pages, dans l'ordre de lecture.
 *
 *   - « fichier » est le nom du fichier Markdown, le même dans chaque langue :
 *     content/fr/guide/02-routes.md, content/en/guide/02-routes.md ;
 *   - « fr » et « en » sont le nom de la page dans l'adresse :
 *     /fr/docs/routes, /en/docs/routing.
 *
 * Pour ajouter une page : déposez son fichier dans content/fr/guide/, ajoutez
 * une ligne ici, puis lancez « wazi docs:build ».
 */

declare(strict_types=1);

return [
    ['fichier' => '01-prise-en-main', 'fr' => 'prise-en-main', 'en' => 'getting-started'],
    ['fichier' => '02-routes', 'fr' => 'routes', 'en' => 'routing'],
    ['fichier' => '03-controleurs-et-services', 'fr' => 'controleurs-et-services', 'en' => 'controllers-and-services'],
    ['fichier' => '04-requetes-et-reponses', 'fr' => 'requetes-et-reponses', 'en' => 'requests-and-responses'],
    ['fichier' => '05-middlewares', 'fr' => 'middlewares', 'en' => 'middlewares'],
    ['fichier' => '06-kioo', 'fr' => 'kioo', 'en' => 'kioo'],
    ['fichier' => '07-configuration', 'fr' => 'configuration', 'en' => 'configuration'],
    ['fichier' => '08-sessions', 'fr' => 'sessions', 'en' => 'sessions'],
    ['fichier' => '09-formulaires', 'fr' => 'formulaires', 'en' => 'forms'],
    ['fichier' => '10-erreurs', 'fr' => 'erreurs', 'en' => 'errors'],
    ['fichier' => '11-securite', 'fr' => 'securite', 'en' => 'security'],
    ['fichier' => '12-deploiement', 'fr' => 'mise-en-ligne', 'en' => 'deployment'],
    ['fichier' => '13-console', 'fr' => 'console', 'en' => 'console'],
    ['fichier' => '14-base-de-donnees', 'fr' => 'base-de-donnees', 'en' => 'database'],
    ['fichier' => '15-barre-de-debogage', 'fr' => 'barre-de-debogage', 'en' => 'debug-bar'],
    ['fichier' => '16-zones', 'fr' => 'zones', 'en' => 'zones'],
];
