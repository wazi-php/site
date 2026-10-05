// Le thème du site, clair ou sombre.
//
// Sans ce script, tout fonctionne : la feuille de style suit le réglage de
// l'appareil. Le script ajoute un choix : un bouton, dans le bandeau.
//
// Le choix est gardé par le navigateur (localStorage), pas par le serveur :
// il ne concerne que l'affichage, et aucun cookie n'est nécessaire.

// 1. Appliquer le choix déjà fait. Ce script est chargé dans l'en-tête de la
//    page, AVANT son affichage : sans cela, une page sombre apparaîtrait
//    d'abord claire, le temps d'un clignement.
try {
    const choisi = localStorage.getItem('theme');

    if (choisi === 'light' || choisi === 'dark') {
        document.documentElement.dataset.theme = choisi;
    }
} catch {
    // Le navigateur interdit le stockage (navigation privée stricte) : on garde le thème de l'appareil.
}

// 2. Faire fonctionner le bouton, une fois la page lue.
document.addEventListener('DOMContentLoaded', () => {
    const bouton = document.getElementById('theme');

    if (!bouton) {
        return;
    }

    // Le bouton est caché dans le HTML : il n'apparaît que si ce script fonctionne.
    bouton.hidden = false;

    bouton.addEventListener('click', () => {
        const racine = document.documentElement;
        // Le thème affiché : celui qui a été choisi, sinon celui de l'appareil.
        const actuel = racine.dataset.theme ?? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        const suivant = actuel === 'dark' ? 'light' : 'dark';

        racine.dataset.theme = suivant;

        try {
            localStorage.setItem('theme', suivant);
        } catch {
            // Stockage interdit : le choix vaudra pour cette page seulement.
        }
    });
});
