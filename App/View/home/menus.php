<?php
$people = $people ?? null;
$themes ??= [];
?>
<main class="pb-5">
    <section class="menu-hero hero py-4 text-center text-white mb-4">
        <div class="container py-3">
            <h1 class="display-6 fw-bold mb-2">Nos menus</h1>
            <p class="lead mb-0">Un prix par personne, tout compris : apéritif, entrée, plat, accompagnements, dessert et digestif.</p>
        </div>
    </section>

    <section class="container">
        <form id="menuFilters" class="menu-toolbar mb-3" data-filter-endpoint="/menus/filter" data-logged-in="<?= !empty($user) ? '1' : '0' ?>" data-fallback-images="<?= $escape(json_encode(menu_image_fallbacks(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>">
            <div class="menu-toolbar__people">
                <label class="fw-semibold mb-0" for="filterPeople">Pour combien de personnes ?</label>
                <div class="input-group people-stepper">
                    <button class="btn btn-outline-primary" type="button" data-step="-1" aria-label="Une personne de moins">−</button>
                    <input id="filterPeople" name="people" class="form-control text-center" type="number" min="1" max="999" inputmode="numeric" placeholder="—" value="<?= $people !== null ? (int) $people : '' ?>">
                    <button class="btn btn-outline-primary" type="button" data-step="1" aria-label="Une personne de plus">+</button>
                </div>
                <div class="people-quick" role="group" aria-label="Choix rapide du nombre de personnes">
                    <?php foreach ([1, 2, 4, 6, 10, 15, 20] as $n): ?>
                        <button type="button" class="btn btn-chip" data-people="<?= $n ?>"><?= $n ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="menu-toolbar__chips" role="group" aria-label="Thème">
                <input class="btn-check" type="radio" name="theme" id="theme_all" value="" checked>
                <label class="btn btn-chip" for="theme_all">Tous</label>
                <?php foreach ($themes as $index => $theme): ?>
                    <input class="btn-check" type="radio" name="theme" id="theme_<?= $index ?>" value="<?= $escape($theme) ?>">
                    <label class="btn btn-chip" for="theme_<?= $index ?>"><?= $escape($theme) ?></label>
                <?php endforeach; ?>
                <span class="menu-toolbar__sep" aria-hidden="true"></span>
                <?php foreach (\App\Core\Labels::DIETARY_REGIME as $regimeValue => $regimeLabel): ?>
                    <?php if ($regimeValue === 'other') { continue; } ?>
                    <input class="btn-check" type="checkbox" name="dietary_regime" id="regime_<?= $escape($regimeValue) ?>" value="<?= $escape($regimeValue) ?>">
                    <label class="btn btn-chip" for="regime_<?= $escape($regimeValue) ?>"><?= $escape($regimeLabel) ?></label>
                <?php endforeach; ?>
            </div>

            <details class="menu-toolbar__more">
                <summary>Budget par personne</summary>
                <div class="row g-2 mt-1">
                    <div class="col-6 col-md-3">
                        <label class="form-label small" for="filterMinPrice">Minimum (€ / pers.)</label>
                        <input id="filterMinPrice" name="min_price" class="form-control" type="number" min="0" step="0.5">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small" for="filterMaxPrice">Maximum (€ / pers.)</label>
                        <input id="filterMaxPrice" name="max_price" class="form-control" type="number" min="0" step="0.5">
                    </div>
                    <div class="col-md-6 d-flex align-items-end gap-2">
                        <button class="btn btn-primary" type="submit">Appliquer</button>
                        <button class="btn btn-outline-secondary" type="reset">Tout effacer</button>
                    </div>
                </div>
            </details>
        </form>

        <details class="pricing-info mb-4" id="pricingInfo">
            <summary>Comment est calculé le prix ?</summary>
            <ol class="mb-0 mt-2">
                <li><strong>Prix par personne × nombre de convives.</strong> Chaque convive a un apéritif (avec ou sans alcool), une entrée, un plat, les accompagnements, un dessert et un digestif (sauf menu enfant).</li>
                <li><strong>Minimum par menu :</strong> certains menus se commandent à partir de 2, 3 ou 6 personnes (indiqué sur la carte).</li>
                <li><strong>−10 % automatiques</strong> dès 5 personnes de plus que le minimum du menu.</li>
                <li><strong>Livraison :</strong> offerte dans Bordeaux ; jusqu’à 25 km, 5 € + 0,59 € par km. Plus loin : retrait gratuit à Bordeaux, ou livraison au même tarif kilométrique si vous l’acceptez. Repas sur place : gratuit.</li>
                <li><strong>Paiement</strong> en espèces à la remise. Pas de frais cachés.</li>
            </ol>
        </details>

        <p id="menuFilterStatus" class="menu-status small text-muted" aria-live="polite">
            <?= count($menus) ?> menu<?= count($menus) > 1 ? 's' : '' ?><?= $people !== null ? ' · prix pour ' . (int) $people . ' personne' . ($people > 1 ? 's' : '') : '' ?>
        </p>

        <div id="menuGrid" class="row g-4 mb-5">
            <?php
            // pas de barrière : les menus possibles pour ce nombre de personnes d'abord, les autres repliés
            $possible = array_filter($menus, static fn ($m): bool => $people === null || $m->getMinPeople() <= $people);
            $others = array_filter($menus, static fn ($m): bool => $people !== null && $m->getMinPeople() > $people);
            ?>
            <?php foreach ($possible as $menu): ?>
                <?php
                $cover = menu_cover_image($menuCovers ?? [], $menu->getId(), $menu->getTitle());
                $dishes = $menuDetails[$menu->getId()]['dishes'] ?? [];
                require __DIR__ . '/_menu_card.php';
                ?>
            <?php endforeach; ?>
            <?php if ($others !== []): ?>
                <div class="col-12">
                    <details class="menu-others">
                        <summary><?= count($others) ?> autre<?= count($others) > 1 ? 's menus' : ' menu' ?> à partir de plus de <?= (int) $people ?> personne<?= $people > 1 ? 's' : '' ?></summary>
                        <div class="row g-4 mt-1">
                            <?php foreach ($others as $menu): ?>
                                <?php
                                $cover = menu_cover_image($menuCovers ?? [], $menu->getId(), $menu->getTitle());
                                $dishes = $menuDetails[$menu->getId()]['dishes'] ?? [];
                                require __DIR__ . '/_menu_card.php';
                                ?>
                            <?php endforeach; ?>
                        </div>
                    </details>
                </div>
            <?php endif; ?>
            <?php if ($menus === []): ?>
                <div class="col-12"><div class="alert alert-warning">Aucun menu disponible pour le moment.</div></div>
            <?php endif; ?>
        </div>
    </section>
</main>
