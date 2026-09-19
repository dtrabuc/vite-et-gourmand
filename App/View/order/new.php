<?php
$errors = $_SESSION['order_errors'] ?? [];
$oldInput = $_SESSION['order_old_input'] ?? [];
unset($_SESSION['order_errors'], $_SESSION['order_old_input']);

$old = static fn(string $key, string $default = ''): string => htmlspecialchars(
    (string) ($oldInput[$key] ?? $default),
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);

$selectedId = (int) ($oldInput['menu_id'] ?? $selectedMenuId);
?>
<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                <section class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <span class="badge bg-primary-subtle text-primary">Étape finale</span>
                        <h1 class="h2 text-primary mt-3">Commander un menu</h1>
                        <p class="text-muted">Les informations de livraison et le prix sont contrôlés côté serveur.</p>

                        <?php if (isset($errors['general'])): ?>
                            <div class="alert alert-danger"><?= $escape($errors['general']) ?></div>
                        <?php endif; ?>

                        <form method="post" action="/orders" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">

                            <div class="col-12">
                                <h2 class="h5 text-primary">Informations client</h2>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nom</label>
                                        <input class="form-control" value="<?= $escape($orderUser?->getLastName() ?? '') ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Prénom</label>
                                        <input class="form-control" value="<?= $escape($orderUser?->getFirstName() ?? '') ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Téléphone</label>
                                        <input class="form-control" value="<?= $escape($orderUser?->getGsm() ?: $orderUser?->getPhone() ?? '') ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">E-mail</label>
                                        <input class="form-control" value="<?= $escape($orderUser?->getEmail() ?? '') ?>" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12"><hr></div>

                            <div class="col-12">
                                <h2 class="h5 text-primary">Commande</h2>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="menu_id">Menu</label>
                                <select class="form-select <?= isset($errors['menu_id']) ? 'is-invalid' : '' ?>" id="menu_id" name="menu_id" required>
                                    <option value="">Sélectionnez un menu</option>
                                    <?php foreach ($menus as $menu): ?>
                                        <option value="<?= $menu->getId() ?>" data-base-price="<?= $menu->getBasePrice() ?>" data-min-people="<?= $menu->getMinPeople() ?>" <?= $selectedId === $menu->getId() ? 'selected' : '' ?>>
                                            <?= $escape($menu->getTitle()) ?> — <?= number_format($menu->getBasePrice(), 2, ',', ' ') ?> € (minimum <?= $menu->getMinPeople() ?> personnes)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['menu_id'])): ?><div class="invalid-feedback"><?= $escape($errors['menu_id']) ?></div><?php endif; ?>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="number_of_people">Nombre de personnes</label>
                                <input class="form-control <?= isset($errors['number_of_people']) ? 'is-invalid' : '' ?>" id="number_of_people" name="number_of_people" type="number" min="1" value="<?= $old('number_of_people') ?>" required>
                                <?php if (isset($errors['number_of_people'])): ?><div class="invalid-feedback"><?= $escape($errors['number_of_people']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="delivery_date">Date</label>
                                <input class="form-control <?= isset($errors['delivery_date']) ? 'is-invalid' : '' ?>" id="delivery_date" name="delivery_date" type="date" min="<?= date('Y-m-d') ?>" value="<?= $old('delivery_date') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="delivery_time">Heure</label>
                                <input class="form-control <?= isset($errors['delivery_time']) ? 'is-invalid' : '' ?>" id="delivery_time" name="delivery_time" type="time" value="<?= $old('delivery_time') ?>" required>
                            </div>

                            <div class="col-12"><hr></div>
                            <div class="col-12">
                                <h2 class="h5 text-primary">Lieu de prestation</h2>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="delivery_address">Adresse</label>
                                <textarea class="form-control <?= isset($errors['delivery_address']) ? 'is-invalid' : '' ?>" id="delivery_address" name="delivery_address" rows="2" required><?= $old('delivery_address', $orderUser?->getAddress() ?? '') ?></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="delivery_postal_code">Code postal</label>
                                <input class="form-control" id="delivery_postal_code" name="delivery_postal_code" maxlength="10" value="<?= $old('delivery_postal_code') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="delivery_city">Ville</label>
                                <input class="form-control <?= isset($errors['delivery_city']) ? 'is-invalid' : '' ?>" id="delivery_city" name="delivery_city" value="<?= $old('delivery_city') ?>" placeholder="Bordeaux" required>
                                <?php if (isset($errors['delivery_city'])): ?><div class="invalid-feedback"><?= $escape($errors['delivery_city']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="delivery_distance_km">Distance depuis Bordeaux (km)</label>
                                <input class="form-control <?= isset($errors['delivery_distance_km']) ? 'is-invalid' : '' ?>" id="delivery_distance_km" name="delivery_distance_km" type="number" min="0" step="0.01" value="<?= $old('delivery_distance_km') ?>">
                                <div class="form-text">Requise uniquement si la ville est hors Bordeaux. Le tarif appliqué est 5 € + 0,59 €/km.</div>
                                <?php if (isset($errors['delivery_distance_km'])): ?><div class="invalid-feedback"><?= $escape($errors['delivery_distance_km']) ?></div><?php endif; ?>
                            </div>

                            <div class="col-12">
                                <section class="card bg-light border-0">
                                    <div class="card-body">
                                        <h2 class="h5 text-primary">Récapitulatif du prix</h2>
                                        <dl class="row mb-0">
                                            <dt class="col-8">Prix du menu</dt>
                                            <dd class="col-4 text-end" id="orderMenuPrice">0,00 €</dd>
                                            <dt class="col-8">Livraison</dt>
                                            <dd class="col-4 text-end" id="orderDeliveryPrice">0,00 €</dd>
                                            <dt class="col-8 fw-bold">Total</dt>
                                            <dd class="col-4 text-end fw-bold" id="orderTotalPrice">0,00 €</dd>
                                        </dl>
                                        <p class="small text-muted mb-0">Le montant affiché ici est indicatif et est recalculé et contrôlé côté serveur lors de la validation.</p>
                                    </div>
                                </section>
                            </div>

                            <div class="col-12">
                                <button class="btn btn-primary" type="submit">Valider la commande</button>
                                <a class="btn btn-outline-secondary ms-2" href="/menus">Retour aux menus</a>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>
