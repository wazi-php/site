// Le script du site. Il n'apporte que du confort : sans JavaScript, chaque
// lien du guide recharge simplement la page.

// « wazi:updated » : wazi.js vient de remplacer des morceaux de la page (voir
// k:zone et k:update dans views/guide/page.kioo). Quand c'est le texte d'une
// page du guide, on remonte en haut pour la lire depuis le début, et on y
// place le curseur de lecture : un lecteur d'écran annonce le nouveau titre.
document.addEventListener('wazi:updated', (evenement) => {
    if (!evenement.detail.zones.includes('page')) {
        return;
    }

    // « instant » : la feuille de styles demande un défilement doux pour les
    // liens de la page ; ici, on change de page, on y va d'un coup.
    window.scrollTo({ top: 0, behavior: 'instant' });

    const titre = document.querySelector('[data-k-zone="page"] h1');

    if (titre) {
        titre.tabIndex = -1;
        titre.focus({ preventScroll: true });
    }
});
