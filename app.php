<?php

/**
 * Le site de Wazi : ses réglages, ses services, ses routes.
 *
 * Ce fichier CONSTRUIT l'application et la retourne. Il ne répond à aucune requête :
 *   - public/index.php le charge, puis répond à la requête du navigateur ;
 *   - la console (le fichier « wazi ») le charge, pour connaître les routes.
 *
 * Ce site n'a ni formulaire, ni compte, ni base de données : il n'ouvre donc
 * pas de session. Un visiteur ne reçoit aucun cookie.
 */

declare(strict_types=1);

use Site\Guide;
use Site\Langues;
use Site\SiteController;
use Wazi\Config\Config;
use Wazi\Http\ServerRequestCreator;
use Wazi\Kernel\Kernel;
use Wazi\View\Kioo;

// 1. Les réglages : une variable d'environnement du serveur, sinon le fichier
//    .env, sinon la valeur par défaut écrite ici.
$config = Config::fromEnvFile(__DIR__ . '/.env');

// 2. Le noyau, qui assemble les pièces.
$app = new Kernel(
    // Le mode développement affiche le message des erreurs et la barre de
    // débogage. Il n'est jamais deviné : sans réglage, le site est en production.
    development: $config->bool('APP_DEBUG', false),
    requestCreator: new ServerRequestCreator(
        trustedHosts: $config->list('APP_HOSTS', []),
        trustedProxies: $config->list('APP_TRUSTED_PROXIES', []),
    ),
    views: __DIR__ . '/views',
    // Les templates préparés par « wazi views:compile », pour la mise en ligne.
    compiledViews: __DIR__ . '/build/views',
);

// 3. Les services qui ont besoin d'un dossier : on explique au conteneur
//    comment les fabriquer. Le contrôleur, lui, est fabriqué tout seul.
$app->container->set(Langues::class, static fn(): Langues => new Langues(__DIR__ . '/lang'));
$app->container->set(Guide::class, static fn(): Guide => new Guide(__DIR__ . '/build/docs'));

// 4. Ce que TOUTES les pages affichent.
$app->container->get(Kioo::class)->share('annee', (int) date('Y'));

// 5. Les routes : elles sont écrites à côté des méthodes du contrôleur.
//    Pour les voir toutes : « wazi routes ».
$app->router->addController(SiteController::class);

return $app;
