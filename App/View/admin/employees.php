<main class="py-5"><div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 text-primary mb-0">Gestion des employés</h1>
        <a class="btn btn-outline-primary" href="/admin/customers">Clients</a>
    </div>

    <section class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5">Créer un employé</h2>
            <p class="small text-muted">Le mot de passe doit contenir au moins 10 caractères avec majuscule, minuscule, chiffre et caractère spécial.</p>
            <form method="post" action="/admin/employees" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                <?php foreach (['first_name' => 'Prénom', 'last_name' => 'Nom', 'email' => 'Email', 'phone' => 'Téléphone', 'gsm' => 'GSM', 'address' => 'Adresse'] as $field => $label): ?>
                    <div class="<?= $field === 'address' ? 'col-12' : 'col-md-6' ?>">
                        <label class="form-label" for="employee_<?= $field ?>"><?= $label ?></label>
                        <?php if ($field === 'address'): ?>
                            <textarea class="form-control" id="employee_<?= $field ?>" name="<?= $field ?>" required></textarea>
                        <?php elseif ($field === 'phone' || $field === 'gsm'): ?>
                            <?php /* même champ téléphone que côté client (intl-tel-input) */ ?>
                            <input class="form-control" id="employee_<?= $field ?>" name="<?= $field ?>" type="tel"
                                   autocomplete="tel" inputmode="tel" data-phone-input
                                   aria-describedby="employee_<?= $field ?>_error" required>
                            <div class="invalid-feedback" id="employee_<?= $field ?>_error"></div>
                        <?php else: ?>
                            <input class="form-control" id="employee_<?= $field ?>" name="<?= $field ?>" type="<?= $field === 'email' ? 'email' : 'text' ?>" required>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <div class="col-md-6">
                    <label class="form-label" for="employee_password">Mot de passe</label>
                    <input class="form-control" id="employee_password" name="password" type="password" required>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">Créer l'employé</button>
                </div>
            </form>
        </div>
    </section>

    <?php foreach ($employees as $employee): ?>
        <section class="card border-0 shadow-sm mb-3">
            <div class="card-body d-flex justify-content-between align-items-center gap-3">
                <div>
                    <strong><?= $escape($employee['first_name'] . ' ' . $employee['last_name']) ?></strong>
                    <div class="small text-muted">
                        <?= $escape($employee['email']) ?> · <?= $employee['is_active'] ? 'Actif' : 'Désactivé' ?>
                    </div>
                </div>
                <?php if ($employee['is_active']): ?>
                    <form method="post" action="/admin/employees/<?= (int) $employee['id'] ?>/disable">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                        <button class="btn btn-outline-danger btn-sm" type="submit">Désactiver</button>
                    </form>
                <?php else: ?>
                    <form method="post" action="/admin/employees/<?= (int) $employee['id'] ?>/enable">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                        <button class="btn btn-outline-success btn-sm" type="submit">Réactiver</button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div></main>
