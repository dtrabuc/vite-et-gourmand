<main class="py-5">
    <div class="menu-detail-hero hero py-5 text-center text-white mb-5">
        <div class="container py-4">
            <h1 class="display-5 fw-bold"><?= $escape($menu->getTitle()) ?></h1>
            <p class="lead mb-0"><?= \App\Service\MenuPricing::format(\App\Service\MenuPricing::perPerson($menu->getBasePrice(), $menu->getMinPeople())) ?> par personne · à partir de <?= $menu->getMinPeople() ?> personne<?= $menu->getMinPeople() > 1 ? 's' : '' ?></p>
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

                            <?php
                            $minPeople = $menu->getMinPeople();
                            $perPerson = \App\Service\MenuPricing::perPerson($menu->getBasePrice(), $minPeople);
                            $people = max((int) ($people ?? $minPeople), 1);
                            $quote = \App\Service\MenuPricing::quote($menu->getBasePrice(), $minPeople, $people);
                            $orderUrl = '/orders/new?menu=' . $menu->getId() . '&people=' . max($people, $minPeople);
                            ?>
                            <div class="row g-4 mt-1">
                                <div class="col-lg-7">
                                    <h3 class="h5 text-primary">Inclus pour chaque convive</h3>
                                    <?php foreach (menu_composition($details['dishes'] ?? []) as $group): ?>
                                        <div class="menu-detail-group">
                                            <h4 class="h6 fw-bold mb-2"><?= $escape($group['summary']) ?></h4>
                                            <?php foreach ($details['dishes'] as $dish): ?>
                                                <?php if ($dish['category'] === $group['key']): ?>
                                                    <div class="menu-detail-dish mb-2">
                                                        <strong><?= $escape($dish['name']) ?></strong>
                                                        <?php if (!empty($dish['description'])): ?>
                                                            <p class="small text-muted mb-0 mt-1"><?= $escape($dish['description']) ?></p>
                                                        <?php endif; ?>
                                                        <p class="small mb-0 mt-1">
                                                            <span class="fw-semibold">Allergènes :</span>
                                                            <?= ($dish['allergens'] ?? []) !== [] ? $escape(implode(', ', $dish['allergens'])) : 'aucun' ?>
                                                        </p>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <p class="small text-muted mb-0">Régime : <?= $escape(\App\Core\Labels::dietaryRegime($menu->getDietaryRegime())) ?> · thème <?= $escape($menu->getTheme()) ?></p>
                                </div>

                                <div class="col-lg-5">
                                    <aside class="price-box" id="menuQuote" data-endpoint="/menus/<?= $menu->getId() ?>/quote" data-menu-id="<?= $menu->getId() ?>" data-min-people="<?= $minPeople ?>" data-logged-in="<?= !empty($user) ? '1' : '0' ?>">
                                        <p class="price-box__unit mb-1"><strong><?= \App\Service\MenuPricing::format($perPerson) ?></strong> / personne</p>
                                        <p class="small text-muted">À partir de <?= $minPeople ?> personne<?= $minPeople > 1 ? 's' : '' ?> · −10 % dès <?= \App\Service\MenuPricing::discountFrom($minPeople) ?> personnes</p>

                                        <label class="form-label fw-semibold" for="quotePeople">Nombre de personnes</label>
                                        <div class="input-group people-stepper mb-3">
                                            <button class="btn btn-outline-primary" type="button" data-step="-1" aria-label="Une personne de moins">−</button>
                                            <input id="quotePeople" class="form-control text-center" type="number" min="1" max="999" inputmode="numeric" value="<?= $people ?>">
                                            <button class="btn btn-outline-primary" type="button" data-step="1" aria-label="Une personne de plus">+</button>
                                        </div>

                                        <dl class="price-box__lines mb-2" aria-live="polite">
                                            <div><dt data-quote-label><?= $people ?> × <?= \App\Service\MenuPricing::format($perPerson) ?></dt><dd data-quote-gross><?= \App\Service\MenuPricing::format($quote['gross']) ?></dd></div>
                                            <div data-quote-discount-row <?= $quote['discount_rate'] > 0 ? '' : 'hidden' ?>><dt>Remise −10 %</dt><dd data-quote-discount>−<?= \App\Service\MenuPricing::format($quote['discount_amount']) ?></dd></div>
                                            <div class="price-box__total"><dt>Total menu</dt><dd data-quote-total><?= \App\Service\MenuPricing::format($quote['total']) ?></dd></div>
                                        </dl>
                                        <p class="small mb-3" data-quote-message><?= $quote['orderable'] ? 'Livraison offerte dans Bordeaux, sinon 5 € + 0,59 €/km.' : 'Ce menu se commande à partir de ' . $minPeople . ' personnes.' ?></p>

                                        <?php if (!empty($user)): ?>
                                            <a class="btn btn-primary w-100" data-quote-order href="<?= $escape($orderUrl) ?>">Commander ce menu</a>
                                        <?php else: ?>
                                            <a class="btn btn-primary w-100" data-quote-order href="/login?redirect=<?= $escape(rawurlencode($orderUrl)) ?>">Se connecter pour commander</a>
                                            <a class="btn btn-link w-100" href="/register">Créer un compte</a>
                                        <?php endif; ?>
                                    </aside>
                                </div>
                            </div>

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

                            <a class="btn btn-outline-primary mt-2" href="/menus">Retour aux menus</a>
                        </div>
                    </article>
                </div>
            </div>
    </div>
</main>