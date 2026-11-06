<?php
$selectedDishIds = array_map('intval', $selectedDishIds ?? []);
$images = $images ?? [];
$imageCount = count($images);
?>
<main class="py-5">
    <div class="container">
        <div class="mb-4">
            <span class="badge bg-primary-subtle text-primary">Espace équipe</span>
            <h1 class="h2 text-primary mt-2 mb-1">Menu « <?= $escape($menu->getTitle()) ?> »</h1>
            <p class="mb-0">
                <a href="/admin/menus">Retour à la gestion des menus</a> ·
                <a href="/menus/<?= (int) $menu->getId() ?>">Voir la page publique</a>
            </p>
        </div>

        <section class="card border-0 shadow-sm mb-4" aria-labelledby="compositionTitle">
            <div class="card-body">
                <h2 class="h4 text-primary" id="compositionTitle">Composition</h2>
                <p class="text-muted small">
                    Cochez au moins une entrée, un plat et un dessert. Les plats se créent et se modifient dans
                    <a href="/admin/dishes">la gestion des plats</a>.
                </p>

                <form method="post" action="/admin/menus/<?= (int) $menu->getId() ?>/dishes">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <div class="row g-4">
                        <?php foreach ($categories as $category => $label): ?>
                            <div class="col-lg-4">
                                <fieldset>
                                    <legend class="h6 fw-bold"><?= $escape($label) ?>s</legend>
                                    <?php $found = false; ?>
                                    <?php foreach ($dishes as $dish): ?>
                                        <?php if ($dish['category'] !== $category) { continue; } ?>
                                        <?php $found = true; ?>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="dishes[]"
                                                   id="dish_<?= (int) $dish['id'] ?>" value="<?= (int) $dish['id'] ?>"
                                                   <?= in_array((int) $dish['id'], $selectedDishIds, true) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="dish_<?= (int) $dish['id'] ?>">
                                                <?= $escape($dish['name']) ?>
                                                <?php if ($dish['allergens'] !== []): ?>
                                                    <span class="d-block small text-muted">Allergènes : <?= $escape(implode(', ', $dish['allergens'])) ?></span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (!$found): ?>
                                        <p class="small text-muted">Aucun plat disponible.</p>
                                    <?php endif; ?>
                                </fieldset>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="btn btn-primary mt-3" type="submit">Enregistrer la composition</button>
                </form>
            </div>
        </section>

        <section class="card border-0 shadow-sm" id="gallery" aria-labelledby="galleryTitle">
            <div class="card-body">
                <h2 class="h4 text-primary" id="galleryTitle">Galerie d’images (<?= $imageCount ?>)</h2>
                <p class="text-muted small">La première image sert de couverture sur les listes de menus.</p>

                <?php if ($images === []): ?>
                    <p class="text-muted">Aucune image pour ce menu.</p>
                <?php else: ?>
                    <ol class="list-unstyled row g-3">
                        <?php foreach ($images as $index => $image): ?>
                            <li class="col-sm-6 col-lg-4">
                                <div class="border rounded p-2 h-100">
                                    <img src="<?= $escape($image['url']) ?>" alt="<?= $escape($image['alt_text']) ?>"
                                         class="img-fluid rounded mb-2 w-100" style="height: 160px; object-fit: cover;" loading="lazy">
                                    <p class="small mb-2">
                                        <strong>Position <?= (int) $image['position'] ?></strong><?= $index === 0 ? ' · couverture' : '' ?><br>
                                        <?= $escape($image['alt_text']) ?>
                                    </p>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach (['up' => ['Monter', $index > 0], 'down' => ['Descendre', $index < $imageCount - 1]] as $direction => [$label, $enabled]): ?>
                                            <form method="post" action="/admin/menus/<?= (int) $menu->getId() ?>/images/<?= $escape($image['id']) ?>/move">
                                                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                                <input type="hidden" name="direction" value="<?= $direction ?>">
                                                <button class="btn btn-outline-secondary btn-sm" type="submit" <?= $enabled ? '' : 'disabled' ?>
                                                        aria-label="<?= $escape($label . ' l’image « ' . $image['alt_text'] . ' »') ?>"><?= $label ?></button>
                                            </form>
                                        <?php endforeach; ?>
                                        <form method="post" action="/admin/menus/<?= (int) $menu->getId() ?>/images/<?= $escape($image['id']) ?>/delete">
                                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                            <button class="btn btn-outline-danger btn-sm" type="submit" data-confirm="Supprimer cette image ?"
                                                    aria-label="<?= $escape('Supprimer l’image « ' . $image['alt_text'] . ' »') ?>">Supprimer</button>
                                        </form>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>

                <h3 class="h5 mt-4">Ajouter une image</h3>
                <form method="post" action="/admin/menus/<?= (int) $menu->getId() ?>/images" enctype="multipart/form-data" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <div class="col-md-6">
                        <label class="form-label" for="menu_image">Fichier (JPEG, PNG ou WebP, 5 Mo max.)</label>
                        <input class="form-control" type="file" id="menu_image" name="image" accept="image/jpeg,image/png,image/webp" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="menu_image_alt">Description de l’image (texte alternatif)</label>
                        <input class="form-control" id="menu_image_alt" name="alt_text" maxlength="200" required
                               placeholder="Ex. : assiette de saumon et légumes de saison">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit">Ajouter à la galerie</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</main>
