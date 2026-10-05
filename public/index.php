<?php

/**
 * Le point d'entrée de votre site : toutes les requêtes passent par ici.
 *
 * C'est le SEUL fichier PHP du dossier public. Votre application est
 * construite dans app.php, au-dessus, hors de portée d'un navigateur :
 * ce fichier ne fait que la charger et lui demander de répondre.
 */

declare(strict_types=1);

use Wazi\Kernel\Kernel;

// Les classes, chargées par Composer.
require __DIR__ . '/../vendor/autoload.php';

// L'application, puis sa réponse à la requête reçue.
Kernel::load(__DIR__ . '/../app.php')->run();
