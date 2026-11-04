<?php
$errors = is_array($errors ?? null) ? $errors : [];
$oldInput = is_array($oldInput ?? null) ? $oldInput : [];
$passwordErrors = is_array($passwordErrors ?? null) ? $passwordErrors : [];
$value = static fn (string $key): string => (string) ($oldInput[$key] ?? $user[$key] ?? '');
?>
<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <section class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h2 text-primary">Mon profil</h1>

                        <?php if (!empty($mustChangePassword)): ?>
                            <div class="alert alert-warning" role="alert">
                                Votre mot de passe provisoire est actif. Choisissez un nouveau mot de passe ci-dessous pour continuer.
                            </div>
                        <?php endif; ?>

                        <form method="post" action="/profile" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">

                            <?php foreach ([
                                'first_name' => 'Prénom',
                                'last_name' => 'Nom',
                                'email' => 'Email',
                            ] as $field => $label): ?>
                                <div class="col-md-6">
                                    <label class="form-label" for="<?= $escape($field) ?>"><?= $escape($label) ?></label>
                                    <input class="form-control <?= isset($errors[$field]) ? 'is-invalid' : '' ?>"
                                           id="<?= $escape($field) ?>" name="<?= $escape($field) ?>"
                                           type="<?= $field === 'email' ? 'email' : 'text' ?>"
                                           value="<?= $escape($value($field)) ?>" required>
                                    <?php if (isset($errors[$field])): ?>
                                        <div class="invalid-feedback"><?= $escape($errors[$field]) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <?php /* le numéro enregistré (E.164) est remis dans le champ avec setNumber() par phone-input.js */ ?>
                            <?php foreach (['phone' => 'Téléphone', 'gsm' => 'GSM'] as $field => $label): ?>
                                <div class="col-md-6">
                                    <label class="form-label" for="<?= $escape($field) ?>"><?= $escape($label) ?></label>
                                    <input class="form-control <?= isset($errors[$field]) ? 'is-invalid' : '' ?>"
                                           id="<?= $escape($field) ?>" name="<?= $escape($field) ?>" type="tel"
                                           autocomplete="tel" inputmode="tel" data-phone-input
                                           aria-describedby="<?= $escape($field) ?>_error"
                                           value="<?= $escape($value($field)) ?>" required>
                                    <div class="invalid-feedback <?= isset($errors[$field]) ? 'd-block' : '' ?>" id="<?= $escape($field) ?>_error"><?= $escape($errors[$field] ?? '') ?></div>
                                </div>
                            <?php endforeach; ?>

                            <div class="col-12">
                                <label class="form-label" for="address">Adresse</label>
                                <textarea class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>"
                                          id="address" name="address" required><?= $escape($value('address')) ?></textarea>
                                <?php if (isset($errors['address'])): ?>
                                    <div class="invalid-feedback"><?= $escape($errors['address']) ?></div>
                                <?php endif; ?>
                            </div>

                            <?php if (isset($errors['general'])): ?>
                                <div class="col-12">
                                    <div class="alert alert-danger"><?= $escape($errors['general']) ?></div>
                                </div>
                            <?php endif; ?>

                            <div class="col-12">
                                <button class="btn btn-primary" type="submit">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="card border-0 shadow-sm mt-4" aria-labelledby="passwordTitle">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="h4 text-primary" id="passwordTitle">Changer mon mot de passe</h2>
                        <p class="text-muted small" id="passwordRules">
                            10 caractères minimum, avec au moins une majuscule, une minuscule, un chiffre et un caractère spécial.
                        </p>

                        <?php if ($passwordErrors !== []): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php foreach ($passwordErrors as $passwordError): ?>
                                    <p class="mb-0"><?= $escape((string) $passwordError) ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="/password" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">

                            <div class="col-md-4">
                                <label class="form-label" for="current_password">Mot de passe actuel</label>
                                <input class="form-control" id="current_password" name="current_password"
                                       type="password" autocomplete="current-password" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="new_password">Nouveau mot de passe</label>
                                <input class="form-control" id="new_password" name="new_password"
                                       type="password" autocomplete="new-password" minlength="10"
                                       aria-describedby="passwordRules" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="confirm_password">Confirmation</label>
                                <input class="form-control" id="confirm_password" name="confirm_password"
                                       type="password" autocomplete="new-password" minlength="10" required>
                            </div>

                            <div class="col-12">
                                <button class="btn btn-outline-primary" type="submit">Modifier le mot de passe</button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>
