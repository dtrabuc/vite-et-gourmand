<main class="py-5">
    <section class="menu-hero hero py-5 text-center text-white mb-5">
        <div class="container py-4">
            <h1 class="display-5 fw-bold mb-3">Nos menus et formules</h1>
            <p class="lead mb-0">Des propositions pour les réceptions privées et les événements professionnels.</p>
        </div>
    </section>

    <section class="container">
        <div class="text-center mb-4">
            <span class="badge bg-primary-subtle text-primary">Menus complets</span>
            <h2 class="section-title d-inline-block mt-3">Choisir une formule</h2>
        </div>

        <form id="menuFilters" class="card border-0 shadow-sm p-3 mb-4" data-filter-endpoint="/menus/filter" data-fallback-images="<?= $escape(json_encode(menu_image_fallbacks(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="filterTheme">Thème</label>
                    <select id="filterTheme" name="theme" class="form-select">
                        <option value="">Tous les thèmes</option>
                        <?php foreach ($themes ?? [] as $theme): ?>
                            <option value="<?= $escape($theme) ?>"><?= $escape($theme) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="filterRegime">Régime</label>
                    <select id="filterRegime" name="dietary_regime" class="form-select">
                        <option value="">Tous les régimes</option>
                        <?php foreach (\App\Core\Labels::DIETARY_REGIME as $regimeValue => $regimeLabel): ?>
                            <option value="<?= $escape($regimeValue) ?>"><?= $escape($regimeLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="filterMinPrice">Prix min.</label><?= info_tip('Prix du menu pour son nombre minimum de personnes, en euros.') ?>
                    <input id="filterMinPrice" name="min_price" class="form-control" type="number" min="0" step="0.01">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="filterMaxPrice">Prix max.</label><?= info_tip('Les menus plus chers que ce montant sont masqués.') ?>
                    <input id="filterMaxPrice" name="max_price" class="form-control" type="number" min="0" step="0.01">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="filterPeople">Personnes min.</label><?= info_tip('Affiche les menus qui peuvent se commander pour ce nombre de personnes.') ?>
                    <input id="filterPeople" name="min_people" class="form-control" type="number" min="1">
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button class="btn btn-primary" type="submit">Filtrer</button>
                    <button class="btn btn-outline-secondary" type="reset">Réinitialiser</button>
                </div>
            </div>
        </form>

        <div id="menuFilterStatus" class="visually-hidden" aria-live="polite"></div>

        <div id="menuGrid" class="row g-4 mb-5">
            <?php foreach ($menus as $menu): ?>
                <?php $cover = menu_cover_image($menuCovers ?? [], $menu->getId(), $menu->getTitle()); ?>
                <?php $details = $menuDetails[$menu->getId()] ?? ['dishes' => []]; ?>
<div class="col-md-6 col-lg-4" data-menu-card>
                    <article class="card menu-card h-100 border-0 shadow-sm overflow-hidden">
                        <img src="<?= $escape($cover['url']) ?>" class="card-img-top menu-card__image" alt="<?= $escape($cover['alt_text'] ?? $menu->getTitle()) ?>" width="1200" height="800" loading="lazy" decoding="async">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between gap-2">
                                <span class="badge bg-primary-subtle text-primary"><?= $escape($menu->getTheme()) ?></span>
                                <strong><?= number_format($menu->getBasePrice(), 2, ',', ' ') ?> €</strong>
                            </div>
                            <h3 class="h4 text-primary mt-3"><?= $escape($menu->getTitle()) ?></h3>
                            <p class="text-muted flex-grow-1"><?= $escape($menu->getDescription()) ?></p>
                            <?php $dishesByCategory = ['starter' => [], 'main' => [], 'dessert' => []]; ?>
                            <?php foreach ($details['dishes'] ?? [] as $dish): ?>
                                <?php if (isset($dishesByCategory[$dish['category']])): ?>
                                    <?php $dishesByCategory[$dish['category']][] = $dish['name']; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <div class="bg-light p-3 rounded mb-3 small text-muted">
                                <p class="mb-1"><strong>Entrées :</strong> <?= $escape(implode(', ', array_slice($dishesByCategory['starter'], 0, 2)) ?: 'Non renseignées') ?></p>
                                <p class="mb-1"><strong>Plats :</strong> <?= $escape(implode(', ', array_slice($dishesByCategory['main'], 0, 2)) ?: 'Non renseignés') ?></p>
                                <p class="mb-0"><strong>Desserts :</strong> <?= $escape(implode(', ', array_slice($dishesByCategory['dessert'], 0, 2)) ?: 'Non renseignés') ?></p>
                            </div>
                            <p class="small mb-3">Minimum : <?= $menu->getMinPeople() ?> personnes · Stock : <?= $menu->getAvailableStock() ?></p>
                            <div class="d-grid gap-2 mt-auto">
                                <a class="btn btn-outline-primary" href="/menus/<?= $menu->getId() ?>">Voir le détail</a>
                                <?php if (!empty($user)): ?>
                                    <a class="btn btn-primary" href="/orders/new?menu=<?= $menu->getId() ?>">Commander</a>
                                <?php else: ?>
                                    <a class="btn btn-primary" href="/login?redirect=<?= $escape(rawurlencode('/orders/new?menu=' . $menu->getId())) ?>">Se connecter pour commander</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
            <?php if ($menus === []): ?>
                <div class="col-12"><div class="alert alert-warning">Aucun menu disponible pour le moment.</div></div>
            <?php endif; ?>
        </div>
    </section>

</main>
