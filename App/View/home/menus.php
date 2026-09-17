<main class="py-5">
    <section class="container">
        <div class="text-center mb-5">
            <span class="badge bg-primary-subtle text-primary">Nos formules</span>
            <h1 class="display-6 text-primary fw-bold mt-3">Menus et prestations</h1>
            <p class="text-muted">Filtrez les formules sans recharger la page.</p>
        </div>
        <form id="menuFilters" class="card border-0 shadow-sm p-3 mb-4" data-filter-endpoint="/menus/filter">
            <div class="row g-3 align-items-end">
                <div class="col-md-4"><label class="form-label" for="filterTheme">Thème</label><select id="filterTheme" name="theme" class="form-select"><option value="">Tous les thèmes</option><?php foreach (array_unique(array_map(static fn ($menu) => $menu->getTheme(), $menus)) as $theme): ?><option value="<?= $escape($theme) ?>"><?= $escape($theme) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><label class="form-label" for="filterPrice">Prix maximum par personne</label><input id="filterPrice" name="max_price" class="form-control" type="number" min="0" step="0.01"></div>
                <div class="col-md-3"><label class="form-label" for="filterPeople">Nombre de personnes</label><input id="filterPeople" name="min_people" class="form-control" type="number" min="1"></div>
                <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Filtrer</button></div>
            </div>
        </form>
        <div id="menuFilterStatus" class="visually-hidden" aria-live="polite"></div>
        <div id="menuGrid" class="row g-4">
            <?php foreach ($menus as $menu): ?>
                <div class="col-md-6 col-lg-4" data-menu-card>
                    <article class="card h-100 border-0 shadow-sm"><div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between gap-2"><span class="badge bg-primary-subtle text-primary"><?= $escape($menu->getTheme()) ?></span><strong><?= number_format($menu->getBasePrice(), 2, ',', ' ') ?> €</strong></div>
                        <h2 class="h4 text-primary mt-3"><?= $escape($menu->getTitle()) ?></h2>
                        <p class="text-muted flex-grow-1"><?= $escape($menu->getDescription()) ?></p>
                        <p class="small">Minimum : <?= $menu->getMinPeople() ?> personnes · Stock : <?= $menu->getAvailableStock() ?></p>
                        <a class="btn btn-outline-primary" href="/menus/<?= $menu->getId() ?>">Voir le détail</a>
                    </div></article>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</main>
