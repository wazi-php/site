<?php

declare(strict_types=1);

namespace Site;

/**
 * Les langues du site, et les textes de son interface.
 *
 * Chaque adresse commence par sa langue : /fr/docs, /en/docs. Les textes de
 * l'interface (menu, boutons, titres) sont dans lang/fr.php et lang/en.php :
 * un tableau par langue, avec les MÊMES clés.
 *
 * Sécurité : la langue demandée dans l'adresse n'est jamais utilisée pour
 * construire un chemin de fichier. Elle est d'abord comparée à la liste
 * fermée ci-dessous ; ce qui n'y est pas donne une page introuvable.
 */
final class Langues
{
    /** Les langues du site. La première est celle qu'on propose par défaut. */
    public const array DISPONIBLES = ['fr', 'en'];

    /** @var array<string, array<string, mixed>> Les textes déjà lus, par langue. */
    private array $textes = [];

    /**
     * @param string $dossier le dossier lang/, qui contient un fichier par langue
     */
    public function __construct(private readonly string $dossier) {}

    public function existe(string $langue): bool
    {
        return in_array($langue, self::DISPONIBLES, true);
    }

    /**
     * L'autre langue : celle vers laquelle pointe le bouton du bandeau.
     */
    public function autre(string $langue): string
    {
        return $langue === 'fr' ? 'en' : 'fr';
    }

    /**
     * Les textes de l'interface dans une langue, rangés par rubrique :
     * ['menu' => ['guide' => 'Documentation', ...], ...].
     *
     * @return array<string, mixed>
     */
    public function textes(string $langue): array
    {
        if (!$this->existe($langue)) {
            throw new \InvalidArgumentException('Cette langue n\'est pas proposée par le site. Les langues disponibles : ' . implode(', ', self::DISPONIBLES) . '.');
        }

        return $this->textes[$langue] ??= self::lire($this->dossier . '/' . $langue . '.php');
    }

    /**
     * La langue à proposer à un visiteur qui arrive sur « / », d'après ce
     * qu'annonce son navigateur (en-tête Accept-Language).
     *
     * On ne lit que les deux premières lettres de chaque langue annoncée, dans
     * l'ordre : « fr-CA,fr;q=0.9,en;q=0.8 » donne « fr ». L'ordre suffit : les
     * navigateurs écrivent leurs langues de la préférée à la moins aimée.
     */
    public function preferee(string $acceptLanguage): string
    {
        foreach (explode(',', strtolower($acceptLanguage)) as $annoncee) {
            $langue = substr(trim($annoncee), 0, 2);

            if ($this->existe($langue)) {
                return $langue;
            }
        }

        return self::DISPONIBLES[0];
    }

    /**
     * @return array<string, mixed>
     */
    private static function lire(string $fichier): array
    {
        $textes = require $fichier;

        if (!is_array($textes)) {
            throw new \RuntimeException('Le fichier ' . basename($fichier) . ' doit retourner un tableau : clé => texte.');
        }

        $verifies = [];

        foreach ($textes as $cle => $texte) {
            if (is_string($cle)) {
                $verifies[$cle] = $texte;
            }
        }

        return $verifies;
    }
}
