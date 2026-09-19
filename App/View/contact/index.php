<?php
$errors = $_SESSION['contact_errors'] ?? [];
$old = $_SESSION['contact_old_input'] ?? [];
unset($_SESSION['contact_errors'], $_SESSION['contact_old_input']);
?>
<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <section class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h2 text-primary">Contact</h1>
                        <p class="text-muted">Une question sur un menu ou une prestation ? Écrivez-nous.</p>
                        <?php if (isset($errors['general'])): ?><div class="alert alert-danger"><?= $escape($errors['general']) ?></div><?php endif; ?>
                        <form method="post" action="/contact" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                            <div class="col-12">
                                <label class="form-label" for="contact_subject">Titre</label>
                                <input class="form-control <?= isset($errors['subject']) ? 'is-invalid' : '' ?>" id="contact_subject" name="subject" maxlength="150" required value="<?= $escape($old['subject'] ?? '') ?>">
                                <?php if (isset($errors['subject'])): ?><div class="invalid-feedback"><?= $escape($errors['subject']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="contact_email">Email</label>
                                <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="contact_email" name="email" type="email" required value="<?= $escape($old['email'] ?? '') ?>">
                                <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= $escape($errors['email']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="contact_message">Description</label>
                                <textarea class="form-control <?= isset($errors['message']) ? 'is-invalid' : '' ?>" id="contact_message" name="message" rows="7" required><?= $escape($old['message'] ?? '') ?></textarea>
                                <?php if (isset($errors['message'])): ?><div class="invalid-feedback"><?= $escape($errors['message']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-12"><button class="btn btn-primary" type="submit">Envoyer</button></div>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>
