<?php

declare(strict_types=1);

namespace Site;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;
use Tempest\Highlight\Highlighter;

/**
 * Transforme une page écrite en Markdown en page prête à afficher.
 *
 * Utilisé par la commande « wazi docs:build », jamais pendant une requête.
 *
 *     texte Markdown
 *          │  league/commonmark : le transforme en HTML
 *          ▼
 *     HTML brut
 *          │  ce fichier : relit le HTML comme un navigateur, puis
 *          │    - sort le titre de la page ;
 *          │    - donne un nom à chaque section (pour le sommaire de droite) ;
 *          │    - colore les extraits de code ;
 *          │    - corrige les liens : une page du guide pointe vers le site,
 *          │      le reste vers le dépôt du framework ;
 *          ▼
 *     titre, HTML, sections, résumé
 *
 * Sécurité : le HTML écrit dans un fichier Markdown est échappé, et un lien
 * dangereux (javascript:) est retiré. Les textes viennent de nos dépôts, mais
 * une demande de fusion peut en proposer : ils ne doivent rien pouvoir exécuter.
 */
final class Markdown
{
    /** Là où se trouvent, dans le dépôt du framework, les fichiers vers lesquels un lien relatif pointe. */
    private const string DEPOT = 'https://github.com/wazi-php/wazi/blob/main/';

    /** Les lettres accentuées, remplacées dans le nom d'une section. */
    private const array SANS_ACCENT = [
        'à' => 'a', 'â' => 'a', 'ä' => 'a', 'ç' => 'c', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ö' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'œ' => 'oe',
    ];

    private readonly MarkdownConverter $convertisseur;

    private readonly Highlighter $colorieur;

