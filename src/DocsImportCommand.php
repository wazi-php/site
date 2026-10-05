<?php

declare(strict_types=1);

namespace Site;

use Wazi\Console\Application;
use Wazi\Console\Argument;
use Wazi\Console\DetailedCommand;
use Wazi\Console\Input;
use Wazi\Console\Output;

/**
 * « wazi docs:import ../wazi » : recopie le guide du framework dans le site.
 *
 * Le guide s'écrit dans le dépôt du framework (docs/guide/), à côté du code
 * qu'il décrit. Le site en garde une copie dans content/fr/ : cette commande
 * la met à jour. On ne modifie donc jamais content/fr/ à la main.
 *
 *     ../wazi/docs/guide/02-routes.md   →   content/fr/guide/02-routes.md
 *     ../wazi/CHANGELOG.md              →   content/fr/journal.md
 *
 * Sécurité : seuls les fichiers dont le nom a la forme « 02-routes.md » sont
 * lus, dans le seul dossier docs/guide du dépôt indiqué, et la commande
 * n'écrit que dans content/fr/.
 */
final readonly class DocsImportCommand implements DetailedCommand
{
    /** 02-routes.md, 14-base-de-donnees.md. */
    private const string PAGE = '/^\d{2}-[a-z0-9]+(?:-[a-z0-9]+)*\.md$/D';

    /**
     * @param string $projet le dossier du site : celui qui contient content/
     */
    public function __construct(private string $projet) {}

    public function name(): string
    {
        return 'docs:import';
    }

    public function description(): string
    {
        return 'Recopie le guide et le journal depuis un dossier du framework, dans content/fr/.';
    }

    public function arguments(): array
    {
        return [new Argument('depot', 'Le dossier du framework sur votre ordinateur : ../wazi')];
    }

    public function options(): array
    {
        return [];
    }

    public function help(): string
    {
        return implode("\n", [
            'Copie docs/guide/*.md et CHANGELOG.md du framework vers content/fr/.',
            'Lancez ensuite « docs:build » pour préparer les pages.',
            '',
            'Une page nouvelle doit aussi recevoir sa ligne dans content/guide.php :',
            'la commande vous le rappelle.',
        ]);
    }

    public function examples(): array
    {
        return ['../wazi' => 'Le framework est dans le dossier voisin'];
    }

    public function run(Input $input, Output $output): int
    {
        $depot = rtrim($input->argument('depot'), '/\\');
        $guide = $depot . '/docs/guide';

        if (!is_dir($guide) || !is_file($depot . '/CHANGELOG.md')) {
            $output->error('Ce dossier ne ressemble pas au dépôt du framework : docs/guide/ ou CHANGELOG.md y manque. Donnez le chemin du dossier « wazi ».');

            return Application::FAILURE;
        }

        $destination = $this->projet . '/content/fr/guide';

        if (!is_dir($destination) && !mkdir($destination, 0o755, true) && !is_dir($destination)) {
            $output->error('Le dossier content/fr/guide/ n\'a pas pu être créé.');

            return Application::FAILURE;
        }

        $connues = self::fichiersDuSommaire($this->projet . '/content/guide.php');
        $copiees = 0;
        $nouvelles = [];

        foreach ((array) scandir($guide) as $fichier) {
            if (!is_string($fichier) || preg_match(self::PAGE, $fichier) !== 1 || !is_file($guide . '/' . $fichier)) {
                continue;
            }

            if (!copy($guide . '/' . $fichier, $destination . '/' . $fichier)) {
                $output->error('Le fichier ' . $fichier . ' n\'a pas pu être copié.');

                return Application::FAILURE;
            }

            $copiees++;

            if (!in_array(substr($fichier, 0, -3), $connues, true)) {
                $nouvelles[] = substr($fichier, 0, -3);
            }
        }

        if (!copy($depot . '/CHANGELOG.md', $this->projet . '/content/fr/journal.md')) {
            $output->error('Le journal des modifications n\'a pas pu être copié.');

            return Application::FAILURE;
        }

        $output->success($copiees . ' pages du guide et le journal copiés dans content/fr/.');

        if ($nouvelles !== []) {
            $output->line();
            $output->note('Pages absentes de content/guide.php, à y ajouter pour qu\'elles apparaissent : ' . implode(', ', $nouvelles));
        }

        $output->line();
        $output->line('Préparez maintenant les pages :');
        $output->line();
        $output->line('    wazi docs:build');

        return Application::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private static function fichiersDuSommaire(string $fichier): array
    {
        $lu = is_file($fichier) ? require $fichier : [];
        $fichiers = [];

        foreach (is_array($lu) ? $lu : [] as $entree) {
            if (is_array($entree) && is_string($entree['fichier'] ?? null)) {
                $fichiers[] = $entree['fichier'];
            }
        }

        return $fichiers;
    }
}
