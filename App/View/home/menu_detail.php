<main class="py-5">
    <div class="menu-detail-hero hero py-5 text-center text-white mb-5">
        <div class="container py-4">
            <h1 class="display-5 fw-bold"><?= $escape($menu->getTitle()) ?></h1>
            <p class="lead mb-0">Entrées, plats, desserts, conditions de réservation et minimum de convives.</p>
        </div>
    </div>

    <div class="container">
            <?php /* menu introuvable : géré par la 404 commune (NotFoundException dans PublicController) */ ?>
            <?php
            $details ??= ['dishes' => [], 'allergens' => []];
            $images = array_values(array_filter($images ?? [], static fn (array $image): bool => $image['url'] !== ''));
            if ($images === []) {
                $images[] = menu_cover_image([], $menu->getId(), $menu->getTitle());
            }
            ?>
            <div class="row justify-content-center">
                <div class="col-xl-10">
                    <article class="card menu-detail-card border-0 shadow-sm overflow-hidden">
                        <?php if (count($images) === 1): ?>
                            <img
                                src="<?= $escape($images[0]['url']) ?>"
                                class="card-img-top menu-detail-image" loading="lazy" decoding="async"
                                alt="<?= $escape($images[0]['alt_text']) ?>"
                            >
                        <?php elseif (count($images) > 1): ?>
                            <div id="menuGallery" class="carousel slide" aria-label="Galerie d’images du menu">
                                <div class="carousel-indicators">
                                    <?php foreach ($images as $index => $image): ?>
                                        <button type="button" data-bs-target="#menuGallery" data-bs-slide-to="<?= $index ?>"
                                                <?= $index === 0 ? 'class="active" aria-current="true"' : '' ?>
                                                aria-label="Image <?= $index + 1 ?> sur <?= count($images) ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                                <div class="carousel-inner">
                                    <?php foreach ($images as $index => $image): ?>
                                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                            <img src="<?= $escape($image['url']) ?>" class="d-block w-100 menu-detail-image" decoding="async"
                                                 alt="<?= $escape($image['alt_text']) ?>" <?= $index > 0 ? 'loading="lazy"' : '' ?>>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#menuGallery" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Image précédente</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#menuGallery" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Image suivante</span>
                                </button>
                            </div>
                        <?php endif; ?>

                        <div class="card-body p-4 p-md-5">
                            <span class="badge bg-primary-subtle text-primary"><?= $escape($menu->getTheme()) ?></span>
                            <h2 class="display-6 text-primary mt-3"><?= $escape($menu->getTitle()) ?></h2>
                            <p class="lead text-muted"><?= $escape($menu->getDescription()) ?></p>

                            <dl class="row mt-4">
                                <dt class="col-sm-5">Prix du menu <?= info_tip('Prix pour le nombre minimum de personnes. Au-delà, le prix augmente par personne.') ?></dt>
                                <dd class="col-sm-7"><?= number_format($menu->getBasePrice(), 2, ',', ' ') ?> €</dd>
                                <dt class="col-sm-5">Minimum <?= info_tip('Nombre de personnes minimum pour commander ce menu. Remise de 10 % à partir de 5 personnes de plus.') ?></dt>
                                <dd class="col-sm-7"><?= $menu->getMinPeople() ?> personne<?= $menu->getMinPeople() > 1 ? 's' : '' ?></dd>
                                <dt class="col-sm-5">Régime alimentaire</dt>
                                <dd class="col-sm-7"><?= $escape(\App\Core\Labels::dietaryRegime($menu->getDietaryRegime())) ?></dd>
                                <dt class="col-sm-5">Stock disponible <?= info_tip('Nombre de commandes encore possibles pour ce menu.') ?></dt>
                                <dd class="col-sm-7"><?= $menu->getAvailableStock() ?> commandes</dd>
                            </dl>

                            <?php if (($details['dishes'] ?? []) !== []): ?>
                                <section class="mt-4">
                                    <h3 class="h4 text-primary">Composition du menu</h3>
                                    <div class="row g-3 mt-1">
                                        <?php foreach (['starter' => 'Entrée', 'main' => 'Plat / accompagnement', 'dessert' => 'Dessert'] as $category => $label): ?>
                                            <div class="col-md-4">
                                                <h4 class="h6 fw-bold"><?= $label ?></h4>
                                                <?php foreach ($details['dishes'] as $dish): ?>
                                                    <?php if ($dish['category'] === $category): ?>
                                                        <div class="menu-detail-dish mb-2">
                                                            <strong><?= $escape($dish['name']) ?></strong>
                                                            <?php if (!empty($dish['description'])): ?>
                                                                <p class="small text-muted mb-0 mt-1"><?= $escape($dish['description']) ?></p>
                                                            <?php endif; ?>
                                                            <p class="small mb-0 mt-2">
                                                                <span class="fw-semibold">Allergènes :</span>
                                                                <?= ($dish['allergens'] ?? []) !== [] ? $escape(implode(', ', $dish['allergens'])) : 'aucun' ?>
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                            <?php endif; ?>

                            <section class="alert alert-warning mt-4">
                                <h3 class="h5">Conditions de commande</h3>
                                <p class="mb-0"><?= $escape($menu->getConditions()) ?></p>
                            </section>

                            <section class="mt-4">
                                <h3 class="h5 text-primary">Allergènes présents dans le menu</h3>
                                <?php if (($details['allergens'] ?? []) !== []): ?>
                                    <p><?= $escape(implode(', ', $details['allergens'])) ?></p>
                                <?php else: ?>
                                    <p class="text-muted">Aucun allergène renseigné dans les données du menu.</p>
                                <?php endif; ?>
                            </section>

                            <div class="d-flex gap-2 flex-wrap mt-4">
                                <a class="btn btn-outline-primary" href="/menus">Retour aux menus</a>
                                <?php if (!empty($user)): ?>
                                    <a class="btn btn-primary" href="/orders/new?menu=<?= $menu->getId() ?>">Commander cette formule</a>
                                <?php else: ?>
                                    <a class="btn btn-primary" href="/login?redirect=<?= $escape(rawurlencode('/orders/new?menu=' . $menu->getId())) ?>">Connectez-vous pour commander</a>
                                    <a class="btn btn-outline-secondary" href="/register">Créer un compte</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
    </div>
</main>