    /**
     * @param array<string, string> $pages les pages du guide dans la langue préparée : nom du fichier (sans .md) => nom dans l'adresse
     * @param string                $langue la langue préparée : « fr », « en »
     */
    public function __construct(private readonly array $pages, private readonly string $langue)
    {
        $environnement = new Environment([
            // Une balise écrite dans le Markdown s'affiche, elle ne s'exécute pas.
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);
        $environnement->addExtension(new CommonMarkCoreExtension());
        $environnement->addExtension(new TableExtension());

        $this->convertisseur = new MarkdownConverter($environnement);
        $this->colorieur = new Highlighter();
    }

    /**
     * @param string $source  le texte Markdown
     * @param string $dossier le dossier du fichier dans le dépôt du framework (« docs/guide », ou « » pour la racine) : il sert à corriger les liens relatifs
     *
     * @return array{titre: string, html: string, sections: list<array{id: string, texte: string}>, resume: string}
     */
    public function page(string $source, string $dossier): array
    {
        $html = $this->convertisseur->convert($source)->getContent();

        // On relit le HTML comme le ferait un navigateur : c'est plus sûr que
        // de chercher des balises dans du texte.
        $document = \Dom\HTMLDocument::createFromString('<!DOCTYPE html><html><body>' . $html . '</body></html>', LIBXML_NOERROR);
        $corps = $document->body;

        if ($corps === null) {
            return ['titre' => '', 'html' => '', 'sections' => [], 'resume' => ''];
        }

        $titre = $this->sortirLeTitre($corps);
        $sections = $this->nommerLesSections($corps);
        $this->colorerLeCode($corps);
        $this->corrigerLesLiens($corps, $dossier);
        $this->entourerLesTableaux($document, $corps);

        return [
            'titre' => $titre,
            'html' => $corps->innerHTML,
            'sections' => $sections,
            'resume' => self::resume($corps),
        ];
    }

    // ------------------------------------------------------------------
    // Les étapes
    // ------------------------------------------------------------------

    /**
     * Le titre « # 2. Les routes » quitte le texte : la mise en page l'affiche
     * elle-même, sans son numéro.
     */
    private function sortirLeTitre(\Dom\Element $corps): string
    {
        $titre = $corps->querySelector('h1');

        if ($titre === null) {
            return '';
        }

        $texte = trim((string) preg_replace('/^\d+\.\s*/', '', trim($titre->textContent ?? '')));
        $titre->remove();

        return $texte;
    }

    /**
     * Chaque titre de section reçoit un nom (id), pour qu'un lien puisse y
     * mener : /fr/docs/routes#les-contraintes.
     *
     * @return list<array{id: string, texte: string}> les sections de premier niveau, pour le sommaire de la page
     */
    private function nommerLesSections(\Dom\Element $corps): array
    {
        $sections = [];
        $pris = [];

        foreach ($corps->querySelectorAll('h2, h3') as $titre) {
            $texte = trim($titre->textContent ?? '');
            $id = self::nomDeSection($texte);

            // Deux sections de même titre : la seconde s'appelle « exemple-2 ».
            for ($suffixe = 2; isset($pris[$id]); $suffixe++) {
                $id = self::nomDeSection($texte) . '-' . $suffixe;
            }

            $pris[$id] = true;
            $titre->setAttribute('id', $id);

            if ($titre->tagName === 'H2') {
                $sections[] = ['id' => $id, 'texte' => $texte];
            }
        }

        return $sections;
    }

    /**
     * Un extrait écrit entre ```php et ``` est coloré : chaque mot du langage
     * est entouré d'une balise <span class="hl-…">, que app.css met en couleur.
     */
    private function colorerLeCode(\Dom\Element $corps): void
    {
        foreach ($corps->querySelectorAll('pre > code') as $code) {
            $bloc = $code->parentElement;

            if ($bloc === null) {
                continue;
            }

            $langage = 'text';

            if (preg_match('/(?:^|\s)language-([a-z]+)/', $code->getAttribute('class') ?? '', $trouve) === 1) {
                $langage = $trouve[1];
            }

            $bloc->setAttribute('data-langage', $langage);

            // « text » : rien à colorer. Le texte est déjà échappé.
            if ($langage === 'text') {
                continue;
            }

            try {
                // parse() rend du HTML : le code échappé, et les balises de couleur.
                $code->innerHTML = $this->colorieur->parse($code->textContent ?? '', $langage);
            } catch (\Throwable) {
                // Un langage que la bibliothèque ne connaît pas : l'extrait reste tel quel.
            }
        }
    }

    /**
     * Les liens écrits pour être lus sur GitHub sont corrigés pour le site :
     *
     *     02-routes.md              →  /fr/docs/routes
     *     ../decisions/0036-….md    →  le fichier, dans le dépôt du framework
     *     https://…                 →  inchangé, ouvert sans donner d'information au site visité
     */
    private function corrigerLesLiens(\Dom\Element $corps, string $dossier): void
    {
        foreach ($corps->querySelectorAll('a[href]') as $lien) {
            $adresse = $lien->getAttribute('href') ?? '';

            if (str_starts_with($adresse, '#')) {
                continue;
            }

            if (preg_match('#^https?://#', $adresse) === 1) {
                $lien->setAttribute('rel', 'noopener noreferrer');

                continue;
            }

            if (str_contains($adresse, ':') || str_starts_with($adresse, '/')) {
                // Ni page du guide, ni adresse web : on ne garde que le texte du lien.
                $lien->removeAttribute('href');

                continue;
            }

            [$chemin, $ancre] = array_pad(explode('#', $adresse, 2), 2, '');

            if (preg_match('#^([a-z0-9-]+)\.md$#', $chemin, $trouve) === 1 && isset($this->pages[$trouve[1]])) {
                $lien->setAttribute('href', '/' . $this->langue . '/docs/' . $this->pages[$trouve[1]] . ($ancre !== '' ? '#' . $ancre : ''));

                continue;
            }

            $lien->setAttribute('href', self::DEPOT . self::simplifier($dossier . '/' . $chemin) . ($ancre !== '' ? '#' . $ancre : ''));
            $lien->setAttribute('rel', 'noopener noreferrer');
        }
    }

    /**
     * Un tableau large déborde sur un petit écran : entouré d'un bloc, c'est
     * ce bloc qui défile, pas la page.
     */
    private function entourerLesTableaux(\Dom\HTMLDocument $document, \Dom\Element $corps): void
    {
        foreach ($corps->querySelectorAll('table') as $tableau) {
            $bloc = $document->createElement('div');
            $bloc->setAttribute('class', 'tableau');
            $tableau->replaceWith($bloc);
            $bloc->appendChild($tableau);
        }
    }

    // ------------------------------------------------------------------
    // Outils
    // ------------------------------------------------------------------

    /**
     * Le premier paragraphe, en texte : il présente la page dans le sommaire.
     */
    private static function resume(\Dom\Element $corps): string
    {
        $texte = trim((string) preg_replace('/\s+/', ' ', $corps->querySelector('p')->textContent ?? ''));

        return mb_strlen($texte) > 200 ? rtrim(mb_substr($texte, 0, 197)) . '…' : $texte;
    }

    /**
     * « Les contraintes de route » → « les-contraintes-de-route ».
     */
    private static function nomDeSection(string $titre): string
    {
        $nom = strtr(mb_strtolower($titre), self::SANS_ACCENT);
        $nom = trim((string) preg_replace('/[^a-z0-9]+/', '-', $nom), '-');

        return $nom !== '' ? $nom : 'section';
    }

    /**
     * « docs/guide/../decisions/x.md » → « docs/decisions/x.md ».
     */
    private static function simplifier(string $chemin): string
    {
        $morceaux = [];

        foreach (explode('/', $chemin) as $morceau) {
            if ($morceau === '..') {
                array_pop($morceaux);
            } elseif ($morceau !== '' && $morceau !== '.') {
                $morceaux[] = $morceau;
            }
        }

        return implode('/', $morceaux);
    }
}
