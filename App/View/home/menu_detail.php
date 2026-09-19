<main class="py-5">
    <div class="container">
        <?php if ($menu === null): ?>
            <div class="alert alert-warning">
                <h1 class="h4">Menu introuvable</h1>
                <a href="/menus">Retour au catalogue</a>
            </div>
        <?php else: ?>
            <?php $details = $details ?? ['images' => [], 'dishes' => [], 'allergens' => []]; ?>
            <div class="row justify-content-center">
                <div class="col-xl-10">
                    <article class="card border-0 shadow-sm overflow-hidden">
                        <?php if ($details['images'] !== []): ?>
                            <div class="row g-0">
                                <?php foreach ($details['images'] as $image): ?>
                                    <div class="col-md-6">
                                        <img src="/uploads/<?= $escape($image['path']) ?>" class="img-fluid w-100 h-100 object-fit-cover" alt="<?= $escape($image['alt_text']) ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="card-body p-4 p-md-5">
                            <span class="badge bg-primary-subtle text-primary"><?= $escape($menu->getTheme()) ?></span>
                            <h1 class="display-6 text-primary mt-3"><?= $escape($menu->getTitle()) ?></h1>
                            <p class="lead text-muted"><?= $escape($menu->getDescription()) ?></p>

                            <dl class="row mt-4">
                                <dt class="col-sm-5">Prix du menu (minimum <?= $menu->getMinPeople() ?> personnes)</dt>
                                <dd class="col-sm-7"><?= number_format($menu->getBasePrice(), 2, ',', ' ') ?> €</dd>
                                <dt class="col-sm-5">Régime alimentaire</dt>
                                <dd class="col-sm-7"><?= $escape($menu->getDietaryRegime()) ?></dd>
                                <dt class="col-sm-5">Nombre minimum</dt>
                                <dd class="col-sm-7"><?= $menu->getMinPeople() ?> personnes</dd>
                                <dt class="col-sm-5">Stock disponible</dt>
                                <dd class="col-sm-7"><?= $menu->getAvailableStock() ?> commandes</dd>
                            </dl>

                            <?php if ($details['dishes'] !== []): ?>
                                <section class="mt-4">
                                    <h2 class="h4 text-primary">Composition du menu</h2>
                                    <div class="row g-3 mt-1">
                                        <?php foreach (['starter' => 'Entrée', 'main' => 'Plat', 'dessert' => 'Dessert'] as $category => $label): ?>
                                            <div class="col-md-4">
                                                <h3 class="h6 fw-bold"><?= $label ?></h3>
                                                <?php foreach ($details['dishes'] as $dish): ?>
                                                    <?php if ($dish['category'] === $category): ?>
                                                        <div class="border rounded p-3 mb-2">
                                                            <strong><?= $escape($dish['name']) ?></strong>
                                                            <?php if (!empty($dish['description'])): ?><p class="small text-muted mb-0 mt-1"><?= $escape($dish['description']) ?></p><?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                            <?php endif; ?>

                            <section class="alert alert-warning mt-4">
                                <h2 class="h5">Conditions de commande</h2>
                                <p class="mb-0"><?= $escape($menu->getConditions()) ?></p>
                            </section>

                            <section class="mt-4">
                                <h2 class="h5 text-primary">Allergènes</h2>
                                <?php if ($details['allergens'] !== []): ?>
                                    <p><?= $escape(implode(', ', $details['allergens'])) ?></p>
                                <?php else: ?>
                                    <p class="text-muted">Aucun allergène renseigné dans les données du menu.</p>
                                <?php endif; ?>
                            </section>

                            <div class="d-flex gap-2 flex-wrap mt-4">
                                <a class="btn btn-outline-primary" href="/menus">Retour aux menus</a>
                                <?php if (!empty($user)): ?>
                                    <a class="btn btn-primary" href="/orders/new?menu=<?= $menu->getId() ?>">Commander ce menu</a>
                                <?php else: ?>
                                    <a class="btn btn-primary" href="/login">Connectez-vous pour commander</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>
