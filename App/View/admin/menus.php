<?php
$regimes = \App\Core\Labels::DIETARY_REGIME;

/**
 * Champs d'un menu. $menu vaut null pour la création ; $prefix rend les identifiants uniques.
 */
$menuFields = static function (string $prefix, ?\App\Entity\Menu $menu) use ($escape, $themes, $regimes): void {
    $id = static fn (string $name): string => $prefix . '_' . $name;
    $theme = $menu?->getTheme() ?? '';
    $minPeople = $menu?->getMinPeople();
    ?>
    <div class="col-md-6">
        <label class="form-label" for="<?= $id('title') ?>">Titre</label>
        <input class="form-control" id="<?= $id('title') ?>" name="title" maxlength="150"
               value="<?= $escape($menu?->getTitle() ?? '') ?>" required>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="<?= $id('theme') ?>">Thème</label>
        <select class="form-select" id="<?= $id('theme') ?>" name="theme" required>
            <?php if ($menu === null): ?>
                <option value="">Choisir</option>
            <?php elseif (!in_array($theme, $themes, true)): ?>
                <option value="">Choisir (actuel : <?= $escape($theme) ?>)</option>
            <?php endif; ?>
            <?php foreach ($themes as $themeOption): ?>
                <option value="<?= $escape($themeOption) ?>" <?= $theme === $themeOption ? 'selected' : '' ?>><?= $escape($themeOption) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="<?= $id('regime') ?>">Régime</label>
        <select class="form-select" id="<?= $id('regime') ?>" name="dietary_regime">
            <?php foreach ($regimes as $value => $label): ?>
                <option value="<?= $value ?>" <?= ($menu?->getDietaryRegime() ?? 'classic') === $value ? 'selected' : '' ?>><?= $escape($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label" for="<?= $id('min_people') ?>">Minimum de personnes</label><?= info_tip('Le client ne peut pas commander pour moins de personnes.') ?>
        <input class="form-control" id="<?= $id('min_people') ?>" type="number" min="1" name="min_people"
               value="<?= $minPeople ?? '' ?>" required>
    </div>
    <div class="col-md-3">
        <label class="form-label" for="<?= $id('base_price') ?>">
            Prix du menu (pour <?= $minPeople !== null ? $minPeople . ' personne' . ($minPeople > 1 ? 's' : '') : 'le minimum de personnes' ?>, €)
        </label>
        <input class="form-control" id="<?= $id('base_price') ?>" type="number" step="0.01" min="0" name="base_price"
               value="<?= $menu?->getBasePrice() ?? '' ?>" required>
    </div>
    <div class="col-12">
        <label class="form-label" for="<?= $id('description') ?>">Description</label>
        <textarea class="form-control" id="<?= $id('description') ?>" name="description" required><?= $escape($menu?->getDescription() ?? '') ?></textarea>
    </div>
    <div class="col-12">
        <label class="form-label" for="<?= $id('conditions') ?>">Conditions</label>
        <textarea class="form-control" id="<?= $id('conditions') ?>" name="conditions" required><?= $escape($menu?->getConditions() ?? '') ?></textarea>
    </div>
    <div class="col-md-3">
        <label class="form-label" for="<?= $id('stock') ?>">Stock (commandes possibles)</label><?= info_tip('Diminue de 1 à chaque commande. À 0, le menu ne peut plus être commandé.') ?>
        <input class="form-control" id="<?= $id('stock') ?>" type="number" min="0" name="available_stock"
               value="<?= $menu?->getAvailableStock() ?? '' ?>" required>
    </div>
    <?php
};
?>
<main class="py-5">
    <div class="container">
        <h1 class="h2 text-primary mb-4">Gestion des menus</h1>

        <section class="card border-0 shadow-sm mb-4" aria-labelledby="newMenuTitle">
            <div class="card-body">
                <h2 class="h5" id="newMenuTitle">Créer un menu</h2>
                <form method="post" action="/admin/menus" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <?php $menuFields('new_menu', null); ?>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit">Créer</button>
                    </div>
                </form>
            </div>
        </section>

        <?php foreach ($menus as $menu): ?>
            <section class="card border-0 shadow-sm mb-3" aria-labelledby="menu_title_<?= $menu->getId() ?>">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h2 class="h5 mb-0" id="menu_title_<?= $menu->getId() ?>">
                            <?= $escape($menu->getTitle()) ?>
                            <?php if ($menu->getAvailableStock() < 1): ?>
                                <span class="badge text-bg-warning ms-2">Épuisé</span>
                            <?php endif; ?>
                        </h2>
                        <a class="btn btn-primary btn-sm" href="/admin/menus/<?= $menu->getId() ?>/content">Composition et galerie</a>
                    </div>

                    <form method="post" action="/admin/menus/<?= $menu->getId() ?>" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                        <?php $menuFields('menu_' . $menu->getId(), $menu); ?>
                        <div class="col-12">
                            <button class="btn btn-outline-primary" type="submit">Enregistrer</button>
                        </div>
                    </form>

                    <form method="post" action="/admin/menus/<?= $menu->getId() ?>/delete" class="mt-2">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                        <button class="btn btn-outline-danger btn-sm" type="submit" data-confirm="Désactiver ce menu ?">Désactiver</button>
                    </form>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</main>
