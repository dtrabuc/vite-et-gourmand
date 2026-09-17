<main>
    <section class="hero bg-primary text-white py-5">
        <div class="container py-4">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="badge text-bg-light text-primary mb-3">Traiteur à Bordeaux</span>
                    <h1 class="display-5 fw-bold">Des menus gourmands pour vos événements</h1>
                    <p class="lead">Une cuisine de saison, des formules lisibles et un accompagnement adapté à chaque réception.</p>
                    <a class="btn btn-light btn-lg" href="/menus">Découvrir les menus</a>
                </div>
            </div>
        </div>
    </section>
    <section class="py-5 bg-white">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div><h2 class="h2 text-primary mb-1">Menus à la une</h2><p class="text-muted mb-0">Des formules disponibles à la commande.</p></div>
                <a href="/menus" class="btn btn-outline-primary">Tout voir</a>
            </div>
            <div class="row g-4">
                <?php foreach ($menus as $menu): ?>
                    <div class="col-md-6 col-lg-4">
                        <article class="card h-100 shadow-sm border-0">
                            <div class="card-body d-flex flex-column">
                                <span class="badge text-bg-light align-self-start mb-2"><?= $escape($menu->getTheme()) ?></span>
                                <h3 class="h4 text-primary"><?= $escape($menu->getTitle()) ?></h3>
                                <p class="text-muted flex-grow-1"><?= $escape($menu->getDescription()) ?></p>
                                <p class="mb-3"><strong><?= number_format($menu->getBasePrice(), 2, ',', ' ') ?> €</strong> pour <?= $menu->getMinPeople() ?> personnes minimum</p>
                                <a class="btn btn-outline-primary" href="/menus/<?= $menu->getId() ?>">Voir le détail</a>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
                <?php if ($menus === []): ?><p class="text-muted">Les menus seront disponibles prochainement.</p><?php endif; ?>
            </div>
        </div>
    </section>
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="h2 text-primary mb-4">Avis clients</h2>
            <div class="row g-4">
                <?php foreach ($reviews as $review): ?>
                    <div class="col-md-4"><article class="card h-100 border-0 shadow-sm"><div class="card-body"><p class="text-warning">★★★★★</p><p class="fst-italic">« <?= $escape($review['comment'] ?? '') ?> »</p><p class="mb-0 text-muted small"><?= $escape($review['user_name'] ?? '') ?></p></div></article></div>
                <?php endforeach; ?>
                <?php if ($reviews === []): ?><p class="text-muted">Les avis validés seront affichés ici.</p><?php endif; ?>
            </div>
        </div>
    </section>
</main>
