<?php
$oldInput = is_array($oldInput ?? null) ? $oldInput : [];
$errors = is_array($errors ?? null) ? $errors : [];
?>
<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <section class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h2 text-primary">Créer votre compte</h1>
                        <p class="text-muted">Les champs sont nécessaires à la préparation de votre prestation.</p>
                        <p class="alert alert-info">Un mot de passe provisoire vous sera envoyé par e-mail. Vous devrez le changer lors de votre première connexion.</p>

                        <form method="post" action="/register" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">

                            <?php foreach ([
                                'first_name' => 'Prénom',
                                'last_name' => 'Nom',
                            ] as $field => $label): ?>
                                <div class="col-md-6">
                                    <label class="form-label" for="<?= $escape($field) ?>"><?= $escape($label) ?></label>
                                    <input class="form-control <?= isset($errors[$field]) ? 'is-invalid' : '' ?>"
                                           id="<?= $escape($field) ?>" name="<?= $escape($field) ?>"
                                           value="<?= $escape($oldInput[$field] ?? '') ?>" required>
                                    <?php if (isset($errors[$field])): ?>
                                        <div class="invalid-feedback"><?= $escape($errors[$field]) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <?php /* Le numéro français se choisit dans intl-tel-input. */ ?>
                            <?php foreach (['phone' => 'Téléphone'] as $field => $label): ?>
                                <div class="col-md-6">
                                    <label class="form-label" for="<?= $escape($field) ?>"><?= $escape($label) ?></label>
                                    <input class="form-control <?= isset($errors[$field]) ? 'is-invalid' : '' ?>"
                                           id="<?= $escape($field) ?>" name="<?= $escape($field) ?>" type="tel"
                                           autocomplete="tel" inputmode="tel" data-phone-input
                                           aria-describedby="<?= $escape($field) ?>_error"
                                           value="<?= $escape($oldInput[$field] ?? '') ?>" required>
                                    <div class="invalid-feedback <?= isset($errors[$field]) ? 'd-block' : '' ?>" id="<?= $escape($field) ?>_error"><?= $escape($errors[$field] ?? '') ?></div>
                                </div>
                            <?php endforeach; ?>

                            <div class="col-md-6">
                                <label class="form-label" for="register_email">Adresse email</label>
                                <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                       id="register_email" name="email" type="email"
                                       value="<?= $escape($oldInput['email'] ?? '') ?>" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?= $escape($errors['email']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="address">Adresse postale</label>
                                <textarea class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>"
                                          id="address" name="address" required><?= $escape($oldInput['address'] ?? '') ?></textarea>
                                <?php if (isset($errors['address'])): ?>
                                    <div class="invalid-feedback"><?= $escape($errors['address']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input <?= isset($errors['privacy_consent']) ? 'is-invalid' : '' ?>"
                                           type="checkbox" id="privacy_consent" name="privacy_consent" value="1"
                                           aria-describedby="privacyConsentHelp" required>
                                    <label class="form-check-label" for="privacy_consent">
                                        J’ai lu la <a href="/confidentialite" target="_blank" rel="noopener">politique de confidentialité</a>
                                        et j’accepte les <a href="/cgv" target="_blank" rel="noopener">conditions générales de vente</a>.
                                    </label>
                                    <?php if (isset($errors['privacy_consent'])): ?>
                                        <div class="invalid-feedback"><?= $escape($errors['privacy_consent']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <p class="form-text mb-0" id="privacyConsentHelp">
                                    Vos données servent uniquement à gérer votre compte et vos commandes. Vous pouvez demander leur suppression à tout moment.
                                </p>
                            </div>

                            <?php if (isset($errors['general'])): ?>
                                <div class="col-12">
                                    <div class="alert alert-danger"><?= $escape($errors['general']) ?></div>
                                </div>
                            <?php endif; ?>

                            <div class="col-12">
                                <button class="btn btn-primary" type="submit">Créer mon compte</button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>
