<?php

declare(strict_types=1);

namespace Site;

/**
 * Les pages de la documentation, telles que « wazi docs:build » les a préparées.
 *
 * Les textes sont écrits en Markdown, dans content/. Les transformer en HTML à
 * chaque visite serait lent : la commande le fait une fois, et range le
 * résultat dans build/docs/, un fichier PHP par page. Ce service ne fait que
 * les lire.
 *
 *     build/docs/fr/sommaire.php            la liste des pages
 *     build/docs/fr/02-routes.php           une page : son titre, son HTML, ses sections
 *     build/docs/fr/journal.php             le journal des modifications
 *
 * Sécurité : le nom de page reçu dans l'adresse ne sert JAMAIS à construire
 * un chemin de fichier. Il est cherché dans le sommaire, et c'est le sommaire
 * (écrit par nous) qui donne le nom du fichier à lire.
 */
final class Guide
{
    /** @var array<string, list<array{fichier: string, nom: string, titre: string, resume: string, traduite: bool}>> */
    private array $sommaires = [];

    /**
     * @param string $dossier le dossier build/docs, rempli par « wazi docs:build »
     */
    public function __construct(private readonly string $dossier) {}

    /**
     * Faux tant que « wazi docs:build » n'a pas été lancé.
     */
    public function estPrepare(string $langue): bool
    {
        return is_file($this->dossier . '/' . $langue . '/sommaire.php');
    }

    /**
     * Les pages du guide dans une langue, dans l'ordre de lecture.
     *
     * @return list<array{fichier: string, nom: string, titre: string, resume: string, traduite: bool}>
     */
    public function sommaire(string $langue): array
    {
        if (!in_array($langue, Langues::DISPONIBLES, true) || !$this->estPrepare($langue)) {
            return [];
        }

        return $this->sommaires[$langue] ??= self::lireListe($this->dossier . '/' . $langue . '/sommaire.php');
    }

    /**
     * Une page du guide, d'après son nom dans l'adresse (/fr/docs/routes).
     *
     * @return array{fichier: string, nom: string, numero: int, titre: string, html: string, sections: list<array{id: string, texte: string}>, traduite: bool, precedente: ?array{nom: string, titre: string}, suivante: ?array{nom: string, titre: string}}|null null si la page n'existe pas
     */
    public function page(string $langue, string $nom): ?array
    {
        $sommaire = $this->sommaire($langue);

        foreach ($sommaire as $rang => $entree) {
            if ($entree['nom'] !== $nom) {
                continue;
            }

            $page = self::lirePage($this->dossier . '/' . $langue . '/' . $entree['fichier'] . '.php');

            if ($page === null) {
                return null;
            }

            return [
                'fichier' => $entree['fichier'],
                'nom' => $entree['nom'],
                'numero' => $rang + 1,
                'titre' => $page['titre'],
                'html' => $page['html'],
                'sections' => $page['sections'],
                'traduite' => $entree['traduite'],
                'precedente' => self::voisine($sommaire[$rang - 1] ?? null),
                'suivante' => self::voisine($sommaire[$rang + 1] ?? null),
            ];
        }

        return null;
    }

    /**
     * Le nom de la même page dans une autre langue : « routes » en français
     * est « routing » en anglais. C'est ce qui relie les deux versions.
     */
    public function nomDans(string $langue, string $fichier): ?string
    {
        foreach ($this->sommaire($langue) as $entree) {
            if ($entree['fichier'] === $fichier) {
                return $entree['nom'];
            }
        }

        return null;
    }

    /**
     * Le journal des modifications.
     *
     * @return array{titre: string, html: string, sections: list<array{id: string, texte: string}>, traduite: bool}|null null s'il n'a pas été préparé
     */
    public function journal(string $langue): ?array
    {
        if (!in_array($langue, Langues::DISPONIBLES, true)) {
            return null;
        }

        return self::lirePage($this->dossier . '/' . $langue . '/journal.php');
    }

    // ------------------------------------------------------------------
    // Lire les fichiers préparés
    // ------------------------------------------------------------------

    /**
     * @param array{nom: string, titre: string}|null $entree
     *
     * @return array{nom: string, titre: string}|null
     */
    private static function voisine(?array $entree): ?array
    {
        return $entree === null ? null : ['nom' => $entree['nom'], 'titre' => $entree['titre']];
    }

    /**
     * @return list<array{fichier: string, nom: string, titre: string, resume: string, traduite: bool}>
     */
    private static function lireListe(string $fichier): array
    {
        $liste = require $fichier;
        $pages = [];

        foreach (is_array($liste) ? $liste : [] as $entree) {
            if (is_array($entree)
                && is_string($entree['fichier'] ?? null)
                && is_string($entree['nom'] ?? null)
                && is_string($entree['titre'] ?? null)
                && is_string($entree['resume'] ?? null)
            ) {
                $pages[] = [
                    'fichier' => $entree['fichier'],
                    'nom' => $entree['nom'],
                    'titre' => $entree['titre'],
                    'resume' => $entree['resume'],
                    'traduite' => ($entree['traduite'] ?? true) === true,
                ];
            }
        }

        return $pages;
    }

    /**
     * @return array{titre: string, html: string, sections: list<array{id: string, texte: string}>, traduite: bool}|null
     */
    private static function lirePage(string $fichier): ?array
    {
        if (!is_file($fichier)) {
            return null;
        }

        $page = require $fichier;

        if (!is_array($page) || !is_string($page['titre'] ?? null) || !is_string($page['html'] ?? null)) {
            return null;
        }

        $sections = [];

        foreach (is_array($page['sections'] ?? null) ? $page['sections'] : [] as $section) {
            if (is_array($section) && is_string($section['id'] ?? null) && is_string($section['texte'] ?? null)) {
                $sections[] = ['id' => $section['id'], 'texte' => $section['texte']];
            }
        }

        return [
            'titre' => $page['titre'],
            'html' => $page['html'],
            'sections' => $sections,
            'traduite' => ($page['traduite'] ?? true) === true,
        ];
    }
}
