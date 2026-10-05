<?php

declare(strict_types=1);

namespace Site;

use Wazi\Console\Application;
use Wazi\Console\DetailedCommand;
use Wazi\Console\Input;
use Wazi\Console\Output;

/**
 * « wazi docs:build » : prépare les pages de la documentation.
 *
 * Lit les fichiers Markdown de content/, et écrit pour chacun un fichier PHP
 * dans build/docs/ : le site n'a plus qu'à le lire (voir Guide). C'est le
 * même principe que « wazi views:compile » pour les templates : le travail
 * lent est fait une fois, avant, et rien n'est écrit pendant une requête.
 *
 * Une page qui n'existe pas encore en anglais est préparée à partir du
 * français, et marquée « non traduite » : le site l'affiche avec un mot
 * d'explication.
 */
final readonly class DocsBuildCommand implements DetailedCommand
{
    /**
     * @param string $projet le dossier du site : celui qui contient content/ et build/
     */
    public function __construct(private string $projet) {}

    public function name(): string
    {
        return 'docs:build';
    }

    public function description(): string
    {
        return 'Prépare les pages de la documentation à partir des fichiers Markdown de content/.';
    }

    public function arguments(): array
    {
        return [];
    }

    public function options(): array
    {
        return [];
    }

    public function help(): string
    {
        return implode("\n", [
            'Transforme chaque fichier de content/<langue>/guide/ en page prête à afficher,',
            'dans build/docs/. À relancer après avoir modifié un texte, et à chaque mise en ligne.',
            '',
            'Une page absente de content/en/ est préparée à partir du français.',
        ]);
    }

    public function examples(): array
    {
        return ['' => 'Prépare toutes les pages, dans toutes les langues'];
    }

    public function run(Input $input, Output $output): int
    {
        $sommaire = self::sommaire($this->projet . '/content/guide.php');

        if ($sommaire === []) {
            $output->error('Le sommaire content/guide.php est introuvable ou vide. Lancez cette commande depuis le dossier du site.');

            return Application::FAILURE;
        }

        foreach (Langues::DISPONIBLES as $langue) {
            $noms = [];

            foreach ($sommaire as $entree) {
                $noms[$entree['fichier']] = $entree[$langue];
            }

            $markdown = new Markdown($noms, $langue);
            $dossier = $this->projet . '/build/docs/' . $langue;
            $liste = [];
            $nonTraduites = [];

            foreach ($sommaire as $entree) {
                [$source, $traduite] = $this->source($langue, 'guide/' . $entree['fichier'] . '.md');

                if ($source === null) {
                    $output->error('Le fichier content/fr/guide/' . $entree['fichier'] . '.md est introuvable. Lancez « wazi docs:import », ou retirez sa ligne de content/guide.php.');

                    return Application::FAILURE;
                }

                $page = $markdown->page($source, 'docs/guide');

                if (!$traduite) {
                    $nonTraduites[] = $entree['fichier'];
                }

                $liste[] = [
                    'fichier' => $entree['fichier'],
                    'nom' => $entree[$langue],
                    'titre' => $page['titre'],
                    'resume' => $page['resume'],
                    'traduite' => $traduite,
                ];

                if (!self::ecrire($dossier . '/' . $entree['fichier'] . '.php', [
                    'titre' => $page['titre'],
                    'html' => $page['html'],
                    'sections' => $page['sections'],
                    'traduite' => $traduite,
                ])) {
                    return $this->echecEcriture($output);
                }
            }

            [$journal, $journalTraduit] = $this->source($langue, 'journal.md');

            if ($journal !== null) {
                $page = $markdown->page($journal, '');

                if (!self::ecrire($dossier . '/journal.php', [
                    'titre' => $page['titre'],
                    'html' => $page['html'],
                    'sections' => $page['sections'],
                    'traduite' => $journalTraduit,
                ])) {
                    return $this->echecEcriture($output);
                }
            }

            // Le sommaire est écrit en dernier : tant qu'il n'est pas là, le
            // site considère que la documentation n'est pas prête.
            if (!self::ecrire($dossier . '/sommaire.php', $liste)) {
                return $this->echecEcriture($output);
            }

            $output->success($langue . ' : ' . count($liste) . ' pages préparées' . ($journal !== null ? ', et le journal' : '') . '.');

            if ($nonTraduites !== []) {
                $output->note(count($nonTraduites) . ' page(s) pas encore traduite(s), affichée(s) en français : ' . implode(', ', $nonTraduites));
            }
        }

        return Application::SUCCESS;
    }

    // ------------------------------------------------------------------
    // Outils internes
    // ------------------------------------------------------------------

    /**
     * Le texte d'une page dans une langue ; à défaut, celui du français.
     *
     * @return array{?string, bool} le texte (null s'il n'existe dans aucune langue), et vrai s'il est dans la langue demandée
     */
    private function source(string $langue, string $fichier): array
    {
        $traduit = $this->projet . '/content/' . $langue . '/' . $fichier;

        if (is_file($traduit)) {
            $texte = file_get_contents($traduit);

            return [$texte === false ? null : $texte, true];
        }

        $francais = $this->projet . '/content/fr/' . $fichier;
        $texte = is_file($francais) ? file_get_contents($francais) : false;

        return [$texte === false ? null : $texte, false];
    }

    private function echecEcriture(Output $output): int
    {
        $output->error('Un fichier de build/docs/ n\'a pas pu être écrit. Vérifiez que vous avez le droit d\'écrire dans ce dossier.');

        return Application::FAILURE;
    }

    /**
     * Écrit un fichier PHP qui retourne une valeur.
     *
     * var_export() écrit la valeur comme on l'écrirait à la main : le fichier
     * ne contient que des textes, des nombres et des tableaux, aucun code à exécuter.
     *
     * Le fichier est écrit à côté, puis prend sa place d'un seul geste : le
     * site ne lit jamais un fichier à moitié écrit.
     */
    private static function ecrire(string $fichier, mixed $valeur): bool
    {
        $dossier = dirname($fichier);

        if (!is_dir($dossier) && !mkdir($dossier, 0o755, true) && !is_dir($dossier)) {
            return false;
        }

        $provisoire = $fichier . '.' . bin2hex(random_bytes(4)) . '.tmp';
        $contenu = "<?php\n\n// Préparé par « wazi docs:build ». Ne modifiez pas ce fichier : modifiez le texte dans content/.\n\nreturn " . var_export($valeur, true) . ";\n";

        if (file_put_contents($provisoire, $contenu) === false) {
            return false;
        }

        if (@rename($provisoire, $fichier)) {
            return true;
        }

        @unlink($provisoire);

        return false;
    }

    /**
     * @return list<array{fichier: string, fr: string, en: string}>
     */
    private static function sommaire(string $fichier): array
    {
        $lu = is_file($fichier) ? require $fichier : [];
        $sommaire = [];

        foreach (is_array($lu) ? $lu : [] as $entree) {
            if (is_array($entree)
                && is_string($entree['fichier'] ?? null) && preg_match('/^[a-z0-9-]+$/D', $entree['fichier']) === 1
                && is_string($entree['fr'] ?? null) && preg_match('/^[a-z0-9-]+$/D', $entree['fr']) === 1
                && is_string($entree['en'] ?? null) && preg_match('/^[a-z0-9-]+$/D', $entree['en']) === 1
            ) {
                $sommaire[] = ['fichier' => $entree['fichier'], 'fr' => $entree['fr'], 'en' => $entree['en']];
            }
        }

        return $sommaire;
    }
}
