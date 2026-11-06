<?php
/*
 * Panneau « Accessibilité » (offcanvas Bootstrap) ouvert par le bouton de la barre du haut.
 * Les boutons sont gérés par public/assets/js/accessibility.js.
 */
$a11yToggles = [
    'contrast' => ['Contraste renforcé', 'Texte plus foncé, fonds unis et bordures marquées.'],
    'readable' => ['Lecture facilitée', 'Plus d’espace entre les lettres, les mots et les lignes (dyslexie).'],
    'links' => ['Liens soulignés', 'Tous les liens sont repérables sans la couleur.'],
    'motion' => ['Animations réduites', 'Supprime les effets de mouvement et de transition.'],
];
?>
<div class="offcanvas offcanvas-end a11y-panel" tabindex="-1" id="a11yPanel" aria-labelledby="a11yPanelTitle">
    <div class="offcanvas-header">
        <h2 class="offcanvas-title h5" id="a11yPanelTitle">Accessibilité</h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fermer le panneau d’accessibilité"></button>
    </div>
    <div class="offcanvas-body">
        <p class="small text-muted">Adaptez l’affichage à vos besoins. Vos choix sont gardés sur cet appareil.</p>

        <fieldset class="mb-4">
            <legend class="h6">Taille du texte</legend>
            <div class="a11y-text-sizes" role="group" aria-label="Taille du texte">
                <button type="button" class="btn btn-outline-primary" data-a11y-text="0" aria-pressed="true"><span aria-hidden="true">A</span><span class="visually-hidden">Taille normale</span></button>
                <button type="button" class="btn btn-outline-primary" data-a11y-text="1" aria-pressed="false"><span aria-hidden="true">A+</span><span class="visually-hidden">Texte agrandi</span></button>
                <button type="button" class="btn btn-outline-primary" data-a11y-text="2" aria-pressed="false"><span aria-hidden="true">A++</span><span class="visually-hidden">Texte grand</span></button>
                <button type="button" class="btn btn-outline-primary" data-a11y-text="3" aria-pressed="false"><span aria-hidden="true">A+++</span><span class="visually-hidden">Texte très grand</span></button>
            </div>
        </fieldset>

        <fieldset class="mb-4">
            <legend class="h6">Confort de lecture</legend>
            <div class="vstack gap-2">
                <?php foreach ($a11yToggles as $option => [$title, $description]): ?>
                    <button type="button" class="a11y-option" data-a11y-toggle="<?= $option ?>" aria-pressed="false">
                        <span class="a11y-option__switch" aria-hidden="true"></span>
                        <span>
                            <span class="a11y-option__title"><?= $escape($title) ?></span>
                            <span class="a11y-option__desc"><?= $escape($description) ?></span>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <button type="button" class="btn btn-outline-secondary w-100" data-a11y-reset>Revenir à l’affichage d’origine</button>

        <p class="small text-muted mt-4 mb-0">
            Astuce : la touche <kbd>Tab</kbd> parcourt la page, <kbd>Échap</kbd> ferme ce panneau et les bulles d’aide.
            Les icônes <span class="info-tip info-tip--static" aria-hidden="true">i</span> donnent une explication au survol ou au toucher.
        </p>

        <p class="visually-hidden" id="a11yStatus" aria-live="polite"></p>
    </div>
</div>
