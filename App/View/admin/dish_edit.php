<main class="py-5">
    <div class="container">
        <div class="mb-4">
            <span class="badge bg-primary-subtle text-primary">Espace équipe</span>
            <h1 class="h2 text-primary mt-2 mb-1">Modifier le plat « <?= $escape($dish['name']) ?> »</h1>
            <a href="/admin/dishes">Retour à la liste des plats</a>
        </div>

        <section class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="post" action="/admin/dishes/<?= (int) $dish['id'] ?>" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">

                    <div class="col-md-6">
                        <label class="form-label" for="dish_name">Nom</label>
                        <input class="form-control" id="dish_name" name="name" maxlength="180"
                               value="<?= $escape($dish['name']) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="dish_category">Catégorie</label>
                        <select class="form-select" id="dish_category" name="category" required>
                            <?php foreach ($categories as $value => $label): ?>
                                <option value="<?= $escape($value) ?>" <?= $dish['category'] === $value ? 'selected' : '' ?>><?= $escape($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="dish_regime">Régime</label>
                        <select class="form-select" id="dish_regime" name="dietary_regime">
                            <?php foreach ($regimes as $value => $label): ?>
                                <option value="<?= $escape($value) ?>" <?= $dish['dietary_regime'] === $value ? 'selected' : '' ?>><?= $escape($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="dish_description">Description</label>
                        <textarea class="form-control" id="dish_description" name="description" rows="3" required><?= $escape($dish['description']) ?></textarea>
                    </div>
                    <div class="col-12">
                        <fieldset>
                            <legend class="form-label fs-6">Allergènes</legend>
                            <div class="d-flex flex-wrap gap-3">
                                <?php foreach ($allergens as $allergen): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="allergens[]"
                                               id="allergen_<?= (int) $allergen['id'] ?>" value="<?= (int) $allergen['id'] ?>"
                                               <?= in_array((int) $allergen['id'], $dish['allergen_ids'], true) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="allergen_<?= (int) $allergen['id'] ?>"><?= $escape($allergen['name']) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit">Enregistrer</button>
                        <a class="btn btn-outline-secondary ms-2" href="/admin/dishes">Annuler</a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</main>
