<?php

declare(strict_types=1);

namespace Site;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Wazi\Http\Response;
use Wazi\Routing\Attribute\Get;
use Wazi\View\Kioo;

/**
 * Les pages du site : l'accueil, la documentation, le journal.
 *
 * Chaque adresse commence par sa langue (/fr, /en). Le paramètre {langue}
 * est vérifié en premier, dans chaque méthode : une langue inconnue donne
 * une page introuvable.
 *
 * Chaque page donne à son template :
 *   - « langue » et « t », les textes de l'interface dans cette langue ;
 *   - « rubrique », pour marquer son lien dans le menu ;
 *   - « adresses », la même page dans chaque langue : c'est ce qui permet au
 *     bouton de langue de mener à la page correspondante, pas à l'accueil.
 */
final readonly class SiteController
{
    public function __construct(private Kioo $kioo, private Langues $langues, private Guide $guide) {}

    /**
     * « / » n'a pas de langue : on envoie le visiteur vers celle que son
     * navigateur annonce.
     */
    #[Get('/')]
    public function racine(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(302, [
            'Location' => '/' . $this->langues->preferee($request->getHeaderLine('Accept-Language')),
            // Dit aux caches que cette réponse dépend de la langue du navigateur.
            'Vary' => 'Accept-Language',
        ]);
    }

    #[Get('/{langue:slug}')]
    public function accueil(ServerRequestInterface $request, string $langue): ResponseInterface
    {
        if (!$this->langues->existe($langue)) {
            return $this->introuvable($request);
        }

        $textes = $this->langues->textes($langue);
        $accueil = is_array($textes['accueil'] ?? null) ? $textes['accueil'] : [];
        $pieces = [];

        // Chaque pièce mène à sa page du guide, dans la langue affichée.
        foreach (is_array($accueil['pieces'] ?? null) ? $accueil['pieces'] : [] as $piece) {
            if (is_array($piece) && is_string($piece['page'] ?? null)) {
                $nom = $this->guide->nomDans($langue, $piece['page']);
                $pieces[] = [...$piece, 'adresse' => '/' . $langue . '/docs' . ($nom !== null ? '/' . $nom : '')];
            }
        }

        $premiere = $this->guide->sommaire($langue)[0]['nom'] ?? null;

        return $this->page('accueil', $langue, 'accueil', ['fr' => '/fr', 'en' => '/en'], [
            'pieces' => $pieces,
            'debut_du_guide' => '/' . $langue . '/docs' . ($premiere !== null ? '/' . $premiere : ''),
        ]);
    }

    #[Get('/{langue:slug}/docs')]
    public function sommaire(ServerRequestInterface $request, string $langue): ResponseInterface
    {
        if (!$this->langues->existe($langue)) {
            return $this->introuvable($request);
        }

        return $this->page('guide/sommaire', $langue, 'guide', ['fr' => '/fr/docs', 'en' => '/en/docs'], [
            'pages' => $this->guide->sommaire($langue),
            'prete' => $this->guide->estPrepare($langue),
        ]);
    }

    #[Get('/{langue:slug}/docs/{nom:slug}')]
    public function pageDuGuide(ServerRequestInterface $request, string $langue, string $nom): ResponseInterface
    {
        if (!$this->langues->existe($langue)) {
            return $this->introuvable($request);
        }

        $page = $this->guide->page($langue, $nom);

        if ($page === null) {
            return $this->introuvable($request, $langue);
        }

        // La même page dans chaque langue : « routes » en français, « routing » en anglais.
        $adresses = [];

        foreach (Langues::DISPONIBLES as $autre) {
            $adresses[$autre] = '/' . $autre . '/docs/' . ($this->guide->nomDans($autre, $page['fichier']) ?? '');
        }

        return $this->page('guide/page', $langue, 'guide', $adresses, [
            'doc' => $page,
            // La mise en page s'élargit : le guide a trois colonnes.
            'large' => true,
            'pages' => $this->guide->sommaire($langue),
            // La page vient du dépôt du framework : c'est là qu'on la corrige.
            'source' => 'https://github.com/wazi-php/wazi/blob/main/docs/guide/' . $page['fichier'] . '.md',
        ]);
    }

    #[Get('/fr/journal')]
    public function journal(ServerRequestInterface $request): ResponseInterface
    {
        return $this->pageDuJournal($request, 'fr');
    }

    #[Get('/en/changelog')]
    public function changelog(ServerRequestInterface $request): ResponseInterface
    {
        return $this->pageDuJournal($request, 'en');
    }

    // ------------------------------------------------------------------
    // Outils internes
    // ------------------------------------------------------------------

    private function pageDuJournal(ServerRequestInterface $request, string $langue): ResponseInterface
    {
        $journal = $this->guide->journal($langue);

        if ($journal === null) {
            return $this->introuvable($request, $langue);
        }

        return $this->page('journal', $langue, 'journal', ['fr' => '/fr/journal', 'en' => '/en/changelog'], ['doc' => $journal, 'large' => true]);
    }

    /**
     * La page « introuvable », dans la langue de l'adresse si elle est connue,
     * sinon dans celle du navigateur.
     */
    private function introuvable(ServerRequestInterface $request, ?string $langue = null): ResponseInterface
    {
        $langue ??= $this->langues->preferee($request->getHeaderLine('Accept-Language'));

        return $this->page('introuvable', $langue, '', ['fr' => '/fr', 'en' => '/en'], [], 404);
    }

    /**
     * @param array<string, string> $adresses  l'adresse de cette page dans chaque langue
     * @param array<string, mixed>  $variables ce que le template affiche en plus
     */
    private function page(string $template, string $langue, string $rubrique, array $adresses, array $variables = [], int $statut = 200): ResponseInterface
    {
        return $this->kioo->page($template, [
            'langue' => $langue,
            'autre_langue' => $this->langues->autre($langue),
            't' => $this->langues->textes($langue),
            'rubrique' => $rubrique,
            'adresses' => $adresses,
            ...$variables,
        ], $statut);
    }
}
