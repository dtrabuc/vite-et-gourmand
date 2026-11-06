<main class="py-5"><div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 text-primary mb-0">Gestion des clients</h1>
        <?php if (!empty($isAdmin)): ?><a class="btn btn-outline-primary" href="/admin/employees">Employés</a><?php endif; ?>
    </div>

    <form method="get" action="/admin/customers" class="row g-2 mb-4">
        <div class="col-md-6">
            <label class="form-label" for="customer_search">Recherche</label>
            <input class="form-control" id="customer_search" name="search" value="<?= $escape($search ?? '') ?>" placeholder="Nom, email, téléphone">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="customer_active">État</label>
            <select class="form-select" id="customer_active" name="active">
                <option value="">Tous</option>
                <option value="1" <?= $active === true ? 'selected' : '' ?>>Actifs</option>
                <option value="0" <?= $active === false ? 'selected' : '' ?>>Désactivés</option>
            </select>
        </div>
        <div class="col-md-3 align-self-end">
            <button class="btn btn-primary w-100" type="submit">Filtrer</button>
        </div>
    </form>

    <?php foreach ($customers as $customer): ?>
        <section class="card border-0 shadow-sm mb-3">
            <div class="card-body d-flex justify-content-between align-items-center gap-3">
                <div>
                    <strong><?= $escape($customer['first_name'] . ' ' . $customer['last_name']) ?></strong>
                    <div><?= $escape($customer['email']) ?></div>
                    <div class="small text-muted"><?= $escape($customer['phone']) ?><?= !empty($customer['gsm']) ? ' · ' . $escape($customer['gsm']) : '' ?></div>
                    <div class="small text-muted"><?= $escape($customer['address']) ?></div>
                </div>
                <?php if ((bool) $customer['is_active']): ?>
                    <form method="post" action="/admin/customers/<?= (int) $customer['id'] ?>/disable">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                        <button class="btn btn-outline-danger btn-sm" type="submit">Désactiver</button>
                    </form>
                <?php else: ?>
                    <form method="post" action="/admin/customers/<?= (int) $customer['id'] ?>/enable">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                        <button class="btn btn-outline-success btn-sm" type="submit">Réactiver</button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <?php if ($customers === []): ?>
        <p class="text-muted">Aucun client trouvé.</p>
    <?php endif; ?>
</div></main>
