<?php
$oldInput = is_array($oldInput ?? null) ? $oldInput : [];
$oldAllergens = array_map('intval', is_array($oldInput['allergens'] ?? null) ? $oldInput['allergens'] : []);
?>
<main class="py-5">
    <div class="container">
        <div class="mb-4">
            <span class="badge bg-primary-subtle text-primary">Espace équipe</span>
            <h1 class="h2 text-primary mt-2 mb-1">Gestion des plats</h1>
            <p class="text-muted mb-0">
                Entrées, plats et desserts, avec leurs allergènes. Un plat peut composer plusieurs menus :
                la composition se règle depuis <a href="/admin/menus">la gestion des menus</a>.
            </p>
        </div>

        <section class="card border-0 shadow-sm mb-4" aria-labelledby="newDishTitle">
            <div class="card-body">
                <h2 class="h5" id="newDishTitle">Ajouter un plat</h2>
                <form method="post" action="/admin/dishes" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">

                    <div class="col-md-6">
                        <label class="form-label" for="dish_name">Nom</label>
                        <input class="form-control" id="dish_name" name="name" maxlength="180"
                               value="<?= $escape($oldInput['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="dish_category">Catégorie</label>
                        <select class="form-select" id="dish_category" name="category" required>
                            <?php foreach ($categories as $value => $label): ?>
                                <option value="<?= $escape($value) ?>" <?= ($oldInput['category'] ?? '') === $value ? 'selected' : '' ?>><?= $escape($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="dish_regime">Régime</label>
                        <select class="form-select" id="dish_regime" name="dietary_regime">
                            <?php foreach ($regimes as $value => $label): ?>
                                <option value="<?= $escape($value) ?>" <?= ($oldInput['dietary_regime'] ?? 'classic') === $value ? 'selected' : '' ?>><?= $escape($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="dish_description">Description</label>
                        <textarea class="form-control" id="dish_description" name="description" rows="2" required><?= $escape($oldInput['description'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <fieldset>
                            <legend class="form-label fs-6">Allergènes</legend>
                            <div class="d-flex flex-wrap gap-3">
                                <?php foreach ($allergens as $allergen): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="allergens[]"
                                               id="new_allergen_<?= (int) $allergen['id'] ?>" value="<?= (int) $allergen['id'] ?>"
                                               <?= in_array((int) $allergen['id'], $oldAllergens, true) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="new_allergen_<?= (int) $allergen['id'] ?>"><?= $escape($allergen['name']) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit">Ajouter le plat</button>
                    </div>
                </form>
            </div>
        </section>

        <?php foreach ($categories as $category => $categoryLabel): ?>
            <?php $inCategory = array_filter($dishes, static fn (array $dish): bool => $dish['category'] === $category); ?>
            <section class="card border-0 shadow-sm mb-4" aria-labelledby="dishes_<?= $escape($category) ?>">
                <div class="card-body">
                    <h2 class="h5" id="dishes_<?= $escape($category) ?>"><?= $escape($categoryLabel) ?>s (<?= count($inCategory) ?>)</h2>
                    <?php if ($inCategory === []): ?>
                        <p class="text-muted mb-0">Aucun plat dans cette catégorie.</p>
                    <?php else: ?>
                        <div class="table-responsive" tabindex="0" role="region" aria-label="Liste des plats (défilable)">
                            <table class="table table-wide align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Nom</th>
                                        <th scope="col">Régime</th>
                                        <th scope="col">Allergènes</th>
                                        <th scope="col" class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inCategory as $dish): ?>
                                        <tr>
                                            <td>
                                                <strong><?= $escape($dish['name']) ?></strong>
                                                <span class="d-block small text-muted"><?= $escape($dish['description']) ?></span>
                                            </td>
                                            <td><?= $escape($regimes[$dish['dietary_regime']] ?? $dish['dietary_regime']) ?></td>
                                            <td><?= $dish['allergens'] !== [] ? $escape(implode(', ', $dish['allergens'])) : '<span class="text-muted">Aucun</span>' ?></td>
                                            <td class="text-end text-nowrap">
                                                <a class="btn btn-outline-primary btn-sm" href="/admin/dishes/<?= (int) $dish['id'] ?>/edit">Modifier</a>
                                                <form method="post" action="/admin/dishes/<?= (int) $dish['id'] ?>/delete" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                                    <button class="btn btn-outline-danger btn-sm" type="submit"
                                                            data-confirm="Supprimer ce plat ? Il sera retiré des menus qui le contiennent.">Supprimer</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</main>
